<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jetons de réinitialisation du mot de passe des étudiants (broker « etudiants »).
 * Table dédiée : la table Laravel « password_reset_tokens » n'existe pas dans le schéma de base.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('etudiant_password_reset_tokens')) {
            return;
        }

        Schema::create('etudiant_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etudiant_password_reset_tokens');
    }
};
