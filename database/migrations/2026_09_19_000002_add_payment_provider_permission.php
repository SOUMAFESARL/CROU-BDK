<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $moduleId = DB::table('modules')->where('slug', 'parametrage.payment-providers')->value('id')
            ?? DB::table('modules')->insertGetId([
            'parent_id' => DB::table('modules')->where('slug', 'parametrage')->value('id'),
            'name' => 'API de paiement', 'slug' => 'parametrage.payment-providers',
            'description' => 'Configurer les réseaux de paiement et leur visibilité étudiant.',
            'icon' => 'fa-solid fa-credit-card',
            'route_name' => 'admin.parametrage.payment-providers.index',
            'menu_key' => 'parametres', 'submenu_key' => 'payment-providers',
            'position' => 15, 'level' => 1, 'actions' => json_encode(['configure']),
            'is_active' => true, 'show_in_menu' => true, 'is_system' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $permissionId = DB::table('permissions')->where('slug', 'parametrage.payment-providers.configure')->value('id')
            ?? DB::table('permissions')->insertGetId([
            'module_id' => $moduleId, 'module' => 'API de paiement', 'action' => 'configure',
            'slug' => 'parametrage.payment-providers.configure',
            'name' => 'Configurer les API de paiement',
            'description' => 'Modifier les identifiants et activer ou désactiver les réseaux.',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        if (DB::table('roles')->where('id', 1)->exists()
            && ! DB::table('role_permission')->where('role_id', 1)->where('permission_id', $permissionId)->exists()) {
            DB::table('role_permission')->insert([
                'role_id' => 1, 'permission_id' => $permissionId,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('slug', 'parametrage.payment-providers.configure')->value('id');
        if ($id) {
            DB::table('role_permission')->where('permission_id', $id)->delete();
        }
        DB::table('permissions')->where('slug', 'parametrage.payment-providers.configure')->delete();
        DB::table('modules')->where('slug', 'parametrage.payment-providers')->delete();
    }
};
