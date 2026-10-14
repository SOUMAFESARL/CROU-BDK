<?php
namespace App\Http\Services;

use App\Models\{HousingEntrySetting,HousingEntryAppointment,HousingEntryMessage,AssignationChambre,StudentContractApproval};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EntryAppointmentService
{
    public function schedule(string $assignmentId): ?HousingEntryAppointment
    {
        return DB::transaction(function()use($assignmentId){
            $settings=HousingEntrySetting::whereKey(1)->lockForUpdate()->first();
            $options=$settings?->values ?? [];
            if (empty($options['appointments_enabled'])) return null;
            $assignment=AssignationChambre::whereKey($assignmentId)->lockForUpdate()->first();
            if (!$assignment || !$assignment->estActive()
                || !StudentContractApproval::where('assignment_id',$assignmentId)->whereNotNull('approved_at')->exists()) return null;
            $existing=HousingEntryAppointment::where('assignment_id',$assignmentId)->first();
            if ($existing) return $existing;
            $earliest=now()->addHours((int)$options['lead_hours']);
            $first=Carbon::parse($options['start_date'])->startOfDay()->max($earliest->copy()->startOfDay());
            for($day=0;$day<90;$day++){
                $date=$first->copy()->addDays($day);
                if (!in_array($date->dayOfWeekIso,array_map('intval',$options['weekdays']),true)) continue;
                $slot=$date->copy()->setTimeFromTimeString($options['opening_time']);
                $end=$date->copy()->setTimeFromTimeString($options['closing_time']);
                while($slot->copy()->addMinutes((int)$options['slot_minutes'])->lte($end)){
                    $finish=$slot->copy()->addMinutes((int)$options['slot_minutes']);
                    if ($slot->gte($earliest) && HousingEntryAppointment::where('status','scheduled')
                        ->where('starts_at','<',$finish)->where('ends_at','>',$slot)->count() < (int)$options['capacity']) {
                        $appointment=HousingEntryAppointment::create([
                            'assignment_id'=>$assignmentId,'student_id'=>$assignment->etudiant_id,
                            'starts_at'=>$slot,'ends_at'=>$finish,'location'=>$options['location'],'status'=>'scheduled',
                        ]);
                        HousingEntryMessage::firstOrCreate(['event_key'=>'appointment:'.$appointment->id],[
                            'student_id'=>$assignment->etudiant_id,'assignment_id'=>$assignmentId,'kind'=>'appointment',
                            'subject'=>'Votre rendez-vous d’entrée',
                            'body'=>'Votre entrée est prévue le '.$slot->format('d/m/Y à H:i').' ('.config('app.timezone').') à '.$options['location']
                                .'. Consultez votre contrat et présentez-vous pour l’état des lieux et la remise des clés.',
                            'details'=>['appointment_id'=>$appointment->id,'starts_at'=>$slot->toIso8601String(),'location'=>$options['location']],
                        ]);
                        return $appointment;
                    }
                    $slot=$finish;
                }
            }
            return null;
        },3);
    }
}
