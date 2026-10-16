<?php

namespace App\Services;

use App\Models\Etudiant;
use App\Models\Loyer;
use App\Models\OnlinePayment;
use App\Models\Paiement;
use App\Models\PaymentProvider;
use App\Payments\OnlinePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Props de la page React « Mes paiements » de l'espace étudiant (Etudiant/MesPaiements) :
 * situation des loyers, loyer réglable en ligne, historique des paiements et suivi des paiements en ligne.
 * Le montant payable vient toujours du solde serveur (OnlinePaymentService) ; seules les valeurs affichées sont envoyées.
 */
class EtudiantPaiements
{
    /** Statuts de `paiements.statut_paiement` montrés à l'étudiant (les annulés sont masqués) : libellé et variante du badge. */
    public const STATUTS = [
        'valide' => ['Payé', 'success'],
        'en_attente' => ['En attente', 'warning'],
        'echoue' => ['Échoué', 'danger'],
        'rembourse' => ['Remboursé', 'info'],
    ];

    private const MOIS = [1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    public function __construct(private OnlinePaymentService $paiementsEnLigne) {}

    public function props(Etudiant $etudiant, Request $request): array
    {
        $id = (string) $etudiant->getKey();
        $statut = array_key_exists((string) $request->query('statut'), self::STATUTS) ? (string) $request->query('statut') : null;
        $loyers = Loyer::where('etudiant_id', $id)->where('statut', '!=', 'annule')
            ->orderByDesc('annee_facturation')->orderByDesc('mois_facturation')->get();
        $paiements = Paiement::where('resident_id', $id)
            ->whereIn('statut_paiement', array_keys(self::STATUTS))
            ->when($statut, fn ($q) => $q->where('statut_paiement', $statut))
            ->latest('created_at')->orderByDesc('id_paiement')
            ->paginate(10)->withPath(route('etudiants.dashboard'))->appends($request->except('page'));

        return [
            'situation' => $this->situation($loyers),
            'reglement' => $this->reglement($id, (string) $etudiant->telephone1),
            'loyers' => $loyers->take(12)->map(fn (Loyer $loyer) => $this->loyer($loyer))->values()->all(),
            'statut' => $statut,
            'statuts' => collect(self::STATUTS)->map(fn ($s, $code) => ['code' => $code, 'libelle' => $s[0]])->values()->all(),
            'paiements' => collect($paiements->items())->map(fn (Paiement $paiement) => $this->paiement($paiement))->values()->all(),
            'pagination' => EtudiantMaintenance::pagination($paiements),
            'enLigne' => $this->enLigne($id),
            'urls' => [
                'payer' => route('etudiants.online-payments.store'),
                'page' => route('etudiants.dashboard', ['section' => 'paiements']),
                'documents' => route('etudiants.documents.personal'),
            ],
        ];
    }

    /** Réseaux réellement utilisables par l'étudiant (identifiants complets) : code et nom seulement, jamais les clés. */
    public function reseaux(): array
    {
        return rescue(fn () => PaymentProvider::payableToStudents()
            ->map(fn (PaymentProvider $reseau) => ['code' => $reseau->code, 'nom' => $reseau->name])
            ->values()->all(), [], false);
    }

    private function situation(Collection $loyers): array
    {
        $attendu = (float) $loyers->sum('montant_total');
        $paye = (float) $loyers->sum('total_paye');
        $reste = (float) $loyers->sum('solde_restant');
        $impayes = $loyers->filter(fn (Loyer $loyer) => (float) $loyer->solde_restant > 0);
        $enRetard = $impayes->filter(fn (Loyer $loyer) => $this->enRetard($loyer));
        // Prochaine échéance : le plus ancien loyer non soldé.
        $prochain = $impayes->sortBy(fn (Loyer $loyer) => sprintf('%04d%02d', $loyer->annee_facturation, $loyer->mois_facturation))->first();

        return [
            'totalPaye' => formatSystemAmount($paye),
            'reste' => formatSystemAmount($reste),
            'penalites' => (float) $loyers->sum('total_penalites') > 0 ? formatSystemAmount($loyers->sum('total_penalites')) : null,
            'recouvrement' => $attendu > 0 ? (int) min(100, round($paye / $attendu * 100)) : null,
            'nbImpayes' => $impayes->count(),
            'nbRetards' => $enRetard->count(),
            'prochaine' => $prochain ? [
                'periode' => $this->periode($prochain),
                'montant' => formatSystemAmount($prochain->solde_restant),
                'limite' => $prochain->date_limit_paiement ? formatDateFr($prochain->date_limit_paiement->format('Y-m-d'), true) : null,
            ] : null,
            'etat' => match (true) {
                $enRetard->isNotEmpty() => ['libelle' => 'En retard', 'ton' => 'red'],
                $impayes->isNotEmpty() => ['libelle' => 'Loyer à régler', 'ton' => 'amber'],
                default => ['libelle' => 'À jour', 'ton' => 'green'],
            },
        ];
    }

    /**
     * Loyer réglable en ligne (le plus ancien non soldé de la chambre occupée, comme OnlinePaymentController),
     * ou la raison pour laquelle aucun paiement n'est possible.
     */
    private function reglement(string $etudiantId, string $telephone): array
    {
        // Numéro proposé par défaut : les 10 chiffres locaux du téléphone principal, s'il est au bon format.
        $chiffres = preg_replace('/\D/', '', $telephone);
        $chiffres = strlen($chiffres) > 10 ? preg_replace('/^(00)?225/', '', $chiffres) : $chiffres;
        $reseaux = $this->reseaux();
        $assignation = $this->paiementsEnLigne->currentAssignment($etudiantId);
        $loyer = $assignation?->chambre ? $this->paiementsEnLigne->rentForAssignment($etudiantId, $assignation) : null;

        return [
            'reseaux' => $reseaux,
            'telephone' => strlen($chiffres) === 10 ? $chiffres : '',
            'indisponible' => match (true) {
                ! $assignation?->chambre => 'Aucune chambre occupée n’est enregistrée dans votre dossier. Contactez le service d’hébergement.',
                ! $loyer => 'Aucun loyer restant à régler. Si votre loyer n’a pas encore été établi, contactez le service financier.',
                $reseaux === [] => 'Aucun moyen de paiement en ligne n’est disponible pour le moment. Contactez le service financier.',
                default => null,
            },
            'loyer' => $loyer ? [
                'id' => (string) $loyer->getKey(),
                'chambre' => $assignation->chambre->numero_chambre,
                'periode' => $this->periode($loyer),
                'montant' => formatSystemAmount($loyer->montant_total),
                'penalites' => (float) $loyer->total_penalites > 0 ? formatSystemAmount($loyer->total_penalites) : null,
                'dejaPaye' => (float) $loyer->total_paye > 0 ? formatSystemAmount($loyer->total_paye) : null,
                'solde' => formatSystemAmount($loyer->solde_restant),
                // Plafond du montant saisi (francs CFA entiers) ; le serveur revérifie au lancement.
                'soldeValeur' => (int) $loyer->solde_restant,
            ] : null,
        ];
    }

    private function loyer(Loyer $loyer): array
    {
        $solde = (float) $loyer->solde_restant;
        [$libelle, $ton] = match (true) {
            $loyer->statut === 'suspendu' => ['Suspendu', 'neutral'],
            $solde <= 0 => ['Payé', 'success'],
            $this->enRetard($loyer) => ['En retard', 'danger'],
            (float) $loyer->total_paye > 0 => ['Payé en partie', 'warning'],
            default => ['À payer', 'warning'],
        };

        return [
            'id' => (string) $loyer->getKey(),
            'periode' => $this->periode($loyer),
            'montant' => formatSystemAmount($loyer->montant_total),
            'paye' => formatSystemAmount($loyer->total_paye),
            'solde' => formatSystemAmount($solde),
            'limite' => $loyer->date_limit_paiement ? $loyer->date_limit_paiement->format('d/m/Y') : null,
            'statut' => ['libelle' => $libelle, 'ton' => $ton],
        ];
    }

    private function paiement(Paiement $paiement): array
    {
        [$libelle, $ton] = self::STATUTS[$paiement->statut_paiement] ?? [ucfirst((string) $paiement->statut_paiement), 'neutral'];
        $date = $paiement->date_paiement ?? $paiement->created_at;

        return [
            'id' => (string) $paiement->getKey(),
            'periode' => $this->moisPayes($paiement->mois),
            'date' => $date?->format('d/m/Y'),
            'reference' => $paiement->numero_paiement ?: $paiement->reference_transaction,
            'methode' => $paiement->methode_paiement ? $paiement->methode_label : null,
            'montant' => formatSystemAmount($paiement->montant),
            'statut' => ['libelle' => $libelle, 'ton' => $ton],
            'recu' => in_array($paiement->statut_paiement, ['valide', 'rembourse'], true)
                ? route('etudiants.documents.receipt', $paiement->getKey())
                : null,
        ];
    }

    /** Cinq derniers paiements en ligne, avec leur page de suivi. */
    private function enLigne(string $etudiantId): array
    {
        return rescue(fn () => OnlinePayment::where('student_id', $etudiantId)->latest()->limit(5)->get()
            ->map(fn (OnlinePayment $paiement) => [
                'id' => (string) $paiement->getKey(),
                'date' => $paiement->created_at?->format('d/m/Y H:i'),
                'reseau' => PaymentProvider::NETWORKS[$paiement->provider_code] ?? $paiement->provider_code,
                'montant' => formatSystemAmount($paiement->amount),
                'statut' => [
                    'libelle' => $paiement->status_label,
                    'ton' => match ($paiement->status) {
                        'succeeded' => 'success',
                        'failed' => 'danger',
                        'review' => 'warning',
                        default => 'info',
                    },
                ],
                'url' => route('etudiants.online-payments.show', $paiement),
            ])->values()->all(), [], false);
    }

    private function enRetard(Loyer $loyer): bool
    {
        return (float) $loyer->solde_restant > 0 && $loyer->date_limit_paiement?->isBefore(today());
    }

    private function periode(Loyer $loyer): string
    {
        return (self::MOIS[(int) $loyer->mois_facturation] ?? $loyer->mois_facturation).' '.$loyer->annee_facturation;
    }

    /** `paiements.mois` : liste de « mm-aaaa » (ou texte libre) → « Septembre 2026, Octobre 2026 ». */
    private function moisPayes($mois): ?string
    {
        $liste = is_array($mois) ? $mois : (is_string($mois) && $mois !== '' ? (json_decode($mois, true) ?? [$mois]) : []);

        return collect(is_array($liste) ? $liste : [$liste])
            ->map(fn ($m) => preg_match('/^(\d{1,2})-(\d{4})$/', (string) $m, $p) && isset(self::MOIS[(int) $p[1]])
                ? self::MOIS[(int) $p[1]].' '.$p[2]
                : (string) $m)
            ->filter()
            ->implode(', ') ?: null;
    }
}
