<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La publication des listes définitives porte aussi sur la file d'attente : le droit de publier la file d'attente n'a
 * plus d'usage.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $permission = DB::table('permissions')->where('slug', 'hebergement.admissions.definitive_attente_publier')->value('id');
            if ($permission) {
                DB::table('role_permission')->where('permission_id', $permission)->delete();
                DB::table('permissions')->where('id', $permission)->delete();
            }
            $module = DB::table('modules')->where('slug', 'hebergement.admissions')->first();
            if ($module) {
                $actions = json_decode($module->actions ?: '[]', true) ?: [];
                DB::table('modules')->where('id', $module->id)->update([
                    'actions' => json_encode(array_values(array_diff($actions, ['definitive_attente_publier']))),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Droit sans usage : rien à rétablir.
    }
};
