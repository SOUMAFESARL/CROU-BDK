<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function () {
            foreach ([
                'hebergement.admissions' => ['personal' => 'Consulter les informations personnelles', 'documents' => 'Consulter les pièces justificatives', 'history' => 'Consulter l’historique des demandes'],
                'utilisateurs' => ['personal' => 'Consulter les informations personnelles'],
            ] as $slug => $actions) {
                $module = DB::table('modules')->where('slug', $slug)->first();
                if (!$module) {
                    continue;
                }
                DB::table('modules')->where('id', $module->id)->update([
                    'actions' => json_encode(array_values(array_unique(array_merge(json_decode($module->actions ?: '[]', true), array_keys($actions))))),
                    'updated_at' => now(),
                ]);
                foreach ($actions as $action => $label) {
                    $permission = $slug.'.'.$action;
                    DB::table('permissions')->updateOrInsert(['slug' => $permission], [
                        'module_id' => $module->id, 'module' => $module->name, 'action' => $action,
                        'name' => $label.' — '.$module->name, 'description' => $label.' dans les vues partagées.',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if (DB::table('roles')->where('id', 1)->exists()) {
                        DB::table('role_permission')->insertOrIgnore([
                            'role_id' => 1, 'permission_id' => DB::table('permissions')->where('slug', $permission)->value('id'),
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Keep assigned section permissions when rolling back application code.
    }
};
