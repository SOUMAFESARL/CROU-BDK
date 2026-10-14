<?php

namespace App\Http\Controllers\admin\immobilier\depart_etat_des_lieux;

use App\Http\Controllers\Controller;
use App\Models\DepartureSetting;
use Illuminate\Http\Request;

class DepartureSettingController extends Controller
{
    public function edit()
    {
        return view('admin.hebergement.depart-etat-des-lieux.settings', ['settings'=>DepartureSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'notice_days'=>'required|integer|min:0|max:365',
            'late_notice_penalty'=>'required|numeric|min:0',
            'unpaid_blocking_threshold'=>'required|numeric|min:0',
            'damage_labels'=>'nullable|array',
            'damage_labels.*'=>'nullable|string|max:255',
            'damage_amounts'=>'nullable|array',
            'damage_amounts.*'=>'nullable|numeric|min:0',
        ]);
        $grid = collect($data['damage_labels'] ?? [])->map(function ($label, $index) use ($data) {
            return ['label'=>trim((string)$label),'amount'=>(float)($data['damage_amounts'][$index] ?? 0)];
        })->filter(fn($item)=>$item['label']!=='')->values()->all();
        DepartureSetting::current()->update([
            'notice_days'=>$data['notice_days'],
            'late_notice_penalty'=>$data['late_notice_penalty'],
            'unpaid_blocking_threshold'=>$data['unpaid_blocking_threshold'],
            'damage_retention_grid'=>$grid,
            'updated_by'=>getInfoAdmin()->users->id_user ?? null,
        ]);
        return back()->with('success','Les paramètres de clôture ont été enregistrés.');
    }
}
