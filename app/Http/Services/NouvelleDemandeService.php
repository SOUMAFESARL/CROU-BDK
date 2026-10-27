<?php

namespace App\Http\Services;

use App\Models\AssignationChambre;
use App\Models\Campagne;
use App\Models\DemandeEtudiant;
use App\Models\DepartureRequest;
use App\Models\Etudiant;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Nouvelle demande d'hébergement d'un étudiant qui a déjà un compte mais n'a jamais été logé,
 * déposée par le formulaire public (/register, proposé sur la page de connexion pendant une campagne ouverte) :
 * une seule demande par campagne, rattachée au compte existant.
 * Un étudiant déjà résident (logé actuellement ou par le passé) passe par la réadmission (ReadmissionService).
 */
class NouvelleDemandeService
{
    public const MESSAGE_RESIDENT = 'Vous avez déjà été logé(e) par le CROU : connectez-vous à votre espace étudiant pour déposer une demande de réadmission.';

    /** L'étudiant est ou a été logé par le CROU : séjour attribué, clôturé ou chambre occupée. */
    public function estDejaResident(Etudiant $etudiant): bool
    {
        $id = (string) $etudiant->getKey();

        return in_array(strtolower((string) $etudiant->statut_inscription), ['residents', 'archivee'], true)
            || DemandeEtudiant::where('etudiant_id', $id)->whereIn('etat_dossier', ['attribue_definitif', 'cloture'])->exists()
            || AssignationChambre::where('etudiant_id', $id)->where('statut', 'confirmee')->exists()
            || DepartureRequest::where('etudiant_id', $id)->where('statut', 'cloturee')->exists();
    }

    /**
     * Compte existant reconnu depuis le formulaire public : l'e-mail et le matricule saisis doivent correspondre à la même fiche.
     * Fiche sans e-mail (créée par l'administration) : reconnue par le matricule et le téléphone enregistré, à condition que
     * l'e-mail saisi n'appartienne à aucun autre compte ; il est ensuite ajouté à la fiche (frontedController::postRegister).
     * Sinon null, et les contrôles d'unicité habituels s'appliquent.
     */
    public function compteExistant(?string $email, ?string $matricule, ?string $telephone = null): ?Etudiant
    {
        $email = trim((string) $email);
        $matricule = trim((string) $matricule);
        if ($email === '' || $matricule === '') {
            return null;
        }

        $parMatricule = Etudiant::whereRaw('UPPER(TRIM(matricule)) = ?', [mb_strtoupper($matricule)])->limit(2)->get();
        if ($parMatricule->count() !== 1) {
            return null; // Inconnu, ou doublon à faire vérifier par l'administration.
        }
        $fiche = $parMatricule->first();
        if (filled($fiche->email1)) {
            return strcasecmp(trim((string) $fiche->email1), $email) === 0 ? $fiche : null;
        }

        if (Etudiant::withTrashed()->whereRaw('LOWER(email1) = ?', [mb_strtolower($email)])->exists()) {
            return null;
        }
        try {
            $parTelephone = trim((string) $telephone) !== '' ? app(StudentIdentityResolver::class)->find($telephone, null) : null;
        } catch (ValidationException) {
            return null;
        }

        return $parTelephone?->getKey() === $fiche->getKey() ? $fiche : null;
    }

    /** Règles de dépôt sur le compte existant ; ValidationException (clé `campagne_id`) si la demande est impossible. */
    public function ensureCanApply(Etudiant $etudiant, Campagne $campagne): void
    {
        if ($this->estDejaResident($etudiant)) {
            $this->invalid(self::MESSAGE_RESIDENT);
        }
        if ($campagne->statut !== 'active') {
            $this->invalid('Aucune demande ne peut être déposée pour cette campagne inactive.');
        }
        if ($campagne->date_echeance_demande && Carbon::parse($campagne->date_echeance_demande)->endOfDay()->isPast()) {
            $this->invalid('La date limite de dépôt des demandes est dépassée.');
        }
        app(DisciplinaryImpactService::class)->verifier($etudiant);
        if (AssignationChambre::where('etudiant_id', $etudiant->getKey())->mobilisantPlace()->exists()) {
            $this->invalid('Une chambre vous est déjà proposée ou réservée. Répondez à cette proposition depuis votre espace étudiant.');
        }
        // Une seule demande par étudiant et par campagne, quel que soit son état (refusée, annulée… comprises).
        if (DemandeEtudiant::where('etudiant_id', $etudiant->getKey())->where('campagne_id', $campagne->getKey())->exists()) {
            $this->invalid('Vous avez déjà déposé une demande pour cette campagne. Suivez-la depuis votre espace étudiant.');
        }
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['campagne_id' => $message]);
    }
}
