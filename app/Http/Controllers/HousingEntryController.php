<?php
namespace App\Http\Controllers;
use App\Models\{HousingEntrySetting,HousingEntryMessage,HousingEntryAppointment};
use App\Http\Services\ContractTerms;
use Illuminate\Http\Request;
class HousingEntryController extends Controller
{
    private function authorizeSettings(): void
    {
        $actor=auth('admin')->user();
        abort_unless($actor && ($actor->hasPermission('admissions.manage') || $actor->hasPermission('hebergement.residents.update')),403);
    }
    public function settings()
    {
        $this->authorizeSettings();
        return view('admin.hebergement.entry-settings',[
            'options'=>HousingEntrySetting::current()->values,
            'terms'=>app(ContractTerms::class)->values(),
            'appointments'=>HousingEntryAppointment::with('student')->latest('starts_at')->paginate(20),
            'failed'=>HousingEntryMessage::where('status','failed')->count(),
        ]);
    }
    public function update(Request $request)
    {
        $this->authorizeSettings();
        $data=$request->validate([
            'enabled'=>'required|boolean','proposal_hours'=>'required|integer|min:1|max:720',
            'appointments_enabled'=>'required|boolean',
            'start_date'=>'nullable|required_if:appointments_enabled,1|date',
            'weekdays'=>'nullable|required_if:appointments_enabled,1|array|min:1',
            'weekdays.*'=>'integer|between:1,7|distinct',
            'opening_time'=>'nullable|required_if:appointments_enabled,1|date_format:H:i',
            'closing_time'=>'nullable|required_if:appointments_enabled,1|date_format:H:i|after:opening_time',
            'slot_minutes'=>'required|integer|min:5|max:240','capacity'=>'required|integer|min:1|max:100',
            'lead_hours'=>'required|integer|min:0|max:720','location'=>'nullable|required_if:appointments_enabled,1|string|max:255',
            'contract'=>'required|array','contract.*'=>'required|string|max:3000',
        ]);
        if ($data['appointments_enabled']) {
            $minutes=\Carbon\Carbon::parse($data['opening_time'])->diffInMinutes(\Carbon\Carbon::parse($data['closing_time']));
            if ($minutes<(int)$data['slot_minutes']) throw \Illuminate\Validation\ValidationException::withMessages(['slot_minutes'=>'La plage d’accueil doit contenir au moins un créneau.']);
        }
        $data['contract']=array_intersect_key($data['contract'],ContractTerms::DEFAULTS);
        \Illuminate\Support\Facades\DB::transaction(function()use($data){
            $settings=HousingEntrySetting::whereKey(1)->lockForUpdate()->firstOrFail();
            $settings->update(['values'=>array_replace($settings->values,$data)]);
        });
        return back()->with('success','Paramètres enregistrés. Les contrats déjà approuvés et les rendez-vous existants sont conservés.');
    }
    public function retry()
    {
        $this->authorizeSettings();
        HousingEntryMessage::where('status','failed')->update(['status'=>'pending','error'=>null]);
        return back()->with('success','Les envois en échec seront repris par le traitement automatique.');
    }
    public function student(Request $request)
    {
        $student=$request->attributes->get('student') ?? auth('etudiant')->user();
        abort_unless($student,401);
        $messages=HousingEntryMessage::where('student_id',$student->getKey())->latest()->paginate(20);
        $appointments=HousingEntryAppointment::where('student_id',$student->getKey())->orderByDesc('starts_at')->get();
        if ($request->expectsJson()) return response()->json(['messages'=>$messages,'appointments'=>$appointments]);
        return view('etudiant.housing-entry',compact('student','messages','appointments'));
    }
}
