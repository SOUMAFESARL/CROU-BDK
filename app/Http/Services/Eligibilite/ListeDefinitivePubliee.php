<?php

namespace App\Http\Services\Eligibilite;

use App\Models\Etudiant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Étudiants de la liste définitive publiée (onglet « Demandes validées »), pas encore logés : « Nouveau paiement » les
 * propose avant l'attribution de leur chambre (paiements hors loyer).
 *
 * Un étudiant y figure quand sa dernière demande :
 * - est « Recevable » (`eligible`) ou confirmée par l'étudiant (`confirme`, « Admis à loger ») ;
 * - a été approuvée en commission depuis les « Demandes validées » (`liste_approbation_commission` = `validees` ou vide —
 *   la file d'attente n'en fait pas partie), approbation encore valable (`RecevabiliteDemandes::approbationsCommission`) ;
 * - appartient à un volet (admission / réadmission) dont la liste a été publiée après cette approbation
 *   (`publication_liste_definitive`, même règle que l'écran « Listes définitives ») ;
 * et que l'étudiant n'a pas d'affectation confirmée.
 */
class ListeDefinitivePubliee
{
    /** États du dossier d'un étudiant de la liste définitive : « Recevable », puis « Confirmé » (admis à loger). */
    public const ETATS = ['eligible', 'confirme'];

    /** Liste publiée (`publication_liste_definitive.liste`) : les demandes validées, pas la file d'attente. */
    public const LISTE = 'validees';

    public function __construct(private RecevabiliteDemandes $recevabilite, private CriteresCampagne $criteres) {}

    /**
     * Limite une requête d'étudiants aux candidats (filtre SQL) : sans affectation confirmée, dernière demande approuvée
     * depuis les « Demandes validées », dossier « Recevable » ou « Confirmé ». `publies()` fait le contrôle complet.
     */
    public function candidats(Builder $query): Builder
    {
        return $query->whereDoesntHave('assignationActive')
            ->whereHas('derniereDemande', fn ($demande) => $demande->whereIn('etat_dossier', self::ETATS)
                ->whereNotNull('date_approbation_commission')
                ->where(fn ($q) => $q->whereNull('liste_approbation_commission')->orWhere('liste_approbation_commission', '!=', 'attente')));
    }

    /**
     * Identifiants des étudiants (avec `derniereDemande.campagne` chargée) qui figurent sur une liste définitive publiée.
     *
     * @param  iterable<Etudiant>  $etudiants
     * @return list<string>
     */
    public function publies(iterable $etudiants): array
    {
        $etudiants = collect($etudiants)->filter(function (Etudiant $etudiant) {
            $demande = $etudiant->derniereDemande;

            return $demande && $demande->campagne_id && in_array($demande->etat_dossier, self::ETATS, true)
                && ($demande->liste_approbation_commission ?? null) !== 'attente';
        });
        if ($etudiants->isEmpty() || ! Schema::hasTable('publication_liste_definitive')) {
            return [];
        }

        $approuvees = $this->recevabilite->approbationsCommission($etudiants->map(fn (Etudiant $etudiant) => $etudiant->derniereDemande), self::ETATS);
        // Date de publication par campagne et par volet.
        $publications = DB::table('publication_liste_definitive')
            ->where('liste', self::LISTE)
            ->whereIn('campagne_id', $etudiants->map(fn (Etudiant $etudiant) => (string) $etudiant->derniereDemande->campagne_id)->unique()->values()->all())
            ->get(['campagne_id', 'volet', 'date_publication'])
            ->groupBy('campagne_id')
            ->map(fn ($lignes) => $lignes->pluck('date_publication', 'volet'));

        return $etudiants->filter(function (Etudiant $etudiant) use ($approuvees, $publications) {
            $demande = $etudiant->derniereDemande;
            if (! in_array((string) $demande->getKey(), $approuvees, true)) {
                return false;
            }
            $volet = $this->criteres->voletEtudiant($demande->campagne, (string) $etudiant->getKey());
            $publiee = $publications->get((string) $demande->campagne_id)?->get($volet);

            return $publiee && Carbon::parse($publiee)->gte(Carbon::parse($demande->date_approbation_commission));
        })->map(fn (Etudiant $etudiant) => (string) $etudiant->getKey())->values()->all();
    }

    /** L'étudiant figure-t-il sur une liste définitive publiée, sans affectation confirmée ? */
    public function figure(string $etudiantId): bool
    {
        $etudiant = $this->candidats(Etudiant::query())->with('derniereDemande.campagne')->find($etudiantId);

        return $etudiant !== null && $this->publies([$etudiant]) !== [];
    }
}
