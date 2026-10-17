<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permissions « Modifier » et « Supprimer » des campagnes (module « Campagnes et admissions »).
 * Idempotente : n'ajoute que ce qui manque ; le rôle 1 (super administrateur) les reçoit.
 */
return new class extends Migration {
    private const MODULE = 'hebergement.admissions';
    private const ACTIONS = ['update' => 'Modifier', 'delete' => 'Supprimer'];

    public function up(): void
    {
        DB::transaction(function () {
            $module = DB::table('modules')->where('slug', self::MODULE)->first();
            if (!$module) {
                return;
            }

            $actions = array_values(array_unique(array_merge(json_decode($module->actions ?: '[]', true) ?: [], array_keys(self::ACTIONS))));
            DB::table('modules')->where('id', $module->id)->update(['actions' => json_encode($actions), 'updated_at' => now()]);

            foreach (self::ACTIONS as $action => $label) {
                $slug = self::MODULE.'.'.$action;
                if (!DB::table('permissions')->where('slug', $slug)->exists()) {
                    DB::table('permissions')->insert([
                        'slug' => $slug, 'module_id' => $module->id,
                        'module' => $module->name, 'action' => $action,
                        'name' => $label.' — '.$module->name,
                        'description' => 'Autorise cette action dans le module '.$module->name.'.',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                if (DB::table('roles')->where('id', 1)->exists()) {
                    DB::table('role_permission')->insertOrIgnore([
                        'role_id' => 1,
                        'permission_id' => DB::table('permissions')->where('slug', $slug)->value('id'),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Les permissions restent : elles peuvent avoir été attribuées à des rôles personnalisés.
    }
};
