<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les crédits exigés par filière ne s'appliquent qu'à la réadmission : ils quittent le paramétrage général
 * (`general.credits {seuil_defaut, filieres}`) pour les critères de réadmission (`readmission.credits {actif,
 * seuil_defaut, filieres}`, qui remplace `readmission.credits_actif`). Chaque version de réadmission reprend les seuils
 * du paramétrage général de sa campagne (version en vigueur, sinon brouillon, sinon la plus récente). Le bloc `credits`
 * des versions générales est conservé (il n'est plus lu) : `CriteresCampagne::creditsAnciens()` s'en sert encore pour une
 * campagne sans version de réadmission.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campagne_regles') || ! Schema::hasColumn('campagne_regles', 'volet')) {
            return;
        }

        $rang = ['en_vigueur' => 0, 'brouillon' => 1];
        $generaux = DB::table('campagne_regles')->where('volet', 'general')->get()
            ->groupBy('campagne_id')
            ->map(fn ($versions) => $versions
                ->sortBy(fn ($v) => [$rang[$v->statut] ?? 2, -(int) $v->version])
                ->map(fn ($v) => json_decode((string) $v->parametres, true)['credits'] ?? null)
                ->first(fn ($credits) => is_array($credits)));

        foreach (DB::table('campagne_regles')->where('volet', 'readmission')->get() as $ligne) {
            $parametres = json_decode((string) $ligne->parametres, true);
            if (! is_array($parametres) || is_array($parametres['readmission']['credits'] ?? null)) {
                continue;
            }
            $general = $generaux->get($ligne->campagne_id) ?? [];
            $parametres['readmission'] ??= [];
            $parametres['readmission']['credits'] = [
                'actif' => (bool) ($parametres['readmission']['credits_actif'] ?? true),
                'seuil_defaut' => (int) ($general['seuil_defaut'] ?? 100),
                'filieres' => array_values($general['filieres'] ?? []),
            ];
            unset($parametres['readmission']['credits_actif']);

            DB::table('campagne_regles')->where('id', $ligne->id)->update(['parametres' => json_encode($parametres)]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('campagne_regles') || ! Schema::hasColumn('campagne_regles', 'volet')) {
            return;
        }

        foreach (DB::table('campagne_regles')->where('volet', 'readmission')->get() as $ligne) {
            $parametres = json_decode((string) $ligne->parametres, true);
            $credits = $parametres['readmission']['credits'] ?? null;
            if (! is_array($credits)) {
                continue;
            }
            $parametres['readmission']['credits_actif'] = (bool) ($credits['actif'] ?? true);
            unset($parametres['readmission']['credits']);

            DB::table('campagne_regles')->where('id', $ligne->id)->update(['parametres' => json_encode($parametres)]);
        }
    }
};
