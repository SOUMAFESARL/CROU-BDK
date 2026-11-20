<?php
namespace App\Http\Services;

use App\Models\{AdmissionDerogation, Campagne, DemandeEtudiant, Etudiant, User};
use App\Http\Services\Eligibilite\{ConformiteDemande, CriteresCampagne, RecevabiliteDemandes, VerrouListeProvisoire};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdmissionDerogationService
{
    public function canApprove(?User $user): bool
    {
        // Pas de contournement implicite pour les administrateurs génériques.
        $role = $user?->role;
        return $role && in_array(Str::lower(Str::ascii(trim($role->name))), [
            'sous-dal', 'sous dal', "sous-directeur de l'accueil et des logements",
            'chef du service logement', 'chef service logement',
        ], true) && $role->permissions()->where('slug', 'hebergement.admissions.derogations.validate')->exists();
    }

    private function reason(string $reason): string
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['reason' => 'Un motif de 1 à 2 000 caractères est obligatoire.']);
        }
        return $reason;
    }

    private function dossier(string $studentId, string $requestId): array
    {
        $student = Etudiant::whereKey($studentId)->lockForUpdate()->firstOrFail();
        app(DisciplinaryImpactService::class)->verifier($student);
        $request = DemandeEtudiant::where('etudiant_id', $studentId)->whereKey($requestId)->lockForUpdate()->firstOrFail();
        $campaign = Campagne::whereKey($request->campagne_id)->lockForUpdate()->firstOrFail();
        $latest = DemandeEtudiant::where('etudiant_id', $studentId)->latest('created_at')->first();
        if (!$latest || $latest->getKey() !== $requestId || $campaign->statut !== 'active'
            || !in_array($request->etat_dossier, ['soumis', 'en_etude'], true)) {
            throw ValidationException::withMessages(['reason' => 'La dérogation doit concerner le dossier courant, non attribué, d’une campagne active.']);
        }
        return [$student, $request, $campaign];
    }

    /**
     * Constat de recevabilité de la demande : critères de la campagne comparés aux valeurs saisies (`ConformiteDemande`,
     * même calcul que la liste des admissions et le bloc « Critères d’éligibilité » de la fiche, situation disciplinaire
     * comprise). Seules les règles sont conservées avec la dérogation, pour vérifier qu'elles n'ont pas changé à la décision.
     */
    private function evaluate(Etudiant $student, DemandeEtudiant $request, Campagne $campaign): array
    {
        $request->setRelation('campagne', $campaign);
        $student->setRelation('derniereDemande', $request);
        $evaluation = app(ConformiteDemande::class)->evaluer($student, $request, true);

        return ['volet' => $evaluation['volet'] ?? null, 'regles' => array_values($evaluation['regles'] ?? [])];
    }

    /** États du dossier depuis lesquels une demande peut être dérogée (un refus est d'abord rouvert en « Soumis »). */
    public const ETATS_DEROGEABLES = ['soumis', 'en_etude', 'refuse'];

    public const MESSAGE_CLOTURE = 'La liste des demandes validées a déjà été approuvée en commission : aucune dérogation n’est plus possible.';

    /**
     * Obstacle à la dérogation de la dernière demande de l'étudiant (bouton « Déroger » de la fiche du dossier), null si
     * elle est possible : demande d'une campagne active classée « Demandes irrecevables » ou « Demandes refusées »
     * (`RecevabiliteDemandes`), dossier « Soumis », « En étude » ou « Refusé », et liste provisoire de son volet pas
     * encore clôturée (liste des demandes validées approuvée en commission, `VerrouListeProvisoire`).
     */
    public function obstacle(Etudiant $student): ?string
    {
        $request = $student->derniereDemande;
        $campaign = $request?->campagne;
        if (! $request || ! $campaign || $campaign->statut !== 'active' || ! in_array($request->etat_dossier, self::ETATS_DEROGEABLES, true)) {
            return 'La dérogation ne concerne que la demande courante, non validée, d’une campagne active.';
        }
        $classe = app(RecevabiliteDemandes::class)->classe($student);
        if (! in_array($classe, [RecevabiliteDemandes::REJETEE, RecevabiliteDemandes::REFUSEE], true)) {
            return 'Seule une demande irrecevable ou refusée peut être dérogée.';
        }
        $volet = app(CriteresCampagne::class)->voletsDemandes([$request])[(string) $student->getKey()] ?? null;
        if (in_array($volet, app(VerrouListeProvisoire::class)->voletsClotures((string) $campaign->getKey()), true)) {
            return self::MESSAGE_CLOTURE;
        }

        return null;
    }

    /**
     * Dérogation (bouton « Déroger » de la fiche du dossier, motif obligatoire) : la demande irrecevable ou refusée est
     * rendue recevable sur-le-champ (dérogation enregistrée approuvée, avec le constat de recevabilité) et passe en tête
     * de la liste provisoire de son volet avec le badge « Dérogé ». Un dossier refusé revient d'abord en « Soumis »
     * (`DossierWorkflow`, motif conservé dans l'historique). Aucune chambre n'est réservée.
     */
    public function deroger(User $actor, string $studentId, string $reason): AdmissionDerogation
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['motif' => 'Le motif de la dérogation est obligatoire (2 000 caractères maximum).']);
        }

        return DB::transaction(function () use ($actor, $studentId, $reason) {
            $student = Etudiant::whereKey($studentId)->lockForUpdate()->firstOrFail();
            app(DisciplinaryImpactService::class)->verifier($student);
            $request = DemandeEtudiant::with('campagne')->where('etudiant_id', $studentId)->latest('created_at')->lockForUpdate()->first();
            $student->setRelation('derniereDemande', $request);
            if ($obstacle = $this->obstacle($student)) {
                throw ValidationException::withMessages(['motif' => $obstacle]);
            }
            $eligibility = $this->evaluate($student, $request, $request->campagne);
            if ($request->etat_dossier === 'refuse') {
                app(DossierWorkflow::class)->admin($request->getKey(), 'soumis', 'Dérogation : '.$reason);
            }

            return AdmissionDerogation::create([
                'id' => (string) Str::uuid(), 'etudiant_id' => $studentId, 'demande_id' => $request->getKey(),
                'campagne_id' => $request->campagne_id, 'requested_by' => $actor->getKey(),
                'requester_name' => $actor->name ?? $actor->getKey(), 'reason' => $reason,
                'eligibility' => $eligibility, 'status' => 'approved',
                'decided_by' => $actor->getKey(), 'decider_name' => $actor->name ?? $actor->getKey(),
                'decision_reason' => $reason, 'decided_at' => now(),
            ]);
        }, 3);
    }

    public function request(User $actor, string $studentId, string $requestId, string $reason): AdmissionDerogation
    {
        $reason = $this->reason($reason);
        return DB::transaction(function () use ($actor, $studentId, $requestId, $reason) {
            [$student, $request, $campaign] = $this->dossier($studentId, $requestId);
            if (AdmissionDerogation::where('demande_id', $requestId)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['reason' => 'Une dérogation attend déjà une décision.']);
            }
            // Seule une demande irrecevable (au moins un critère non conforme) peut être rendue recevable par dérogation.
            $eligibility = $this->evaluate($student, $request, $campaign);
            if (!collect($eligibility['regles'])->contains('etat', 'nok')) {
                throw ValidationException::withMessages(['reason' => 'La dérogation ne concerne qu’une demande irrecevable (au moins un critère non conforme).']);
            }
            return AdmissionDerogation::create([
                'id' => (string) Str::uuid(), 'etudiant_id' => $studentId, 'demande_id' => $requestId,
                'campagne_id' => $campaign->getKey(), 'requested_by' => $actor->getKey(),
                'requester_name' => $actor->name ?? $actor->getKey(), 'reason' => $reason,
                'eligibility' => $eligibility, 'status' => 'pending',
            ]);
        }, 3);
    }

    public function decide(User $actor, string $id, bool $approve, string $reason): void
    {
        abort_unless($this->canApprove($actor), 403);
        $reason = $this->reason($reason);
        DB::transaction(function () use ($actor, $id, $approve, $reason) {
            $initial = AdmissionDerogation::findOrFail($id);
            Etudiant::whereKey($initial->etudiant_id)->lockForUpdate()->firstOrFail();
            $exception = AdmissionDerogation::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($exception->status !== 'pending' || (string) $exception->requested_by === (string) $actor->getKey()) {
                throw ValidationException::withMessages(['reason' => 'Seul un autre responsable peut décider sur une dérogation en attente.']);
            }
            if ($approve) {
                [$student, $request, $campaign] = $this->dossier($exception->etudiant_id, $exception->demande_id);
                if ($this->evaluate($student, $request, $campaign)['regles'] != ($exception->eligibility['regles'] ?? null)) {
                    throw ValidationException::withMessages(['reason' => 'Les critères ont changé : refusez cette demande puis faites établir une nouvelle dérogation.']);
                }
                $student->update(['statut_inscription' => 'en_attente', 'is_rejet' => 0]);
                // L'approbation autorise la transition, mais ne simule ni proposition ni acceptation.
                $exception->update(['status'=>'approved','decided_at'=>now(),'decided_by'=>$actor->getKey()]);
                app(DossierWorkflow::class)->admin($request->getKey(),'eligible','Recevabilité par dérogation validée.');
            }
            $exception->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $actor->getKey(),
                'decider_name' => $actor->name ?? $actor->getKey(), 'decision_reason' => $reason, 'decided_at' => now()]);
        }, 3);
    }
}
