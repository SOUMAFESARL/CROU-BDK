<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Droit `hebergement.resultats.admettre` : faire passer une demande de la file d'attente publiée dans « Admis à loger »
 * (page « Résultats d'admission »). Idempotente ; accordé au seul rôle 1.
 */
return new class extends Migration
{
    private const MODULE = 'hebergement.resultats';

    private const ACTION = 'admettre';

    private const LIBELLE = 'Valider une demande de la file d’attente';

    public function up(): void
    {
        DB::transaction(function () {
            $module = DB::table('modules')->where('slug', self::MODULE)->first();
            if (! $module) {
                return;
            }
            $actions = array_values(array_unique([...(json_decode($module->actions ?: '[]', true) ?: []), self::ACTION]));
            DB::table('modules')->where('id', $module->id)->update(['actions' => json_encode($actions), 'updated_at' => now()]);

            $slug = self::MODULE.'.'.self::ACTION;
            if (! DB::table('permissions')->where('slug', $slug)->exists()) {
                DB::table('permissions')->insert([
                    'slug' => $slug, 'module_id' => $module->id,
                    'module' => $module->name, 'action' => self::ACTION,
                    'name' => self::LIBELLE, 'description' => self::LIBELLE.'.',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            if (DB::table('roles')->where('id', 1)->exists()) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => 1, 'permission_id' => DB::table('permissions')->where('slug', $slug)->value('id'),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Le droit reste : il peut avoir été attribué à des rôles personnalisés.
    }
};
