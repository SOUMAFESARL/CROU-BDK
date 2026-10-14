<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonne « remember_token » des étudiants : requise par le guard « etudiant »
 * lorsque l'option « Se souvenir de moi » est cochée à la connexion.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('etudiants') || Schema::hasColumn('etudiants', 'remember_token')) {
            return;
        }

        Schema::table('etudiants', function (Blueprint $table) {
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('etudiants') && Schema::hasColumn('etudiants', 'remember_token')) {
            Schema::table('etudiants', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        }
    }
};
