<?php

namespace App\Http\Services;

use App\Http\Services\Eligibilite\CriteresCampagne;
use App\Models\Campagne;
use App\Models\DemandeEtudiant;
use App\Models\Etudiant;
use App\Models\Reclamation;
use App\Models\ReclamationPeriode;
use App\Models\User;
use App\Services\AdminAccess;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Réclamations sur les demandes d'hébergement (docs/reclamations.md) : dépôt public pendant une période ouverte,
 * rattachement à la demande de l'étudiant, traitement par l'administration. Une réclamation ne modifie jamais l'état
 * du dossier : la décision reste prise sur la fiche de la demande (`DossierWorkflow`, dérogation…).
 */
class ReclamationService
{
    public function __construct(private CriteresCampagne $criteres) {}

    /** Les tables existent (migration passée). */
    public function disponible(): bool
    {
        return Schema::hasTable('reclamations') && Schema::hasTable('reclamation_periodes');
    }

    /** Période de réclamation ouverte aujourd'hui (une seule à la fois), ou null. */
    public function periodeOuverte(): ?ReclamationPeriode
    {
        return $this->disponible() ? ReclamationPeriode::ouvertes()->with('campagne')->orderBy('date_debut')->first() : null;
    }

    /**
     * Types de demande ouverts par la campagne : seuls choix du formulaire.
     *
     * @return list<string>
     */
    public function typesOuverts(Campagne $campagne): array
    {
        return array_keys(array_filter($this->criteres->volets($campagne)));
    }

    /**
     * Dépose une réclamation. La demande est retrouvée par le nom complet (ordre des mots, casse et accents indifférents)
     * et le numéro de table au bac ou matricule, parmi les demandes déposées pour la campagne de la période.
     *
     * @param  array{nom_prenoms: string, num_bac_matricule: string, type_demande: string, motif: string}  $donnees
     */
    public function deposer(ReclamationPeriode $periode, array $donnees): Reclamation
    {
        $demande = $this->demandeDe($periode, $donnees['nom_prenoms'], $donnees['num_bac_matricule']);
        if ($this->criteres->voletEtudiant($periode->campagne, (string) $demande->etudiant_id) !== $donnees['type_demande']) {
            $this->invalide('type_demande', 'Le type choisi ne correspond pas à votre demande pour cette campagne.');
        }

        return DB::transaction(function () use ($periode, $donnees, $demande) {
            // Verrou sur la demande : deux envois simultanés ne créent qu'une réclamation.
            DemandeEtudiant::whereKey($demande->getKey())->lockForUpdate()->first();
            if (Reclamation::where('demande_id', $demande->getKey())->nonTraitees()->exists()) {
                $this->invalide('motif', 'Une réclamation est déjà en cours de traitement pour votre demande.');
            }

            return Reclamation::create([
                'periode_id' => $periode->getKey(),
                'campagne_id' => $periode->campagne_id,
                'demande_id' => (string) $demande->getKey(),
                'etudiant_id' => (string) $demande->etudiant_id,
                'nom_prenoms' => Str::squish($donnees['nom_prenoms']),
                'num_bac_matricule' => Str::upper(Str::squish($donnees['num_bac_matricule'])),
                'type_demande' => $donnees['type_demande'],
                'motif' => trim($donnees['motif']),
                'statut' => Reclamation::EN_ATTENTE,
            ]);
        });
    }

    /** Change le statut d'une réclamation. La réponse (colonne `reponse`) n'est plus saisie pour l'instant ; les anciennes sont conservées. */
    public function traiter(Reclamation $reclamation, string $statut, ?User $admin): Reclamation
    {
        $reclamation->update([
            'statut' => $statut,
            'traite_par' => $admin?->id_user,
            'traite_par_nom' => $admin?->nom_affichage ?? $admin?->name,
            'traite_at' => now(),
        ]);

        return $reclamation;
    }

    /**
     * Crée ou modifie une période. Elle commence après la fin de la campagne concernée (le lien de réclamation remplace
     * donc celui de la demande d'hébergement sur la page de connexion). Deux périodes ne se chevauchent pas : le formulaire
     * public ne demande pas la campagne.
     *
     * @param  array{campagne_id: string, date_debut: string, date_fin: string}  $donnees
     */
    public function enregistrerPeriode(array $donnees, ?ReclamationPeriode $periode, ?User $admin): ReclamationPeriode
    {
        $finCampagne = Campagne::withTrashed()->find($donnees['campagne_id'])?->periode_fin;
        if (! $finCampagne) {
            $this->invalide('campagne_id', 'Cette campagne n’a pas de date de fin : renseignez-la avant d’ouvrir une période de réclamation.');
        }
        if ($donnees['date_debut'] <= $finCampagne->toDateString()) {
            $this->invalide('date_debut', 'La réclamation doit commencer après la fin de la campagne ('.$finCampagne->format('d/m/Y').').');
        }
        $occupee = ReclamationPeriode::query()
            ->when($periode, fn ($query) => $query->whereKeyNot($periode->getKey()))
            ->whereHas('campagne')
            ->whereDate('date_debut', '<=', $donnees['date_fin'])
            ->whereDate('date_fin', '>=', $donnees['date_debut'])
            ->first();
        if ($occupee) {
            $this->invalide('date_debut', sprintf(
                'Une autre période de réclamation est prévue du %s au %s : choisissez des dates qui ne la chevauchent pas.',
                $occupee->date_debut->format('d/m/Y'), $occupee->date_fin->format('d/m/Y'),
            ));
        }
        if ($periode && (string) $periode->campagne_id !== $donnees['campagne_id'] && $periode->reclamations()->exists()) {
            $this->invalide('campagne_id', 'Des réclamations ont déjà été déposées : la campagne de cette période ne peut plus être changée.');
        }

        $periode ??= new ReclamationPeriode(['created_by' => $admin?->id_user]);
        $periode->fill($donnees + ['updated_by' => $admin?->id_user])->save();

        return $periode;
    }

