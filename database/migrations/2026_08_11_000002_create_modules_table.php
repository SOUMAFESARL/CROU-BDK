<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('name', 120);
            $table->string('slug', 160)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('route_name', 180)->nullable();
            $table->string('menu_key', 80)->nullable();
            $table->string('submenu_key', 80)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedTinyInteger('level')->default(0);
            $table->json('actions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_menu')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('module_id')->nullable()->after('id')->index();
            $table->string('action', 50)->nullable()->after('module');
        });

        $now = now();
        $parents = [
            ['name'=>'Tableau de bord','slug'=>'dashboard','icon'=>'fa-solid fa-table-columns','route_name'=>'admin.dashboard.index','menu_key'=>'dashboard','position'=>1],
            ['name'=>'Utilisateurs','slug'=>'utilisateurs','icon'=>'fa-solid fa-users','route_name'=>'admin.users.index','menu_key'=>'users','position'=>2],
            ['name'=>'Gestion Hébergement','slug'=>'hebergement','icon'=>'fa-solid fa-building','route_name'=>'admin.hebergement.index','menu_key'=>'hebergement','position'=>3],
            ['name'=>'Paramétrage général','slug'=>'parametrage','icon'=>'fa-solid fa-gear','route_name'=>'admin.parametrage.index','menu_key'=>'parametres','position'=>4],
        ];
        foreach ($parents as $parent) DB::table('modules')->insert($parent + ['level'=>0,'actions'=>json_encode(['view']),'is_active'=>1,'show_in_menu'=>1,'is_system'=>1,'created_at'=>$now,'updated_at'=>$now]);
        $ids = DB::table('modules')->pluck('id','slug');
        $children = [
            ['hebergement','Parc immobilier','hebergement.parc','fa-solid fa-building','admin.hebergement.parc-immobilier.index','parc-immobilier',['view','create','update','delete']],
            ['hebergement','Équipements','hebergement.equipements','fa-solid fa-toolbox','admin.hebergement.equipements.index','equipements',['view','create','update','delete']],
            ['hebergement','Campagnes et admissions','hebergement.admissions','fa-solid fa-bullhorn','admin.hebergement.campagnes_admission.admission','campagnes',['view','create','update','validate']],
            ['hebergement','Attribution des chambres','hebergement.attributions','fa-solid fa-bed','admin.allocation.index','residents',['view','assign','validate','export']],
            ['hebergement','Résidents et contrats','hebergement.residents','fa-solid fa-users','admin.hebergement.residents_contrat.admission','residents',['view','create','update']],
            ['hebergement','Vie résidentielle','hebergement.vie-residentielle','fa-solid fa-city','admin.hebergement.vie_residentielle.index','vie-cite',['view']],
            ['hebergement','Départs et états des lieux','hebergement.departs','fa-solid fa-door-open','admin.hebergement.depart_etat_des_lieux.depart','departs',['view','create','update','close']],
            ['hebergement','Paiements des loyers','hebergement.paiements','fa-solid fa-money-bill-wave','admin.hebergement.paiements.index','finances',['view','create','validate','export']],
            ['hebergement','Reporting et statistiques','hebergement.reporting','fa-solid fa-chart-bar','admin.hebergement.reporting.index','reporting',['view','export']],
            ['hebergement','Paramétrage hébergement','hebergement.parametrage','fa-solid fa-tools','admin.hebergement.parametrage-herb.index','parametrage-herb',['view','configure']],
            ['parametrage','Gestion des modules','parametrage.modules','fa-solid fa-sitemap','admin.parametrage.modules.index','modules',['view','create','update','delete','configure']],
            ['parametrage','Rôles et permissions','parametrage.roles','fa-solid fa-user-shield','admin.parametrage.roles-permissions.index','roles',['view','create','update','delete','assign']],
            ['parametrage','Référentiels généraux','parametrage.referentiels','fa-solid fa-list','admin.parametrage.countrie.index','referentiels',['view','create','update','delete']],
            ['parametrage','Configuration système','parametrage.configuration','fa-solid fa-sliders','admin.parametrage.configuration.index','parametrage',['view','configure']],
        ];
        foreach ($children as $i=>$m) DB::table('modules')->insert(['parent_id'=>$ids[$m[0]],'name'=>$m[1],'slug'=>$m[2],'icon'=>$m[3],'route_name'=>$m[4],'menu_key'=>$m[0]==='hebergement'?'hebergement':'parametres','submenu_key'=>$m[5],'position'=>$i+1,'level'=>1,'actions'=>json_encode($m[6]),'is_active'=>1,'show_in_menu'=>1,'is_system'=>1,'created_at'=>$now,'updated_at'=>$now]);
    }

    public function down(): void
    {
        Schema::table('permissions', fn (Blueprint $table) => $table->dropColumn(['module_id','action']));
        Schema::dropIfExists('modules');
    }
};
