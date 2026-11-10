<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Socle des droits du paramétrage des campagnes (places par cité, règles d'éligibilité, liste provisoire).
 * Idempotente : n'ajoute que ce qui manque ; seul le rôle 1 (super administrateur) les reçoit.
 */
return new class extends Migration {
    private const MODULES = [
        'hebergement.eligibilite' => [
            'name' => 'Éligibilité et liste provisoire',
            'permissions' => ['generate' => 'Générer la liste provisoire et lancer une simulation'],
        ],
        'hebergement.eligibilite.quotas' => [
            'name' => 'Places et quotas de campagne',
            'permissions' => ['view' => 'Consulter les places et quotas de campagne', 'configure' => 'Saisir les places et quotas de campagne'],
        ],
        'hebergement.eligibilite.regles' => [
            'name' => 'Règles d’éligibilité de campagne',
            'permissions' => ['view' => 'Consulter les règles d’éligibilité de campagne', 'configure' => 'Paramétrer les règles d’éligibilité de campagne'],
        ],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            foreach (self::MODULES as $slug => $definition) {
                $module = DB::table('modules')->where('slug', $slug)->first();
                if (!$module) {
                    $parent = substr($slug, 0, strrpos($slug, '.'));
                    $id = DB::table('modules')->insertGetId([
                        'slug' => $slug, 'name' => $definition['name'],
                        'parent_id' => DB::table('modules')->where('slug', $parent)->value('id'),
                        'level' => substr_count($slug, '.'), 'actions' => '[]',
                        'is_active' => true, 'show_in_menu' => false, 'is_system' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $module = (object) ['id' => $id, 'name' => $definition['name'], 'actions' => '[]'];
                }

                $actions = array_values(array_unique(array_merge(json_decode($module->actions ?: '[]', true) ?: [], array_keys($definition['permissions']))));
                DB::table('modules')->where('id', $module->id)->update(['actions' => json_encode($actions), 'updated_at' => now()]);

                foreach ($definition['permissions'] as $action => $label) {
                    $permission = $slug.'.'.$action;
                    if (!DB::table('permissions')->where('slug', $permission)->exists()) {
                        DB::table('permissions')->insert([
                            'slug' => $permission, 'module_id' => $module->id,
                            'module' => $module->name, 'action' => $action,
                            'name' => $label, 'description' => $label.'.',
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    if (DB::table('roles')->where('id', 1)->exists()) {
                        DB::table('role_permission')->insertOrIgnore([
                            'role_id' => 1,
                            'permission_id' => DB::table('permissions')->where('slug', $permission)->value('id'),
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Les permissions restent : elles peuvent avoir été attribuées à des rôles personnalisés.
    }
};
