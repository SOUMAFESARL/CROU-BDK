<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Droit de paramétrer les sanctions (types, points retirés, effets) et la note de conduite initiale :
 * `hebergement.discipline.configure`, ajouté au module « Discipline ». La consultation du paramétrage reste
 * ouverte au droit `hebergement.discipline.view`.
 * Idempotente : n'ajoute que ce qui manque ; seul le rôle 1 (super administrateur) reçoit le droit.
 */
return new class extends Migration
{
    private const MODULE = 'hebergement.discipline';

    private const ACTION = 'configure';

    private const LIBELLE = 'Paramétrer les sanctions et la note de conduite';

    public function up(): void
    {
        DB::transaction(function () {
            $module = DB::table('modules')->where('slug', self::MODULE)->first();
            if (! $module) {
                $id = DB::table('modules')->insertGetId([
                    'slug' => self::MODULE, 'name' => 'Discipline',
                    'parent_id' => DB::table('modules')->where('slug', 'hebergement')->value('id'),
                    'level' => 1, 'actions' => '[]',
                    'is_active' => true, 'show_in_menu' => false, 'is_system' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $module = (object) ['id' => $id, 'name' => 'Discipline', 'actions' => '[]'];
            }

            $actions = array_values(array_unique(array_merge(json_decode($module->actions ?: '[]', true) ?: [], [self::ACTION])));
            DB::table('modules')->where('id', $module->id)->update(['actions' => json_encode($actions), 'updated_at' => now()]);

            $slug = self::MODULE.'.'.self::ACTION;
            if (! DB::table('permissions')->where('slug', $slug)->exists()) {
                DB::table('permissions')->insert([
                    'slug' => $slug, 'module_id' => $module->id,
                    'module' => $module->name, 'action' => self::ACTION,
                    'name' => self::LIBELLE, 'description' => self::LIBELLE.'.',
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
        });
    }

    public function down(): void
    {
        // Le droit reste : il peut avoir été attribué à des rôles personnalisés.
    }
};
