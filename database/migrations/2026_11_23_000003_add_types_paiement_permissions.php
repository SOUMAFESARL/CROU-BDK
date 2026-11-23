<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Droits dédiés de la page « Types de paiement » (Paramétrage hébergement › Parc immobilier) :
 * module `hebergement.types-paiement`, actions `view`, `create`, `update`, `delete`.
 * Idempotente. Le rôle 1 reçoit tous les droits ; pour ne retirer l'accès à personne, les rôles qui pouvaient déjà
 * consulter (`hebergement.parametrage.view`) ou configurer (`hebergement.parametrage.configure`) le paramétrage
 * reçoivent respectivement la consultation et l'ajout / la modification / la suppression des types de paiement.
 */
return new class extends Migration
{
    private const MODULE = 'hebergement.types-paiement';

    private const NOM = 'Types de paiement';

    private const ACTIONS = [
        'view' => ['Consulter les types de paiement', 'hebergement.parametrage.view'],
        'create' => ['Ajouter un type de paiement', 'hebergement.parametrage.configure'],
        'update' => ['Modifier un type de paiement', 'hebergement.parametrage.configure'],
        'delete' => ['Supprimer un type de paiement', 'hebergement.parametrage.configure'],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $module = DB::table('modules')->where('slug', self::MODULE)->first();
            if (! $module) {
                $parent = DB::table('modules')->where('slug', 'hebergement')->value('id');
                $id = DB::table('modules')->insertGetId([
                    'slug' => self::MODULE, 'name' => self::NOM,
                    'parent_id' => $parent, 'level' => $parent ? 1 : 0,
                    'actions' => '[]', 'is_active' => true, 'show_in_menu' => false, 'is_system' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $module = (object) ['id' => $id, 'name' => self::NOM, 'actions' => '[]'];
            }

            $actions = array_values(array_unique(array_merge(json_decode($module->actions ?: '[]', true) ?: [], array_keys(self::ACTIONS))));
            DB::table('modules')->where('id', $module->id)->update(['actions' => json_encode($actions), 'updated_at' => now()]);

            foreach (self::ACTIONS as $action => [$libelle, $droitExistant]) {
                $slug = self::MODULE.'.'.$action;
                if (! DB::table('permissions')->where('slug', $slug)->exists()) {
                    DB::table('permissions')->insert([
                        'slug' => $slug, 'module_id' => $module->id,
                        'module' => $module->name, 'action' => $action,
                        'name' => $libelle, 'description' => $libelle.'.',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $permission = DB::table('permissions')->where('slug', $slug)->value('id');

                $roles = DB::table('role_permission')
                    ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                    ->where('permissions.slug', $droitExistant)
                    ->pluck('role_permission.role_id');
                if (DB::table('roles')->where('id', 1)->exists()) {
                    $roles->push(1);
                }
                foreach ($roles->unique() as $role) {
                    DB::table('role_permission')->insertOrIgnore([
                        'role_id' => $role, 'permission_id' => $permission,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Les droits restent : ils peuvent avoir été attribués à des rôles personnalisés.
    }
};
