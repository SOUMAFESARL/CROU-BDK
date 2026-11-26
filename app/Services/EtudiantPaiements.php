<?php

namespace App\Services;

use App\Http\Services\PaiementService;
use App\Models\AssignationChambre;
use App\Models\Etudiant;
use App\Models\Loyer;
use App\Models\Paiement;
use App\Models\PaymentProvider;
use App\Payments\OnlinePaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Props de la page React « Mes paiements » de l'espace étudiant (Etudiant/MesPaiements) : loyers des mois écoulés
 * du séjour de la campagne (payé, avec quittance, ou non payé), loyer réglable en ligne et historique des paiements.
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

    /** Onglets de la page (`?onglet=`) : « Mes loyers » par défaut. */
    public const ONGLETS = ['loyers', 'historique'];

    /** Durée de logement d'une campagne, en mois : le relevé des loyers ne va pas au-delà du 10e mois du séjour. */
    public const MOIS_PAR_CAMPAGNE = 10;

    private const MOIS = [1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    public function __construct(private OnlinePaymentService $paiementsEnLigne) {}

    public function props(Etudiant $etudiant, Request $request): array
    {
        $id = (string) $etudiant->getKey();
        $statut = array_key_exists((string) $request->query('statut'), self::STATUTS) ? (string) $request->query('statut') : null;
        $paiements = Paiement::where('resident_id', $id)
            ->whereIn('statut_paiement', array_keys(self::STATUTS))
            ->when($statut, fn ($q) => $q->where('statut_paiement', $statut))
            ->latest('created_at')->orderByDesc('id_paiement')
            ->paginate(10)->withPath(route('etudiants.dashboard'))->appends(['onglet' => 'historique'] + $request->except(['page', 'onglet']));
        $releve = $this->releve($id);

        // Un ancien lien filtré ou paginé (sans `onglet`) ouvre directement l'historique ; un ancien onglet retiré, les loyers.
        $onglet = $request->query('onglet');
        $parDefaut = $statut || $request->query('page') ? 'historique' : 'loyers';

        return [
            'onglet' => in_array($onglet, self::ONGLETS, true) ? $onglet : $parDefaut,
            'reglement' => $this->reglement($id, (string) $etudiant->telephone1),
            'campagne' => $releve['campagne'],
            'loyers' => $releve['mois']->sortByDesc('cle')->map(fn (array $mois) => $this->ligneMois($mois))->values()->all(),
            'statut' => $statut,
            'statuts' => collect(self::STATUTS)->map(fn ($s, $code) => ['code' => $code, 'libelle' => $s[0]])->values()->all(),
            'paiements' => collect($paiements->items())->map(fn (Paiement $paiement) => $this->paiement($paiement))->values()->all(),
            'pagination' => EtudiantMaintenance::pagination($paiements),
            'urls' => [
                'payer' => route('etudiants.online-payments.store'),
                'page' => route('etudiants.dashboard', ['section' => 'paiements']),
                'documents' => route('etudiants.documents.personal'),
            ],
        ];
    }

    /**
     * Données de la quittance de loyer d'un mois (`aaaa-mm`) du séjour de l'étudiant, ou null si ce mois
     * n'est pas un mois écoulé du séjour ou n'est pas entièrement payé.
     */
    public function quittance(Etudiant $etudiant, string $cle): ?array
    {
        $releve = $this->releve((string) $etudiant->getKey());
        $mois = $releve['mois']->firstWhere('cle', $cle);
        if (! $mois || ! $mois['paye']) {
            return null;
        }
        $chambre = $releve['sejour']->chambre;

        return [
            'etudiant' => $etudiant,
            'numero' => 'Q-'.str_replace('-', '', $cle).'-'.strtoupper(substr(hash('sha256', $etudiant->getKey().$cle), 0, 6)),
            'periode' => $mois['periode'],
            'campagne' => $releve['campagne'],
            'civilite' => app(ParcoursEtudiant::class)->civilite($etudiant),
            'parcours' => app(ParcoursEtudiant::class)->pour((string) $etudiant->getKey(), $releve['sejour']->campagne_id),
            // Numéro de la chambre, puis la cité : « 1 · Cité Zanzan ».
            'chambre' => collect([$chambre?->numero_chambre, $chambre?->cite?->name])->filter()->join(' · '),
            'montant' => $mois['montant'] !== null ? formatSystemAmount($mois['montant']) : null,
            'datePaiement' => $mois['datePaiement']?->format('d/m/Y'),
            // Montant affecté à ce mois (un paiement de plusieurs mois est réparti à parts égales).
            'paiements' => $mois['paiements']->map(fn (Paiement $paiement) => [
                'numero' => $paiement->numero_paiement ?: $paiement->getKey(),
                'date' => ($paiement->date_paiement ?? $paiement->created_at)?->format('d/m/Y'),
                'methode' => $paiement->methode_paiement ? $paiement->methode_label : '—',
                'montant' => formatSystemAmount($this->partDuMois($paiement, $cle)),
            ])->values()->all(),
        ];
    }

    /**
     * Mois de loyer du séjour, tels que le relevé « Mes loyers » les calcule, pour le formulaire admin « Nouveau paiement » :
     * mois payés et mois en retard (non payés, échéance passée), dans l'ordre chronologique, au format `mm-aaaa`.
     *
     * @return array{payes: list<string>, en_retard: list<string>}
     */
    public function etatDesMois(string $etudiantId): array
    {
        $mois = $this->releve($etudiantId)['mois']->sortBy('cle');
        $code = fn (array $m) => substr($m['cle'], 5, 2).'-'.substr($m['cle'], 0, 4);

        return [
            'payes' => $mois->where('paye', true)->map($code)->values()->all(),
            'en_retard' => $mois->where('enRetard', true)->map($code)->values()->all(),
        ];
    }

    /** Réseaux réellement utilisables par l'étudiant (identifiants complets) : code et nom seulement, jamais les clés. */
    public function reseaux(): array
    {
        return rescue(fn () => PaymentProvider::payableToStudents()
            ->map(fn (PaymentProvider $reseau) => ['code' => $reseau->code, 'nom' => $reseau->name])
            ->values()->all(), [], false);
    }

    /**
     * Séjour de la campagne (dernière attribution confirmée et commencée) et ses mois de loyer, dans la limite des
     * 10 mois de logement de la campagne (MOIS_PAR_CAMPAGNE) : les mois écoulés jusqu'au mois en cours (ou au mois
     * de fin du séjour), payés ou non, puis les mois à venir déjà payés (avance).
     *
     * Le premier mois est le premier mois facturé sur ce séjour ou payé d'avance pour la campagne (avance versée
     * avant l'attribution, qui peut précéder ou suivre l'entrée) ; à défaut, le mois d'entrée.
     *
     * Un mois est payé quand une avance validée le couvre (l'avance payée avant l'attribution est un loyer), quand
     * son loyer facturé est soldé ou, sans loyer facturé, quand un paiement validé de loyer le couvre (`paiements.mois`).
     *
     * @return array{sejour: ?AssignationChambre, campagne: ?string, mois: Collection<int, array>}
     */
    private function releve(string $etudiantId): array
    {
        $sejour = AssignationChambre::where('etudiant_id', $etudiantId)->where('statut', 'confirmee')
            ->where('date_assignation', '<=', now())
            ->orderByDesc('date_assignation')->orderByDesc('created_at')->first();
        if (! $sejour) {
            return ['sejour' => null, 'campagne' => null, 'mois' => collect()];
        }

        $loyers = Loyer::where('etudiant_id', $etudiantId)->where('assignation_id', $sejour->getKey())
            ->where('statut', '!=', 'annule')->get()
            ->keyBy(fn (Loyer $loyer) => sprintf('%04d-%02d', $loyer->annee_facturation, $loyer->mois_facturation));
        $paiements = Paiement::where('resident_id', $etudiantId)->where('statut_paiement', 'valide')
            ->where(fn ($q) => $q->whereIn('type_paiement', PaiementService::TYPES_LOYER)->orWhereNull('type_paiement'))
            ->orderBy('date_paiement')->orderBy('created_at')->get();

        // Mois payés d'avance pour la campagne (avance versée avant l'attribution, sans loyer ni attribution).
        $avances = $sejour->campagne_id
            ? Paiement::where('resident_id', $etudiantId)->where('statut_paiement', 'valide')
                ->where('type_paiement', PaiementService::TYPE_AVANCE)->deCampagne($sejour->campagne_id)->get()
                ->flatMap(fn (Paiement $paiement) => $this->clesMois($paiement->mois))
            : collect();
        $debut = $loyers->keys()->merge($avances)->min() ?? $sejour->date_assignation->format('Y-m');
        $debut = CarbonImmutable::createFromFormat('!Y-m', $debut);
        $ecoule = CarbonImmutable::parse($sejour->date_fin && $sejour->date_fin->isPast() ? $sejour->date_fin : now())->startOfMonth();
        // Calendrier des loyers de la campagne (Paiements › Calendrier des loyers) : ses 10 mois et leurs échéances ;
        // sans calendrier, 10 mois consécutifs à partir du premier mois du séjour, échéance le 10.
        $service = app(CalendrierLoyer::class);
        $calendrier = $service->de($sejour->campagne);
        $dates = $calendrier
            ? collect($calendrier)->map(fn (array $entree) => CarbonImmutable::createFromFormat('!Y-m', $entree['mois']))
            : collect(range(0, self::MOIS_PAR_CAMPAGNE - 1))->map(fn (int $rang) => $debut->addMonths($rang));

        $mensuel = (float) ($sejour->chambre?->typeChambre?->montant ?: $sejour->chambre?->prix ?: 0);
        $mois = collect();
        foreach ($dates as $date) {
            $cle = $date->format('Y-m');
            $loyer = $loyers->get($cle);
            $couverture = $paiements->filter(fn (Paiement $paiement) => in_array($cle, $this->clesMois($paiement->mois), true)
                || ($loyer && $paiement->loyer_id === $loyer->getKey()))->values();
            // L'avance payée au départ est un loyer : un mois qu'elle couvre est payé, même si le loyer facturé
            // à l'attribution n'a pas été soldé.
            $parAvance = $couverture->contains(fn (Paiement $paiement) => $paiement->type_paiement === PaiementService::TYPE_AVANCE
                && in_array($cle, $this->clesMois($paiement->mois), true));
            $paye = $parAvance || ($loyer ? (float) $loyer->solde_restant <= 0 : $couverture->isNotEmpty());
            // Mois à venir : seulement s'il est déjà payé (avance). Mois du calendrier avant le début du séjour
            // (arrivée en cours d'année) : seulement s'il a été facturé ou payé.
            if (! $paye && ($date > $ecoule || ($date < $debut && ! $loyer))) {
                continue;
            }
            $echeance = $loyer?->date_limit_paiement ?? ($calendrier ? $service->echeance($calendrier, $date->month, $date->year) : null);
            // Sans loyer facturé, le montant du mois est la part des paiements qui le couvrent (avance de 2 mois → moitié).
            $part = $couverture->filter(fn (Paiement $paiement) => in_array($cle, $this->clesMois($paiement->mois), true))
                ->sum(fn (Paiement $paiement) => $this->partDuMois($paiement, $cle));
            $montant = $loyer ? (float) $loyer->montant_total : ($part > 0 ? $part : ($mensuel > 0 ? $mensuel : null));
            $dernier = $couverture->last();

            $mois->push([
                'cle' => $cle,
                'periode' => self::MOIS[$date->month].' '.$date->year,
                'loyer' => $loyer,
                'paye' => $paye,
                'montant' => $montant,
                'avance' => $parAvance,
                'echeance' => $echeance,
                'enRetard' => ! $paye && ($echeance ? $echeance->isBefore(today()) : $date->isBefore(now()->startOfMonth())),
                'datePaiement' => $paye && $dernier ? ($dernier->date_paiement ?? $dernier->created_at) : null,
                'paiements' => $paye ? $couverture : collect(),
            ]);
        }

        return [
            'sejour' => $sejour,
            'campagne' => $sejour->campagne_id ? $sejour->campagne?->annee_campagne : null,
            'mois' => $mois,
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
                (bool) ($ferme = OnlinePaymentService::indisponibilite()) => $ferme,
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
                'dejaPaye' => (float) $loyer->total_paye > 0 ? formatSystemAmount($loyer->total_paye) : null,
                'solde' => formatSystemAmount($loyer->solde_restant),
                // Plafond du montant saisi (francs CFA entiers) ; le serveur revérifie au lancement.
                'soldeValeur' => (int) $loyer->solde_restant,
            ] : null,
        ];
    }

    /** Ligne de « Mes loyers » : payé (quittance à voir ou télécharger) ou non payé. */
    private function ligneMois(array $mois): array
    {
        $quittance = route('etudiants.quittances.show', $mois['cle']);

        return [
            'id' => $mois['cle'],
            'periode' => $mois['periode'],
            'montant' => $mois['montant'] !== null ? formatSystemAmount($mois['montant']) : null,
            'limite' => $mois['echeance']?->format('d/m/Y'),
            'datePaiement' => $mois['datePaiement']?->format('d/m/Y'),
            'paye' => $mois['paye'],
            'avance' => $mois['avance'],
            'statut' => $mois['paye']
                ? ['libelle' => 'Payé', 'ton' => 'success']
                : ['libelle' => 'Non payé', 'ton' => $mois['enRetard'] ? 'danger' : 'warning'],
            'quittance' => $mois['paye'] ? ['voir' => $quittance, 'telecharger' => $quittance.'?telecharger=1'] : null,
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
            'type' => $paiement->type_paiement ? $paiement->type_label : null,
            'methode' => $paiement->methode_paiement ? $paiement->methode_label : null,
            'montant' => formatSystemAmount($paiement->montant),
            'statut' => ['libelle' => $libelle, 'ton' => $ton],
            // Même reçu que l'administration (App\Services\RecuPaiement), pour les paiements validés ou remboursés.
            'recu' => in_array($paiement->statut_paiement, ['valide', 'rembourse'], true) ? [
                'voir' => $recu = route('etudiants.paiements.recu', $paiement->getKey()),
                'telecharger' => $recu.'?telecharger=1',
            ] : null,
        ];
    }

    private function periode(Loyer $loyer): string
    {
        return (self::MOIS[(int) $loyer->mois_facturation] ?? $loyer->mois_facturation).' '.$loyer->annee_facturation;
    }

    /** `paiements.mois` : liste de « mm-aaaa » (ou texte libre). */
    private function listeMois($mois): array
    {
        $liste = is_array($mois) ? $mois : (is_string($mois) && $mois !== '' ? (json_decode($mois, true) ?? [$mois]) : []);

        return is_array($liste) ? $liste : [$liste];
    }

    /**
     * Part d'un paiement affectée au mois `aaaa-mm` : réparti à parts égales sur les mois qu'il couvre
     * (avance de 16 000 sur 2 mois → 8 000) ; un paiement rattaché au seul loyer du mois compte en entier.
     */
    private function partDuMois(Paiement $paiement, string $cle): float
    {
        $cles = $this->clesMois($paiement->mois);

        return in_array($cle, $cles, true) ? (float) $paiement->montant / count($cles) : (float) $paiement->montant;
    }

    /** `paiements.mois` → clés « aaaa-mm » des mois reconnus. */
    private function clesMois($mois): array
    {
        return collect($this->listeMois($mois))
            ->map(fn ($m) => preg_match('/^(\d{1,2})-(\d{4})$/', (string) $m, $p) && isset(self::MOIS[(int) $p[1]])
                ? sprintf('%04d-%02d', $p[2], $p[1])
                : null)
            ->filter()->values()->all();
    }

    /** `paiements.mois` : liste de « mm-aaaa » (ou texte libre) → « Septembre 2026, Octobre 2026 ». */
    private function moisPayes($mois): ?string
    {
        return collect($this->listeMois($mois))
            ->map(fn ($m) => preg_match('/^(\d{1,2})-(\d{4})$/', (string) $m, $p) && isset(self::MOIS[(int) $p[1]])
                ? self::MOIS[(int) $p[1]].' '.$p[2]
                : (string) $m)
            ->filter()
            ->implode(', ') ?: null;
    }
}