    /** Nouvelle date de fin d'une campagne : elle reste antérieure au début de ses périodes de réclamation. */
    public function verifierFinCampagne(string $campagneId, string $finCampagne): void
    {
        $debut = $this->disponible() ? ReclamationPeriode::where('campagne_id', $campagneId)->orderBy('date_debut')->first()?->date_debut : null;
        if ($debut && Carbon::parse($finCampagne)->toDateString() >= $debut->toDateString()) {
            $this->invalide('periode_fin', 'Une période de réclamation de cette campagne commence le '.$debut->format('d/m/Y').' : la campagne doit se terminer avant.');
        }
    }

    public function supprimerPeriode(ReclamationPeriode $periode): void
    {
        if ($periode->reclamations()->exists()) {
            $this->invalide('periode', 'Des réclamations ont été déposées pendant cette période : elle ne peut pas être supprimée. Avancez sa date de fin pour la fermer.');
        }
        $periode->delete();
    }

    /** Réclamation telle qu'affichée (liste et fiche de la demande) ; le lien de traitement suit les droits de l'agent. */
    public function presenter(Reclamation $reclamation, ?User $admin): array
    {
        return [
            'id' => (string) $reclamation->getKey(),
            'numero' => $reclamation->numero,
            'nom_prenoms' => $reclamation->nom_prenoms,
            'num_bac_matricule' => $reclamation->num_bac_matricule,
            'type_demande' => $reclamation->type_demande,
            'campagne' => collect([$reclamation->campagne?->libelle, $reclamation->campagne?->annee_campagne])->filter()->implode(' · ') ?: null,
            'motif' => $reclamation->motif,
            'statut' => $reclamation->statut,
            'date' => $reclamation->created_at?->format('Y-m-d H:i'),
            'traite_par' => $reclamation->traite_par_nom,
            'traite_at' => $reclamation->traite_at?->format('Y-m-d H:i'),
            'url_traiter' => app(AdminAccess::class)->allows($admin, 'admin.hebergement.reclamations.traiter')
                ? route('admin.hebergement.reclamations.traiter', ['reclamation' => $reclamation->getKey()]) : null,
        ];
    }

    /**
     * Demande de l'étudiant pour la campagne. Le numéro saisi doit être l'un de ceux de la fiche (numéro de table de la
     * demande ou matricule) ; une fiche sans aucun numéro connu est rattachée par le nom seul, s'il est sans homonyme.
     */
    private function demandeDe(ReclamationPeriode $periode, string $nom, string $numero): DemandeEtudiant
    {
        $cle = $this->cleNom($nom);
        $demandes = DemandeEtudiant::query()
            ->where('campagne_id', $periode->campagne_id)
            ->where('etat_dossier', '!=', 'brouillon')
            ->orderByDesc('created_at')
            ->get();
        $etudiants = Etudiant::query()->whereIn('id_etudiant', $demandes->pluck('etudiant_id')->unique()->values())->get(['id_etudiant', 'name', 'matricule'])
            ->filter(fn (Etudiant $etudiant) => $cle !== '' && $this->cleNom((string) $etudiant->name) === $cle)
            ->keyBy(fn (Etudiant $etudiant) => (string) $etudiant->getKey());
        $candidates = $demandes->filter(fn (DemandeEtudiant $demande) => $etudiants->has((string) $demande->etudiant_id));
        if ($candidates->isEmpty()) {
            $this->invalide('nom_prenoms', 'Aucune demande de cette campagne ne correspond à ce nom. Saisissez-le comme dans votre demande.');
        }

        $saisi = $this->cleNumero($numero);
        $numeros = fn (DemandeEtudiant $demande) => array_filter([
            $this->cleNumero($demande->num_table_bac), $this->cleNumero($etudiants[(string) $demande->etudiant_id]->matricule),
        ], fn (string $connu) => $connu !== '');
        $retenues = $candidates->filter(fn (DemandeEtudiant $demande) => in_array($saisi, $numeros($demande), true));
        if ($retenues->isEmpty()) {
            $retenues = $candidates->filter(fn (DemandeEtudiant $demande) => $numeros($demande) === []);
        }
        if ($retenues->isEmpty()) {
            $this->invalide('num_bac_matricule', 'Ce numéro ne correspond pas à votre demande : saisissez le numéro de table au bac ou le matricule indiqué dans votre demande.');
        }
        // Homonymes non départagés : le rattachement serait arbitraire.
        if ($retenues->unique('etudiant_id')->count() > 1) {
            $this->invalide('nom_prenoms', 'Plusieurs demandes correspondent à ces informations : adressez-vous au service Logement du CROU.');
        }

        return $retenues->first();
    }

    /** Mots du nom sans accents ni casse, triés : « Kouassi Ange » et « ANGE KOUASSI » désignent le même étudiant. */
    private function cleNom(string $nom): string
    {
        $mots = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($nom)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        sort($mots);

        return implode(' ', $mots);
    }

    /** Numéro de table ou matricule sans casse, espaces ni ponctuation. */
    private function cleNumero(?string $numero): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', Str::lower(Str::ascii((string) $numero)));
    }

    private function invalide(string $champ, string $message): never
    {
        throw ValidationException::withMessages([$champ => $message]);
    }
}
