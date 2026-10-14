<?php
namespace App\Http\Services;

use App\Models\DepartureSetting;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DepartureClosureRules
{
    public function evaluate(DepartureSetting $settings, string $noticeDate, string $exitDate, array $pieces, float $unpaid): array
    {
        $threshold = (float) $settings->unpaid_blocking_threshold;
        if ($threshold > 0 && $unpaid >= $threshold) {
            throw ValidationException::withMessages([
                'id_etudiant' => 'Clôture bloquée : les impayés du résident ('.number_format($unpaid, 0, ',', ' ').' FCFA) atteignent le seuil autorisé.',
            ]);
        }
        $days = max(0, (int) Carbon::parse($noticeDate)->startOfDay()->diffInDays(Carbon::parse($exitDate)->startOfDay(), false));
        $penalty = $days < (int) $settings->notice_days ? (float) $settings->late_notice_penalty : 0;
        $rules = collect($settings->damage_retention_grid ?? [])
            ->filter(fn ($rule) => !empty($rule['label']) && (float) ($rule['amount'] ?? 0) > 0);
        $retentions = [];
        foreach ($pieces as $piece) {
            foreach ($piece['elements'] ?? [] as $element) {
                if (($element['etat'] ?? '') !== 'mauvais') continue;
                $name = trim((string) ($element['name_element'] ?? $element['name'] ?? ''));
                if ($name === '') continue;
                $normalized = Str::of($name)->lower()->ascii()->toString();
                $rule = $rules->first(fn ($rule) =>
                    str_contains($normalized, Str::of($rule['label'])->lower()->ascii()->toString())
                    || str_contains(Str::of($rule['label'])->lower()->ascii()->toString(), $normalized));
                if ($rule) $retentions[] = [
                    'piece' => $piece['name_piece'] ?? $piece['name'] ?? '',
                    'element' => $name, 'rule' => $rule['label'], 'amount' => (float) $rule['amount'],
                ];
            }
        }
        return [
            'preavis_effectif_jours' => $days, 'penalite_preavis' => $penalty,
            'retenues_degradations' => $retentions,
            'parametres' => [
                'notice_days' => (int) $settings->notice_days,
                'late_notice_penalty' => (float) $settings->late_notice_penalty,
                'unpaid_blocking_threshold' => $threshold,
                'damage_retention_grid' => $settings->damage_retention_grid ?? [],
            ],
            'impayes_constates' => $unpaid,
        ];
    }
}
