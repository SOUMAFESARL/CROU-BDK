<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Droits de la page « Résultats » (Gestion hébergement) : module `hebergement.resultats`, actions `view`, `publier`, `export`.
 * Idempotente ; droits accordés au seul rôle 1, les autres rôles les reçoivent depuis « Rôles et permissions ».
 */
return new class extends Migration
{
    private const MODULE = 'hebergement.resultats';

    private const NOM = 'Résultats';

    private const ACTIONS = [
        'view' => 'Consulter les résultats d’admission',
        'publier' => 'Choisir les campagnes visibles et masquer la page publique des résultats',
        'export' => 'Exporter les résultats d’admission',
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

            foreach (self::ACTIONS as $action => $libelle) {
                $slug = self::MODULE.'.'.$action;
                if (! DB::table('permissions')->where('slug', $slug)->exists()) {
                    DB::table('permissions')->insert([
                        'slug' => $slug, 'module_id' => $module->id,
                        'module' => $module->name, 'action' => $action,
                        'name' => $libelle, 'description' => $libelle.'.',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                if (DB::table('roles')->where('id', 1)->exists()) {
                    DB::table('role_permission')->insertOrIgnore([
                        'role_id' => 1, 'permission_id' => DB::table('permissions')->where('slug', $slug)->value('id'),
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
