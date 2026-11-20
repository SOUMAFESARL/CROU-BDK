<?php

namespace App\Http\Services\Eligibilite;

use App\Models\DemandeEtudiant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Clôture des listes provisoires : dès que la liste des « Demandes validées » d'un volet (admission ou réadmission)
 * a été approuvée en commission pour une campagne (au moins une demande du volet approuvée depuis cet onglet,
 * `demande.date_approbation_commission`, liste d'origine `validees` ou non renseignée), aucune autre demande de la liste
 * provisoire de ce volet ne peut être validée ni mise en attente : les demandes restantes ne peuvent plus qu'être
 * rejetées.
 */
class VerrouListeProvisoire
{
    public const MESSAGE = 'La liste des demandes validées a déjà été approuvée en commission : aucune autre demande de la liste provisoire ne peut être validée.';

    /** @var array<string, list<string>> volets clôturés par campagne (cache de la requête) */
    private array $volets = [];

    public function __construct(private CriteresCampagne $criteres, private RecevabiliteDemandes $recevabilite) {}

    /**
     * Volets de la campagne dont la liste des demandes validées a été approuvée en commission.
     *
     * @return list<string>
     */
    public function voletsClotures(?string $campagneId): array
    {
        if (! $campagneId || ! Schema::hasColumn('demande', 'date_approbation_commission')) {
            return [];
        }

        return $this->volets[$campagneId] ??= array_values(array_unique($this->criteres->voletsDemandes(
            DemandeEtudiant::with('campagne')
                ->where('campagne_id', $campagneId)
                ->whereNotNull('date_approbation_commission')
                ->when(Schema::hasColumn('demande', 'liste_approbation_commission'), fn ($q) => $q->where(
                    fn ($q) => $q->whereNull('liste_approbation_commission')->orWhere('liste_approbation_commission', 'validees')
                ))
                ->get(),
        )));
    }

    /**
     * La demande figure dans une liste provisoire clôturée : dernière demande de l'étudiant, classée recevable (ni
     * validée, ni en attente), dans un volet dont la liste des demandes validées est approuvée.
     */
    public function bloque(DemandeEtudiant $demande): bool
    {
        $clotures = $this->voletsClotures($demande->campagne_id ? (string) $demande->campagne_id : null);
        if ($clotures === []) {
            return false;
        }
        $etudiant = $demande->etudiant;
        if (! $etudiant || (string) $etudiant->derniereDemande?->getKey() !== (string) $demande->getKey()) {
            return false;
        }
        $etudiant->derniereDemande->loadMissing('campagne');
        if ($this->recevabilite->classe($etudiant) !== RecevabiliteDemandes::RECEVABLE) {
            return false;
        }

        return in_array($this->criteres->voletsDemandes([$etudiant->derniereDemande])[(string) $etudiant->getKey()] ?? null, $clotures, true);
    }

    /** Refuse la validation d'une demande d'une liste provisoire clôturée. */
    public function verifier(DemandeEtudiant $demande): void
    {
        if ($this->bloque($demande)) {
            throw ValidationException::withMessages(['etat' => self::MESSAGE]);
        }
    }
}
