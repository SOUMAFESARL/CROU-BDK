<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $definitions = [
            'activity' => ['name' => 'activity', 'actions' => ['view']],
            'dashboard' => ['name' => 'dashboard', 'actions' => ['view']],
            'hebergement.admissions' => ['name' => 'hebergement.admissions', 'actions' => ['create', 'validate', 'view']],
            'hebergement.admissions.derogations' => ['name' => 'hebergement.admissions.derogations', 'actions' => ['validate']],
            'hebergement.annonces' => ['name' => 'Annonces', 'actions' => ['create', 'delete', 'update', 'validate', 'view']],
            'hebergement.attributions' => ['name' => 'hebergement.attributions', 'actions' => ['assign', 'export', 'validate', 'view']],
            'hebergement.departs' => ['name' => 'hebergement.departs', 'actions' => ['close', 'create', 'export', 'update', 'view']],
            'hebergement.discipline' => ['name' => 'Discipline', 'actions' => ['create', 'update', 'view']],
            'hebergement.equipements' => ['name' => 'hebergement.equipements', 'actions' => ['create', 'delete', 'update', 'view']],
            'hebergement.maintenance' => ['name' => 'Maintenance', 'actions' => ['assign', 'create', 'update', 'view']],
            'hebergement.paiements' => ['name' => 'hebergement.paiements', 'actions' => ['create', 'export', 'update', 'validate', 'view']],
            'hebergement.parametrage' => ['name' => 'hebergement.parametrage', 'actions' => ['configure', 'view']],
            'hebergement.parc' => ['name' => 'hebergement.parc', 'actions' => ['create', 'delete', 'export', 'update', 'view']],
            'hebergement.reporting' => ['name' => 'hebergement.reporting', 'actions' => ['view']],
            'hebergement.residents' => ['name' => 'hebergement.residents', 'actions' => ['update', 'view']],
            'hebergement.vie-residentielle' => ['name' => 'hebergement.vie-residentielle', 'actions' => ['view']],
            'hebergement' => ['name' => 'hebergement', 'actions' => ['view']],
            'parametrage.configuration' => ['name' => 'parametrage.configuration', 'actions' => ['configure', 'view']],
            'parametrage.legal-documents' => ['name' => 'parametrage.legal-documents', 'actions' => ['manage', 'signatures']],
            'parametrage.modules' => ['name' => 'parametrage.modules', 'actions' => ['configure', 'create', 'delete', 'update', 'view']],
            'parametrage.payment-providers' => ['name' => 'parametrage.payment-providers', 'actions' => ['configure']],
            'parametrage.referentiels' => ['name' => 'parametrage.referentiels', 'actions' => ['create', 'delete', 'update', 'view']],
            'parametrage.roles' => ['name' => 'parametrage.roles', 'actions' => ['assign', 'create', 'delete', 'update', 'view']],
            'parametrage' => ['name' => 'parametrage', 'actions' => ['view']],
            'utilisateurs' => ['name' => 'Utilisateurs', 'actions' => ['create', 'delete', 'update', 'view']],
        ];
        DB::transaction(function () use ($definitions) {
            foreach ($definitions as $slug => $definition) {
                $module = DB::table('modules')->where('slug', $slug)->first();
                if (!$module) {
                    $parentSlug = str_contains($slug, '.') ? substr($slug, 0, strrpos($slug, '.')) : null;
                    $id = DB::table('modules')->insertGetId([
                        'slug' => $slug, 'name' => $definition['name'],
                        'parent_id' => $parentSlug ? DB::table('modules')->where('slug', $parentSlug)->value('id') : null,
                        'level' => substr_count($slug, '.'), 'actions' => '[]',
                        'is_active' => true, 'show_in_menu' => false, 'is_system' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $module = (object) ['id' => $id, 'name' => $definition['name'], 'actions' => '[]'];
                }
                $actions = array_values(array_unique(array_merge(json_decode($module->actions ?: '[]', true), $definition['actions'])));
                DB::table('modules')->where('id', $module->id)->update(['actions' => json_encode($actions), 'updated_at' => now()]);
                foreach ($definition['actions'] as $action) {
                    $permissionSlug = $slug.'.'.$action;
                    if (!DB::table('permissions')->where('slug', $permissionSlug)->exists()) {
                        $labels = ['view'=>'Consulter', 'create'=>'Créer', 'update'=>'Modifier', 'delete'=>'Supprimer', 'validate'=>'Valider', 'assign'=>'Affecter', 'export'=>'Exporter', 'configure'=>'Configurer', 'close'=>'Clôturer'];
                        DB::table('permissions')->insert([
                            'slug' => $permissionSlug, 'module_id' => $module->id,
                            'module' => $module->name, 'action' => $action,
                            'name' => ($labels[$action] ?? ucfirst($action)).' — '.$module->name,
                            'description' => 'Autorise cette action dans le module '.$module->name.'.',
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    if (DB::table('roles')->where('id', 1)->exists()) {
                        DB::table('role_permission')->insertOrIgnore([
                            'role_id' => 1,
                            'permission_id' => DB::table('permissions')->where('slug', $permissionSlug)->value('id'),
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Preserve permissions and assignments that may now be used by custom roles.
    }
};
