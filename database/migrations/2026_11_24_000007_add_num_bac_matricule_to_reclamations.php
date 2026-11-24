<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Réclamations : numéro de table au bac ou matricule saisi par l'étudiant (null pour les réclamations antérieures). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reclamations') && ! Schema::hasColumn('reclamations', 'num_bac_matricule')) {
            Schema::table('reclamations', function (Blueprint $table) {
                $table->string('num_bac_matricule', 50)->nullable()->after('date_naissance');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reclamations', 'num_bac_matricule')) {
            Schema::table('reclamations', fn (Blueprint $table) => $table->dropColumn('num_bac_matricule'));
        }
    }
};
