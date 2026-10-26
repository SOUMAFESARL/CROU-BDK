<?php
namespace App\Http\Controllers\admin\immobilier\parametrage\scoring_campagne;
use App\Http\Controllers\Controller;
use App\Http\Services\CampaignScoringService;
use App\Models\{Campagne, CampaignScoring, User};
use App\Services\{AdminAccess, ParametrageHebergement};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ScoringCampagneController extends Controller
{
    public function index(Request $request) {
        $campagnes=Campagne::orderByDesc('created_at')->get();
        $selected=$request->query('campagne_id',$campagnes->first()?->getKey());
        $campagne=$campagnes->first(fn($c)=>(string)$c->getKey()===(string)$selected);
        abort_if($selected && !$campagne,404);
        $history=$campagne?CampaignScoring::where('campagne_id',$campagne->getKey())->latest('id')->limit(10)->get():collect();
        $scoring=$history->first();
        $access=app(AdminAccess::class);
        $ids=$history->pluck('created_by')->filter()->unique();
        $auteurs=$ids->isEmpty() ? collect() : User::whereIn('id_user',$ids)->pluck('name','id_user');
        return Inertia::render('Admin/Hebergement/Parametrage/Scoring',[
            'campagnes'=>app(ParametrageHebergement::class)->options($campagnes),
            'campagneId'=>$campagne ? (string)$campagne->getKey() : null,
            // Dernière version enregistrée ; sans version, la page propose les valeurs d'exemple de l'ancien formulaire.
            'scoring'=>$scoring ? [
                'weights'=>$scoring->weights,'points'=>$scoring->points,'minimum_score'=>(float)$scoring->minimum_score,
                'distance_thresholds'=>CampaignScoringService::distanceThresholds($scoring),
                'pmr_first'=>(bool)$scoring->pmr_first,'active'=>(bool)$scoring->active,
            ] : null,
            'history'=>$history->map(fn(CampaignScoring $version)=>[
                'id'=>$version->id,'date'=>$version->created_at?->format('d/m/Y H:i'),'active'=>(bool)$version->active,
                'minimum_score'=>(float)$version->minimum_score,'auteur'=>$auteurs[$version->created_by] ?? $version->created_by,
            ])->values(),
            'groups'=>CampaignScoringService::GROUPS,'fields'=>CampaignScoringService::FIELDS,
            'distanceDefaults'=>CampaignScoringService::DISTANCE_THRESHOLDS,
            'urls'=>[
                'index'=>route('admin.hebergement.parametrage-herb.scoring-campagne.index'),
                'parametrage'=>route('admin.hebergement.parametrage-herb.index'),
                'store'=>$access->allows($request->user('admin'),'admin.hebergement.parametrage-herb.scoring-campagne.store')
                    ? route('admin.hebergement.parametrage-herb.scoring-campagne.store') : null,
            ],
        ]);
    }
    public function store(Request $request) {
        abort_unless(auth('admin')->user()?->hasPermission('hebergement.parametrage.configure'),403);
        $rules=['campagne_id'=>'required|string','active'=>'required|boolean','pmr_first'=>'required|boolean',
            'minimum_score'=>'required|numeric|between:0,100','weights'=>'required|array','points'=>'required|array'];
        foreach(CampaignScoringService::FIELDS as $group=>$fields) {
            $rules['weights.'.$group]='required|numeric|between:0,100';
            $rules['points.'.$group]='required|array';
            foreach($fields as $key=>$label) $rules['points.'.$group.'.'.$key]='required|numeric|between:0,100';
        }
        // Seuils des tranches de distance (km) : facultatifs, les seuils par défaut s'appliquent sinon.
        $rules['distance_thresholds']='nullable|array';
        foreach(array_keys(CampaignScoringService::DISTANCE_THRESHOLDS) as $key) $rules['distance_thresholds.'.$key]='required_with:distance_thresholds|integer|between:1,10000';
        $data=$request->validate($rules,['distance_thresholds.*.between'=>'Le seuil de distance doit être compris entre 1 et 10 000 km.',
            'distance_thresholds.*.required_with'=>'Indiquez le seuil de distance.',
            'distance_thresholds.*.integer'=>'Le seuil de distance doit être un nombre entier de kilomètres.']);
        $weights=array_intersect_key($data['weights'],CampaignScoringService::GROUPS);
        if(abs(array_sum($weights)-100)>0.00001)throw ValidationException::withMessages(['weights'=>'Le total des pondérations doit être égal à 100 %.']);
        $seuils=null;
        if(!empty($data['distance_thresholds'])) {
            $seuils=array_map('intval',array_intersect_key($data['distance_thresholds'],CampaignScoringService::DISTANCE_THRESHOLDS));
            $precedent=null;
            foreach($seuils as $key=>$seuil) {
                if($precedent!==null && $seuil<=$precedent)throw ValidationException::withMessages(['distance_thresholds.'.$key=>'Chaque seuil de distance doit être supérieur au précédent.']);
                $precedent=$seuil;
            }
        }
        $points=[];
        foreach(CampaignScoringService::FIELDS as $group=>$fields)$points[$group]=array_intersect_key($data['points'][$group],$fields);
        DB::transaction(function()use($data,$weights,$points,$seuils){
            $campaign=Campagne::whereKey($data['campagne_id'])->lockForUpdate()->firstOrFail();
            CampaignScoring::create(['campagne_id'=>$campaign->getKey(),'weights'=>$weights,'points'=>$points,'distance_thresholds'=>$seuils,
                'minimum_score'=>$data['minimum_score'],'pmr_first'=>$data['pmr_first'],'active'=>$data['active'],
                'created_by'=>auth('admin')->id()]);
        },3);
        return redirect()->route('admin.hebergement.parametrage-herb.scoring-campagne.index',['campagne_id'=>$data['campagne_id']])
            ->with('success','Barème enregistré. Il sera utilisé au prochain classement automatique si vous l’avez activé.');
    }
}
