<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $permissionsExist = Schema::hasTable('permissions');
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('slug', 160)->unique();
                $table->string('module', 80)->index();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('role_permission')) {
            Schema::create('role_permission', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('permission_id');
                $table->timestamps();
                $table->primary(['role_id', 'permission_id']);
            });
        }

        if ($permissionsExist) {
            return;
        }

        $now = now();
        DB::table('permissions')->insert([
            ['name'=>'Gérer les rôles et permissions','slug'=>'parametrage.roles.manage','module'=>'Paramétrage','description'=>'Créer les rôles et définir leurs autorisations.','created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Gérer les utilisateurs','slug'=>'utilisateurs.manage','module'=>'Utilisateurs','description'=>'Créer, modifier et affecter les utilisateurs.','created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Consulter les statistiques','slug'=>'statistiques.view','module'=>'Statistiques','description'=>'Consulter les tableaux de bord et indicateurs.','created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Gérer les admissions','slug'=>'admissions.manage','module'=>'Admissions','description'=>'Traiter les dossiers et admissions.','created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Gérer les attributions','slug'=>'attributions.manage','module'=>'Hébergement','description'=>'Attribuer et réaffecter les chambres.','created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Gérer le parc immobilier','slug'=>'parc.manage','module'=>'Hébergement','description'=>'Administrer cités, bâtiments et chambres.','created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Gérer les paiements','slug'=>'paiements.manage','module'=>'Vie résidentielle','description'=>'Enregistrer et valider les paiements.','created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Gérer la maintenance','slug'=>'maintenance.manage','module'=>'Vie résidentielle','description'=>'Créer et traiter les ordres de travail.','created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Gérer la discipline','slug'=>'discipline.manage','module'=>'Vie résidentielle','description'=>'Traiter rapports et sanctions disciplinaires.','created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Clôturer les séjours','slug'=>'sejours.cloture','module'=>'Vie résidentielle','description'=>'Gérer les départs et états des lieux de sortie.','created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Gérer les paramètres','slug'=>'parametrage.manage','module'=>'Paramétrage','description'=>'Modifier les paramètres généraux.','created_at'=>$now,'updated_at'=>$now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
    }
};
