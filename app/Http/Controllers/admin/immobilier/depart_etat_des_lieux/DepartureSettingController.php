<?php

namespace App\Http\Controllers\admin\immobilier\depart_etat_des_lieux;

use App\Http\Controllers\Controller;
use App\Models\DepartureSetting;
use App\Services\AdminAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartureSettingController extends Controller
{
    /** Règles de clôture des séjours (page React `Admin/Hebergement/Departs/Parametres`). */
    public function edit(Request $request)
    {
        $settings = DepartureSetting::current();
        $access = app(AdminAccess::class);
        $url = fn (string $route) => $access->allows($request->user('admin'), $route) ? route($route) : null;

        return Inertia::render('Admin/Hebergement/Departs/Parametres', [
            'settings' => [
                'notice_days' => (int) $settings->notice_days,
                'late_notice_penalty' => (float) $settings->late_notice_penalty,
                'unpaid_blocking_threshold' => (float) $settings->unpaid_blocking_threshold,
                'damage_retention_grid' => collect($settings->damage_retention_grid ?? [])
                    ->map(fn ($rule) => is_array($rule)
                        ? ['label' => (string) ($rule['label'] ?? ''), 'amount' => (float) ($rule['amount'] ?? 0)]
                        : ['label' => (string) $rule, 'amount' => 0.0])
                    ->filter(fn ($rule) => trim($rule['label']) !== '')->values(),
                'updated_at' => $settings->updated_at?->format('Y-m-d H:i'),
            ],
            'urls' => [
                'update' => $url('admin.hebergement.depart_etat_des_lieux.settings.update'),
                'departs' => $url('admin.hebergement.depart_etat_des_lieux.depart'),
            ],
        ]);
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
