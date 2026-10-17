<?php
namespace App\Http\Services;

use App\Models\{AdmissionDerogation,AssignationChambre,Campagne,DemandeEtudiant,Etudiant};
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\ValidationException;

class DossierWorkflow
{
    public const LABELS = [
        'brouillon'=>'Brouillon','soumis'=>'Soumis','incomplet'=>'Incomplet','en_etude'=>'En étude',
        'eligible'=>'Éligible','pre_attribue'=>'Pré-attribué','accepte'=>'Accepté par l’étudiant',
        'attribue_definitif'=>'Attribué définitif','refuse'=>'Refusé','annule'=>'Annulé',
        'expire'=>'Expiré','cloture'=>'Clôturé',
    ];
    public const NEXT = [
        'brouillon'=>['soumis','annule'], 'soumis'=>['incomplet','en_etude','refuse','annule','expire'],
        'incomplet'=>['soumis','annule','expire'], 'en_etude'=>['incomplet','eligible','refuse','annule','expire'],
        'eligible'=>['pre_attribue','en_etude','refuse','annule','expire'],
        'pre_attribue'=>['accepte','refuse','eligible','annule','expire'],
        'accepte'=>['attribue_definitif','eligible','annule','expire'],
        'attribue_definitif'=>['cloture'], 'refuse'=>['en_etude'], 'annule'=>[], 'expire'=>[], 'cloture'=>[],
    ];
    public function current(string $studentId, string $campaignId): DemandeEtudiant
    {
        return DemandeEtudiant::where('etudiant_id',$studentId)->where('campagne_id',$campaignId)
            ->latest('created_at')->orderByDesc('id_demande')->firstOrFail();
    }
    public function eligible(DemandeEtudiant $request): array
    {
        $student = Etudiant::findOrFail($request->etudiant_id);
        app(DisciplinaryImpactService::class)->verifier($student);
        $student = app(EtudiantRepositoryInterface::class)->findWithRelations($student->getKey());
        $student->setRelation('derniereDemande',$request->load('niveauEtudiantActuel'));
        $campaign = Campagne::findOrFail($request->campagne_id);
        $evaluation = app(EtudiantRepositoryInterface::class)->verifierEligibilite($student,[
            'success'=>true,'data'=>['campagnes'=>collect([$campaign])],
        ]);
        if (($evaluation['eligible'] ?? false) !== true) {
            $exception = AdmissionDerogation::where('demande_id',$request->getKey())->where('status','approved')->latest('decided_at')->first();
            if (!$exception || $exception->eligibility != $evaluation) {
                throw ValidationException::withMessages(['etat_dossier'=>'Le dossier n’est pas éligible. Corrigez les critères ou faites valider une dérogation motivée.']);
            }
            $evaluation['derogation_id']=$exception->getKey();
        }
        return $evaluation;
    }
    public function transition(string $id, string $to, string $kind, ?string $actorId, ?string $reason = null, array $details = []): DemandeEtudiant
    {
        return DB::transaction(function() use($id,$to,$kind,$actorId,$reason,$details) {
            $initial=DemandeEtudiant::findOrFail($id);
            $student=Etudiant::whereKey($initial->etudiant_id)->lockForUpdate()->firstOrFail();
            $request=DemandeEtudiant::whereKey($id)->lockForUpdate()->firstOrFail();
            $from=$request->etat_dossier;
            if (!in_array($to,self::NEXT[$from] ?? [],true)) {
                $this->invalid('Transition interdite : '.(self::LABELS[$from] ?? $from).' → '.(self::LABELS[$to] ?? $to).'. Actualisez le dossier.');
            }
            if ($kind==='etudiant') {
                abort_unless((string)$actorId===(string)$student->getKey(),403);
                if (!in_array($to,['soumis','accepte','refuse','annule'],true)
                    || ($to==='refuse' && $from!=='pre_attribue')
                    || ($to==='annule' && !in_array($from,['brouillon','soumis','incomplet'],true))) abort(403);
            } elseif ($kind==='admin') {
                $actor=Auth::guard('admin')->user();
                abort_unless($actor && (string)$actor->getKey()===(string)$actorId
                    && ($actor->hasPermission('admissions.manage') || $actor->hasPermission('hebergement.admissions.validate')
                        || $actor->hasPermission('hebergement.residents.update')
                        || ($to==='eligible' && app(AdmissionDerogationService::class)->canApprove($actor)
                            && AdmissionDerogation::where('demande_id',$id)->where('status','approved')->where('decided_by',$actorId)->exists())),403);
                if (in_array($to,['accepte','soumis'],true)) abort(403);
            } elseif ($kind==='systeme') {
                if (!in_array($to,['soumis','expire','refuse','annule','cloture'],true)) abort(403);
            } else abort(403);
            if (in_array($to,['incomplet','refuse','annule','expire'],true) || ($from==='refuse')) {
                if (trim((string)$reason)==='') $this->invalid('Le motif de cette transition est obligatoire.');
            }
            if (in_array($to,['soumis','en_etude','eligible','pre_attribue','accepte','attribue_definitif'],true)) {
                $campaign=Campagne::findOrFail($request->campagne_id);
                if ($campaign->statut!=='active') $this->invalid('La campagne n’est plus active.');
                if ($to==='soumis' && $campaign->date_echeance_demande
                    && \Carbon\Carbon::parse($campaign->date_echeance_demande)->endOfDay()->isPast()) {
                    $this->invalid('Le délai de dépôt est dépassé.');
                }
                app(DisciplinaryImpactService::class)->verifier($student);
            }
            if (in_array($to,['eligible','pre_attribue','attribue_definitif'],true) && ($to !== 'eligible' || !in_array($from,['pre_attribue','accepte'],true))) {
                $details['eligibilite']=$this->eligible($request);
            }
            if ($kind === 'etudiant' && in_array($to,['accepte','refuse'],true)
                && (string)($details['assignation_id'] ?? '') !== (string)$request->assignation_id) {
                $this->invalid('La proposition a changé. Consultez la chambre proposée avant de répondre.');
            }
            $assignmentId=$details['assignation_id'] ?? $request->assignation_id;
            if (in_array($to,['pre_attribue','accepte','attribue_definitif'],true)) {
                $assignment=AssignationChambre::whereKey($assignmentId)->where('etudiant_id',$student->getKey())
                    ->where('campagne_id',$request->campagne_id)->lockForUpdate()->first();
                if (!$assignment || !in_array($assignment->statut,['en_attente','confirmee'],true)
                    || ($assignment->date_fin && $assignment->date_fin->lte(now()))) $this->invalid('La proposition de chambre est absente, annulée ou expirée.');
                if ($to==='attribue_definitif' && $assignment->statut!=='confirmee') $this->invalid('La chambre doit être confirmée par le service Logement.');
                if (in_array($to,['pre_attribue','accepte'],true) && $assignment->statut!=='en_attente') $this->invalid('Cette proposition n’est plus en attente.');
            }
            if ((in_array($to,['refuse','annule','expire'],true) || ($to==='eligible' && in_array($from,['pre_attribue','accepte'],true))) && $request->assignation_id) {
                $assignment=AssignationChambre::whereKey($request->assignation_id)->first();
                if ($assignment && $assignment->statut==='confirmee') $this->invalid('Un séjour confirmé doit suivre la procédure de sortie.');
                if ($assignment && $assignment->statut==='en_attente') {
                    app(AllocationChambreService::class)->annulerAssignation($assignment->getKey(),$reason ?: 'Retrait de la proposition avant réexamen.',false);
                }
            }
            if ($to === 'cloture') {
                $assignment = AssignationChambre::whereKey($request->assignation_id)
                    ->where('etudiant_id', $student->getKey())->lockForUpdate()->first();
                $departure = \App\Models\DepartureRequest::where('assignation_id', $request->assignation_id)
                    ->where('etudiant_id', $student->getKey())->where('decision', 'approuvee')
                    ->where('statut', 'cloturee')->with('settlement')->first();
                $settlement = $departure?->settlement;
                $exit = $settlement ? \App\Models\EtatDesLieux::whereKey($settlement->etat_des_lieux_id)
                    ->where('id_etudiant', $student->getKey())->where('id_chambre', $assignment?->chambre_id)
                    ->where('type_etat', 'sortie')->first() : null;
                if (!$assignment || !in_array($assignment->statut, ['annulee','expulsion'], true)
                    || !$assignment->date_fin || !$exit) {
                    $this->invalid('La clôture exige une sortie approuvée, un état des lieux de sortie et un décompte établi selon les paramètres de départ.');
                }
                $details['decompte_id'] = $settlement->getKey();
                $details['etat_des_lieux_id'] = $exit->getKey();
            }
            if ($to === 'accepte') {
                $details['echeance_proposition'] = $assignment->date_fin?->toIso8601String();
                // L'accord reçu dans les délais conserve la réservation jusqu'à la validation finale.
                $assignment->update(['date_fin'=>null]);
            }
            $legacy=match($to) {
                'eligible','pre_attribue','accepte'=>'En_verification',
                'attribue_definitif','cloture'=>'Acceptée',
                'refuse','annule','expire'=>'Refusée', default=>'En_attente',
            };
            $changes=['etat_dossier'=>$to,'statut_demande'=>$legacy,'updated_at'=>now()];
            if ($to==='pre_attribue') $changes['assignation_id']=$assignmentId;
            if ($to==='eligible') $changes['assignation_id']=null;
            DB::table($request->getTable())->where('id_demande',$id)->update($changes);
            $details['auteur_nom'] = $kind === 'admin' ? Auth::guard('admin')->user()?->name
                : ($kind === 'etudiant' ? $student->name : 'Traitement automatique');
            DB::table('dossier_transitions')->insert([
                'demande_id'=>$id,'ancien_etat'=>$from,'nouvel_etat'=>$to,'acteur_type'=>$kind,
                'acteur_id'=>$actorId,'motif'=>$reason,'details'=>json_encode($details,JSON_UNESCAPED_UNICODE),'created_at'=>now(),
            ]);
            $latest=DemandeEtudiant::where('etudiant_id',$student->getKey())->latest('created_at')->orderByDesc('id_demande')->value('id_demande');
            if ($latest===$id) {
                $student->update(['statut_inscription'=>match($to) {
                    'eligible'=>'en_verification','pre_attribue','accepte'=>'en_attente',
                    'attribue_definitif'=>'residents','refuse','annule','expire'=>'annuler',
                    'cloture'=>'archivee',default=>'demande',
                }]);
            }
            return $request->fresh();
        },3);
    }
    public function admin(string $id,string $to,?string $reason=null,array $details=[]): DemandeEtudiant
    {
        return $this->transition($id,$to,'admin',Auth::guard('admin')->id(),$reason,$details);
    }
    public function legacy(string $id,string $status,?string $reason): void
    {
        $request=DemandeEtudiant::findOrFail($id);
        $target=match(mb_strtolower($status)) {
            'refusée','refusee','rejetee'=>'refuse', 'annulee','annuler'=>'annule',
            'acceptée','acceptee','validee'=>'attribue_definitif',
            'en_verification'=>'eligible', 'en_cours'=>'en_etude',
            'en_attente'=>'soumis', default=>null,
        };
        if (!$target) $this->invalid('Statut inconnu.');
        // L'évaluation automatique ne constitue pas une nouvelle soumission.
        if ($target==='soumis' && $request->etat_dossier==='soumis') return;
        $this->transition($id,$target,Auth::guard('admin')->check()?'admin':'systeme',Auth::guard('admin')->id(),$reason);
    }
    public function admissionDirecte(DemandeEtudiant $dossier, \App\Models\Chambre $room, Campagne $campaign, ?int $bed = null): string
    {
        return DB::transaction(function () use ($dossier, $room, $campaign, $bed) {
            $student = Etudiant::whereKey($dossier->etudiant_id)->lockForUpdate()->firstOrFail();
            $dossier = DemandeEtudiant::whereKey($dossier->getKey())->lockForUpdate()->firstOrFail();
            if ($dossier->campagne_id !== $campaign->getKey() || $campaign->statut !== 'active') {
                $this->invalid('La campagne doit être active et correspondre au dossier.');
            }
            if (!in_array($dossier->etat_dossier, ['soumis', 'brouillon'], true)
                || AssignationChambre::where('etudiant_id', $student->getKey())->mobilisantPlace()->exists()) {
                $this->invalid('Cet étudiant possède déjà un dossier traité ou une chambre occupée ou réservée.');
            }
            $rules = app(AttributionRules::class);
            $room = $rules->verrouillerChambre($room->getKey());
            // Étudiant déjà installé : pas d'exigence de statut PMR confirmé.
            $rules->verifier($student, $room, $campaign, null, false);
            $assignment = AssignationChambre::create([
                'etudiant_id' => $student->getKey(), 'chambre_id' => $room->getKey(),
                'campagne_id' => $campaign->getKey(),
                'lit_id' => app(LitService::class)->choisir($room, $bed)->getKey(),
                'statut' => 'confirmee', 'type_assignation' => 'manuelle',
                'date_assignation' => $dossier->date_demande ?? now(), 'date_fin' => null,
            ]);
            DB::table($dossier->getTable())->where('id_demande', $dossier->getKey())->update([
                'etat_dossier' => 'attribue_definitif', 'statut_demande' => 'Acceptée',
                'assignation_id' => $assignment->getKey(), 'updated_at' => now(),
            ]);
            $student->update([
                'statut_inscription' => 'residents', 'is_validation' => true,
                'date_validation' => now(), 'users_validation' => Auth::guard('admin')->id(),
            ]);
            DB::table('dossier_transitions')->insert([
                'demande_id' => $dossier->getKey(), 'ancien_etat' => $dossier->etat_dossier,
                'nouvel_etat' => 'attribue_definitif', 'acteur_type' => 'admin',
                'acteur_id' => Auth::guard('admin')->id(),
                'motif' => 'Régularisation administrative d’un étudiant déjà logé.',
                'details' => json_encode(['admission_directe' => true, 'assignation_id' => $assignment->getKey(),
                    'auteur_nom' => Auth::guard('admin')->user()?->name], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
            $room->actualiserOccupation();
            return 'attribue_definitif';
        }, 3);
    }
    private function invalid(string $message): never {
        throw ValidationException::withMessages(['etat_dossier'=>$message]);
    }
}
