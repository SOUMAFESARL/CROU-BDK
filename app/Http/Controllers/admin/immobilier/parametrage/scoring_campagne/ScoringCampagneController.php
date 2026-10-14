<?php
namespace App\Http\Controllers\admin\immobilier\parametrage\scoring_campagne;
use App\Http\Controllers\Controller;
use App\Http\Services\CampaignScoringService;
use App\Models\{Campagne, CampaignScoring};
use Illuminate\Http\Request;
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
        return view('admin.hebergement.parametrage-herb.scoring-campagne.index',[
            'campagnes'=>$campagnes,'campagne'=>$campagne,'scoring'=>$history->first(),'history'=>$history,
            'groups'=>CampaignScoringService::GROUPS,'fields'=>CampaignScoringService::FIELDS,
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
        $data=$request->validate($rules);
        $weights=array_intersect_key($data['weights'],CampaignScoringService::GROUPS);
        if(abs(array_sum($weights)-100)>0.00001)throw ValidationException::withMessages(['weights'=>'Le total des pondérations doit être égal à 100 %.']);
        $points=[];
        foreach(CampaignScoringService::FIELDS as $group=>$fields)$points[$group]=array_intersect_key($data['points'][$group],$fields);
        DB::transaction(function()use($data,$weights,$points){
            $campaign=Campagne::whereKey($data['campagne_id'])->lockForUpdate()->firstOrFail();
            CampaignScoring::create(['campagne_id'=>$campaign->getKey(),'weights'=>$weights,'points'=>$points,
                'minimum_score'=>$data['minimum_score'],'pmr_first'=>$data['pmr_first'],'active'=>$data['active'],
                'created_by'=>auth('admin')->id()]);
        },3);
        return redirect()->route('admin.hebergement.parametrage-herb.scoring-campagne.index',['campagne_id'=>$data['campagne_id']])
            ->with('success','Barème enregistré. Il sera utilisé au prochain classement automatique si vous l’avez activé.');
    }
}
