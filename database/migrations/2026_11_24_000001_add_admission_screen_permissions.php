<?php

use App\Services\AdmissionPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $module = DB::table('modules')->where('slug', 'hebergement.admissions')->first();
            if (!$module) {
                throw new RuntimeException('Module hebergement.admissions introuvable.');
            }

            $labels = [
                'view' => 'Consulter', 'valider' => 'Valider', 'refuser' => 'Refuser',
                'attente' => 'Mettre en attente', 'approuver' => 'Approuver',
                'publier' => 'Publier', 'export' => 'Exporter', 'lever_refus' => 'Lever un refus automatique',
                'traiter' => 'Traiter', 'decision' => 'Décider depuis la fiche', 'document' => 'Valider ou rejeter une pièce',
            ];
            $moduleActions = json_decode($module->actions ?: '[]', true) ?: [];
            $date = now();
            foreach (AdmissionPermissions::SCOPES as $scope => [$screen, $actions]) {
                foreach ($actions as $action) {
                    $slug = AdmissionPermissions::slug($scope, $action);
                    $localAction = $scope.'_'.$action;
                    $name = $screen.' — '.$labels[$action];
                    $moduleActions[] = $localAction;
                    $created = DB::table('permissions')->insertOrIgnore([
                        'slug' => $slug, 'module_id' => $module->id, 'module' => $module->name,
                        'action' => $localAction, 'name' => $name, 'description' => $name,
                        'created_at' => $date, 'updated_at' => $date,
                    ]);

                    if (!$created) {
                        continue;
                    }

                    $legacy = in_array($action, ['view', 'export'], true) ? 'view' : 'validate';
                    $legacyId = DB::table('permissions')->where('slug', AdmissionPermissions::PREFIX.$legacy)->value('id');
                    $roles = DB::table('role_permission')->where('permission_id', $legacyId)->pluck('role_id');
                    $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
                    foreach ($roles as $roleId) {
                        DB::table('role_permission')->insertOrIgnore([
                            'role_id' => $roleId, 'permission_id' => $permissionId,
                            'created_at' => $date, 'updated_at' => $date,
                        ]);
                    }
                }
            }
            DB::table('modules')->where('id', $module->id)->update([
                'actions' => json_encode(array_values(array_unique($moduleActions))),
                'updated_at' => $date,
            ]);
        });
    }

    public function down(): void
    {
        // Conserver les droits répartis entre les rôles lors d’un retour du code.
    }
};
