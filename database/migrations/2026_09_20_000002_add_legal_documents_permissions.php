<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $module = DB::table('modules')->where('slug', 'parametrage.legal-documents')->value('id')
            ?? DB::table('modules')->insertGetId([
            'parent_id' => DB::table('modules')->where('slug', 'parametrage')->value('id'),
            'name' => 'Signatures et documents', 'slug' => 'parametrage.legal-documents',
            'description' => 'Configurer les signataires et rédiger les documents de la résidence.',
            'icon' => 'fa-solid fa-file-signature', 'route_name' => 'admin.parametrage.legal-documents.index',
            'menu_key' => 'parametres', 'submenu_key' => 'legal-documents', 'position' => 16, 'level' => 1,
            'actions' => json_encode(['manage', 'signatures']), 'is_active' => true, 'show_in_menu' => true,
            'is_system' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        foreach (['manage' => 'Rédiger et valider les documents', 'signatures' => 'Configurer les signatures des responsables'] as $action => $name) {
            $id = DB::table('permissions')->where('slug', 'parametrage.legal-documents.'.$action)->value('id')
                ?? DB::table('permissions')->insertGetId([
                'module_id' => $module, 'module' => 'Signatures et documents', 'action' => $action,
                'slug' => 'parametrage.legal-documents.'.$action, 'name' => $name,
                'description' => $name, 'created_at' => $now, 'updated_at' => $now,
            ]);
            if (DB::table('roles')->where('id', 1)->exists()
                && ! DB::table('role_permission')->where('role_id', 1)->where('permission_id', $id)->exists()) {
                DB::table('role_permission')->insert(['role_id' => 1, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('slug', [
            'parametrage.legal-documents.manage', 'parametrage.legal-documents.signatures',
        ])->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        DB::table('modules')->where('slug', 'parametrage.legal-documents')->delete();
    }
};
