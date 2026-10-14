<?php
namespace App\Http\Services;
use App\Models\{Etudiant,Campagne,DemandeEtudiant,AssignationChambre,DepartureRequest};
use App\Repositories\DemandeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReadmissionService
{
    public function ensureCanApply(Etudiant $student, Campagne $campaign): void
    {
        if ($campaign->statut!=='active') $this->invalid('Aucune demande ne peut être déposée pour cette campagne inactive.');
        if ($campaign->date_echeance_demande && \Carbon\Carbon::parse($campaign->date_echeance_demande)->endOfDay()->isPast())
            $this->invalid('La date limite de dépôt des demandes est dépassée.');
        if ($campaign->readmission !== null && !(bool)$campaign->readmission)
            $this->invalid('Cette campagne n’autorise pas les réadmissions.');
        app(DisciplinaryImpactService::class)->verifier($student);
        if (AssignationChambre::where('etudiant_id',$student->getKey())->mobilisantPlace()->exists())
            $this->invalid('Un séjour ou une réservation est encore en cours. Terminez la sortie avant une nouvelle demande.');
        if (DemandeEtudiant::where('etudiant_id',$student->getKey())->where('campagne_id',$campaign->getKey())
            ->whereNotIn('etat_dossier',['cloture','refuse','annule','expire'])->exists())
            $this->invalid('Un dossier existe déjà pour cette campagne. Suivez-le depuis vos demandes.');
    }
    public function submit(Etudiant $student, array $data): DemandeEtudiant
    {
        return DB::transaction(function()use($student,$data){
            $student=Etudiant::whereKey($student->getKey())->lockForUpdate()->firstOrFail();
            $campaign=Campagne::whereKey($data['campagne_id'])->lockForUpdate()->firstOrFail();
            if ($student->statut_inscription!=='archivee' && !DepartureRequest::where('etudiant_id',$student->getKey())->where('statut','cloturee')->exists())
                $this->invalid('La réadmission concerne les étudiants dont le séjour précédent est clôturé.');
            $this->ensureCanApply($student,$campaign);
            $dossier=app(DemandeRepository::class)->create(array_merge($data,[
                'id_etudiant'=>$student->getKey(),'id_campagne'=>$campaign->getKey(),
                'annee_academique_actuel'=>$campaign->annee_campagne,
                'moyenne'=>$data['moyenne']??null,'credit_valider'=>$data['credit_valider']??null,
            ]));
            $student->update(['statut_inscription'=>'demande']);
            return $dossier;
        },3);
    }
    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['campagne_id'=>$message]);
    }
}
