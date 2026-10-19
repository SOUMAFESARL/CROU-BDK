<?php
namespace App\Http\Services;

use App\Models\{AdmissionDerogation, Campagne, DemandeEtudiant, Etudiant, User};
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
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

    private function evaluate(Etudiant $student, Campagne $campaign): array
    {
        $repository = app(EtudiantRepositoryInterface::class);
        return $repository->verifierEligibilite($repository->findWithRelations($student->getKey()), [
            'success' => true, 'data' => ['campagnes' => collect([$campaign])],
        ]);
    }

    public function request(User $actor, string $studentId, string $requestId, string $reason): AdmissionDerogation
    {
        $reason = $this->reason($reason);
        return DB::transaction(function () use ($actor, $studentId, $requestId, $reason) {
            [$student, $request, $campaign] = $this->dossier($studentId, $requestId);
            if (AdmissionDerogation::where('demande_id', $requestId)->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['reason' => 'Une dérogation attend déjà une décision.']);
            }
            $eligibility = $this->evaluate($student, $campaign);
            if (($eligibility['eligible'] ?? null) !== false || !collect($eligibility['regles'] ?? [])->contains(fn ($rule) => ($rule['ok'] ?? null) === false)) {
                throw ValidationException::withMessages(['reason' => 'La dérogation nécessite un constat de critères non respectés.']);
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
                if ($this->evaluate($student, $campaign) != $exception->eligibility) {
                    throw ValidationException::withMessages(['reason' => 'Les critères ont changé : refusez cette demande puis faites établir une nouvelle dérogation.']);
                }
                $student->update(['statut_inscription' => 'en_attente', 'is_rejet' => 0]);
                // L'approbation autorise la transition, mais ne simule ni proposition ni acceptation.
                $exception->update(['status'=>'approved','decided_at'=>now(),'decided_by'=>$actor->getKey()]);
                app(DossierWorkflow::class)->admin($request->getKey(),'eligible','Éligibilité par dérogation validée.');
            }
            $exception->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $actor->getKey(),
                'decider_name' => $actor->name ?? $actor->getKey(), 'decision_reason' => $reason, 'decided_at' => now()]);
        }, 3);
    }
}
