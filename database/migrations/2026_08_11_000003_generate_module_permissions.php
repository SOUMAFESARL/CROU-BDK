<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $labels = ['view'=>'Consulter','create'=>'Créer','update'=>'Modifier','delete'=>'Supprimer','validate'=>'Valider','assign'=>'Affecter','export'=>'Exporter','configure'=>'Configurer','transmit'=>'Transmettre','close'=>'Clôturer'];
        foreach (DB::table('modules')->get() as $module) {
            foreach (json_decode($module->actions ?: '[]', true) as $action) {
                DB::table('permissions')->updateOrInsert(['slug'=>$module->slug.'.'.$action], [
                    'module_id'=>$module->id,'module'=>$module->name,'action'=>$action,
                    'name'=>($labels[$action] ?? ucfirst($action)).' — '.$module->name,
                    'description'=>'Autorise cette action dans le module '.$module->name.'.','updated_at'=>now(),'created_at'=>now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereNotNull('module_id')->delete();
    }
};
