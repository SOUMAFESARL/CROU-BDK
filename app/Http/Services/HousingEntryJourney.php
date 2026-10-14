<?php
namespace App\Http\Services;
use App\Models\{HousingEntrySetting,HousingEntryMessage,HousingEntryAppointment,Campagne,Annonce,DemandeEtudiant,AssignationChambre,Etudiant,StudentContractApproval};
use Illuminate\Support\Facades\{DB,Mail};
use Illuminate\Support\Str;

class HousingEntryJourney
{
    public function prepare(): void
    {
        $options=HousingEntrySetting::current()->values;
        if (empty($options['enabled'])) return;
        app(\App\Repositories\CampagneRepository::class)->getAll();
        Campagne::where('statut','active')->where(fn($q)=>$q->whereNull('date_echeance_demande')->orWhere('date_echeance_demande','>=',today()))
            ->each(function($campaign){
                DB::transaction(function()use($campaign){
                    $campaign=Campagne::whereKey($campaign->getKey())->lockForUpdate()->firstOrFail();
                    if (DB::table('housing_campaign_announcements')->where('campaign_id',$campaign->getKey())->exists()) return;
                    $id=(string)Str::uuid();
                    Annonce::withoutEvents(fn()=>Annonce::create([
                        'id_annonce'=>$id,'titre'=>'Ouverture de la campagne '.$campaign->annee_campagne,
                        'contenu'=>'La campagne de logement '.$campaign->annee_campagne.' est ouverte.'
                            .($campaign->date_echeance_demande ? ' Dépôt des dossiers jusqu’au '.\Carbon\Carbon::parse($campaign->date_echeance_demande)->format('d/m/Y').'.' : '')
                            .' Les étudiants déjà inscrits utilisent leur espace étudiant pour une réadmission.',
                        'statut'=>'publie','audience'=>'all','canaux'=>['flux','email'],'date_publication'=>now(),
                        'date_expiration'=>$campaign->date_echeance_demande ? \Carbon\Carbon::parse($campaign->date_echeance_demande)->endOfDay() : null,
                    ]));
                    DB::table('housing_campaign_announcements')->insert(['campaign_id'=>$campaign->getKey(),'announcement_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
                });
            });
        DemandeEtudiant::whereIn('etat_dossier',['pre_attribue','accepte','attribue_definitif'])
            ->whereNotNull('assignation_id')->with('proposition.chambre.cite','proposition.chambre.batiment','proposition.lit')
            ->chunkById(100,function($requests)use($options){
                foreach($requests as $dossier){
                    $assignment=$dossier->proposition;
                    if (!$assignment || !$assignment->created_at || $assignment->created_at->lt($options['started_at'])) continue;
                    if ($dossier->etat_dossier==='pre_attribue' && $assignment->date_fin?->lte(now())){
                        app(DossierWorkflow::class)->transition($dossier->getKey(),'expire','systeme',null,'Échéance de réponse à la proposition dépassée.');
                        continue;
                    }
                    if ($dossier->etat_dossier==='pre_attribue'){
                        $room=$assignment->chambre;
                        $deadline=$assignment->date_fin?->format('d/m/Y à H:i');
                        $body='Chambre '.($room?->numero_chambre ?? '').', bâtiment '.($room?->batiment?->name ?? '')
                            .', cité '.($room?->cite?->name ?? '').', lit '.($assignment->lit?->numero ?? 'à préciser')
                            .'. Loyer mensuel : '.number_format((float)($room?->loyer ?? 0),0,',',' ').' FCFA.'
                            .($deadline ? ' Répondez avant le '.$deadline.' ('.config('app.timezone').').' : '')
                            .' Acceptez ou refusez depuis votre espace étudiant : '.route('etudiants.dashboard',['section'=>'demandes']);
                        $this->message($assignment,'proposal','Votre proposition de chambre',$body,['deadline'=>$assignment->date_fin?->toIso8601String()]);
                    }
                    if ($dossier->etat_dossier==='attribue_definitif' && $assignment->estActive()){
                        if (!StudentContractApproval::where('assignment_id',$assignment->getKey())->exists())
                            app(StudentContractApprovalService::class)->current(Etudiant::findOrFail($assignment->etudiant_id));
                        $this->message($assignment,'contract','Votre attribution définitive et votre contrat',
                            'Votre attribution est validée. Consultez et approuvez votre contrat : '.route('etudiants.contract.approval')
                            .'. Votre rendez-vous d’entrée sera proposé après approbation, selon les créneaux du service Logement.');
                        app(EntryAppointmentService::class)->schedule($assignment->getKey());
                    }
                }
            },'id_demande');
        HousingEntryAppointment::where('status','scheduled')->each(function($appointment){
            if (!AssignationChambre::whereKey($appointment->assignment_id)->actives()->exists())
                $appointment->update(['status'=>'cancelled']);
        });
    }
    private function message(AssignationChambre $assignment,string $kind,string $subject,string $body,array $details=[]): void
    {
        HousingEntryMessage::firstOrCreate(['event_key'=>$kind.':'.$assignment->getKey()],[
            'student_id'=>$assignment->etudiant_id,'assignment_id'=>$assignment->getKey(),
            'kind'=>$kind,'subject'=>$subject,'body'=>$body,'details'=>$details,
        ]);
    }
    public function sendPending(int $limit=50): void
    {
        if (empty(HousingEntrySetting::current()->values['enabled'])) return;
        foreach(HousingEntryMessage::where('status','pending')->orderBy('id')->limit($limit)->get() as $message){
            if (!HousingEntryMessage::whereKey($message->id)->where('status','pending')->update(['status'=>'sending'])) continue;
            $assignment=AssignationChambre::find($message->assignment_id);
            $valid=$message->kind==='proposal'
                ? ($assignment?->statut==='en_attente' && (!$assignment->date_fin || $assignment->date_fin->isFuture())
                    && DemandeEtudiant::where('assignation_id',$assignment->getKey())->where('etat_dossier','pre_attribue')->exists())
                : ($assignment && $assignment->estActive());
            if (!$valid) {$message->update(['status'=>'cancelled']);continue;}
            try {
                $student=Etudiant::findOrFail($message->student_id);
                $email=collect([$student->email1,$student->email2])->first(fn($email)=>filter_var($email,FILTER_VALIDATE_EMAIL));
                if (!$email) throw new \RuntimeException('Adresse e-mail absente.');
                app(\App\Services\AnnonceDiffusionService::class)->configureMail();
                Mail::to($email)->send(new \App\Mail\AnnonceMail($message->subject,$message->body));
                $message->update(['status'=>'sent','sent_at'=>now(),'error'=>null]);
            } catch(\Throwable $exception) {
                $message->update(['status'=>'failed','error'=>'Envoi non confirmé. Vérifiez l’adresse et le paramétrage e-mail.']);
            }
        }
    }
}
