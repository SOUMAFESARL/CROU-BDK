<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_derogations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('etudiant_id')->index();
            $table->string('demande_id')->index();
            $table->string('campagne_id')->index();
            $table->string('requested_by');
            $table->string('requester_name');
            $table->text('reason');
            $table->json('eligibility');
            $table->string('status')->default('pending');
            $table->string('decided_by')->nullable();
            $table->string('decider_name')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
        $moduleId = DB::table('modules')->insertGetId([
            'parent_id' => DB::table('modules')->where('slug', 'hebergement.admissions')->value('id'),
            'name' => 'Dérogations d’admission', 'slug' => 'hebergement.admissions.derogations',
            'level' => 2, 'actions' => json_encode(['validate']),
            'is_active' => true, 'show_in_menu' => false, 'is_system' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $id = DB::table('permissions')->insertGetId([
            'module_id' => $moduleId, 'action' => 'validate',
            'name' => 'Valider les dérogations d’admission', 'slug' => 'hebergement.admissions.derogations.validate',
            'module' => 'Admissions', 'description' => 'Décider sur la dérogation demandée par un autre agent.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['Sous-DAL', 'Chef du service logement'] as $name) {
            if (!DB::table('roles')->where('name', $name)->exists()) {
                DB::table('roles')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        foreach (DB::table('roles')->get() as $role) {
            $name = Str::lower(Str::ascii(trim($role->name)));
            if (in_array($name, ['sous-dal', 'sous dal', "sous-directeur de l'accueil et des logements", 'chef du service logement', 'chef service logement'], true)) {
                DB::table('role_permission')->insert(['role_id' => $role->id, 'permission_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
    public function down(): void
    {
        $ids = DB::table('permissions')->where('slug', 'hebergement.admissions.derogations.validate')->pluck('id');
        DB::table('role_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        DB::table('modules')->where('slug', 'hebergement.admissions.derogations')->delete();
        Schema::dropIfExists('admission_derogations');
    }
};
