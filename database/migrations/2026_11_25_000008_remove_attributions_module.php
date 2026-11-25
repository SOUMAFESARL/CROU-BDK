<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La page « Attribution des chambres » est supprimée, avec son module `hebergement.attributions`.
 * Le formulaire d'attribution relève de « Résidents et contrats » : les rôles qui avaient `assign` / `validate` reçoivent
 * `hebergement.residents.attribuer` / `hebergement.residents.pmr`. Idempotente.
 */
return new class extends Migration
{
    private const ANCIEN = 'hebergement.attributions';

    private const MODULE = 'hebergement.residents';

    /** Nouvelle action => ancien droit repris et libellé. */
    private const ACTIONS = [
        'attribuer' => ['assign', 'Attribuer une chambre à un étudiant admis à loger'],
        'pmr' => ['validate', 'Enregistrer la vérification du statut PMR'],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $this->ajouterDroitsResidents();

            $anciens = DB::table('permissions')->where('slug', 'like', self::ANCIEN.'.%')->pluck('id');
            DB::table('role_permission')->whereIn('permission_id', $anciens)->delete();
            DB::table('permissions')->whereIn('id', $anciens)->delete();
            DB::table('modules')->where('slug', self::ANCIEN)->delete();
        });
    }

    public function down(): void
    {
        // Page supprimée : le module et ses droits ne sont pas rétablis.
    }

    private function ajouterDroitsResidents(): void
    {
        $module = DB::table('modules')->where('slug', self::MODULE)->first();
        if (! $module) {
            return;
        }
        $actions = array_values(array_unique([...(json_decode($module->actions ?: '[]', true) ?: []), ...array_keys(self::ACTIONS)]));
        DB::table('modules')->where('id', $module->id)->update(['actions' => json_encode($actions), 'updated_at' => now()]);

        foreach (self::ACTIONS as $action => [$repris, $libelle]) {
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
            $ancien = DB::table('permissions')->where('slug', self::ANCIEN.'.'.$repris)->value('id');
            $roles = $ancien ? DB::table('role_permission')->where('permission_id', $ancien)->pluck('role_id') : collect();
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
    }
};
