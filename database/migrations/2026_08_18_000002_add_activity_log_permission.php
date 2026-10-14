<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        DB::table('modules')->updateOrInsert(
            ['slug' => 'activity'],
            [
                'parent_id' => null,
                'name' => "Journal d'activité",
                'description' => 'Consulter les actions enregistrées dans l’application.',
                'icon' => 'fa-solid fa-clock-rotate-left',
                'route_name' => 'admin.activity-logs.index',
                'menu_key' => 'activity',
                'submenu_key' => null,
                'position' => 5,
                'level' => 0,
                'actions' => json_encode(['view']),
                'is_active' => true,
                'show_in_menu' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $moduleId = DB::table('modules')->where('slug', 'activity')->value('id');

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'activity.view'],
            [
                'module_id' => $moduleId,
                'module' => "Journal d'activité",
                'action' => 'view',
                'name' => "Consulter tous les journaux d'activité",
                'description' => "Autorise un responsable à consulter l'activité de tous les utilisateurs.",
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $permissionId = DB::table('permissions')->where('slug', 'activity.view')->value('id');
        if (DB::table('roles')->where('id', 1)->exists()) {
            DB::table('role_permission')->updateOrInsert(
                ['role_id' => 1, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'activity.view')->value('id');
        if ($permissionId) DB::table('role_permission')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('slug', 'activity.view')->delete();
        DB::table('modules')->where('slug', 'activity')->delete();
    }
};
