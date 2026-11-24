<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Réclamations : le droit de traiter devient l'action « Traiter » (`hebergement.reclamations.traiter`), affichée
 * « Modifier » jusqu'ici (`.update`). Le droit est renommé : les rôles qui l'ont le gardent. Idempotente.
 */
return new class extends Migration
{
    private const MODULE = 'hebergement.reclamations';

    public function up(): void
    {
        DB::transaction(function () {
            $module = DB::table('modules')->where('slug', self::MODULE)->first();
            if (! $module) {
                return;
            }

            $droit = ['slug' => self::MODULE.'.traiter', 'action' => 'traiter', 'name' => 'Traiter une réclamation', 'description' => 'Traiter une réclamation.', 'updated_at' => now()];
            $ancien = DB::table('permissions')->where('slug', self::MODULE.'.update')->value('id');
            $nouveau = DB::table('permissions')->where('slug', $droit['slug'])->value('id');
            if ($ancien && ! $nouveau) {
                DB::table('permissions')->where('id', $ancien)->update($droit);
                $nouveau = $ancien;
            } elseif ($ancien) {
                // Les deux existent : les rôles de l'ancien droit reçoivent le nouveau.
                foreach (DB::table('role_permission')->where('permission_id', $ancien)->pluck('role_id') as $role) {
                    DB::table('role_permission')->insertOrIgnore(['role_id' => $role, 'permission_id' => $nouveau, 'created_at' => now(), 'updated_at' => now()]);
                }
                DB::table('role_permission')->where('permission_id', $ancien)->delete();
                DB::table('permissions')->where('id', $ancien)->delete();
            } elseif (! $nouveau) {
                $nouveau = DB::table('permissions')->insertGetId($droit + ['module_id' => $module->id, 'module' => $module->name, 'created_at' => now()]);
            }
            if (DB::table('roles')->where('id', 1)->exists()) {
                DB::table('role_permission')->insertOrIgnore(['role_id' => 1, 'permission_id' => $nouveau, 'created_at' => now(), 'updated_at' => now()]);
            }

            $actions = array_map(fn ($action) => $action === 'update' ? 'traiter' : $action, json_decode($module->actions ?: '[]', true) ?: []);
            DB::table('modules')->where('id', $module->id)->update([
                'actions' => json_encode(array_values(array_unique([...$actions, 'traiter']))), 'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // Le droit reste : il peut avoir été attribué à des rôles personnalisés.
    }
};
