<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réclamations sur les demandes d'hébergement : périodes d'ouverture (une campagne, une date de début et de fin)
 * et réclamations déposées par les étudiants depuis la page de connexion, rattachées à leur demande.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reclamation_periodes')) {
            Schema::create('reclamation_periodes', function (Blueprint $table) {
                $table->string('id_periode', 36)->primary();
                $table->string('campagne_id', 150)->index();
                $table->date('date_debut');
                $table->date('date_fin');
                $table->string('created_by', 150)->nullable();
                $table->string('updated_by', 150)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reclamations')) {
            Schema::create('reclamations', function (Blueprint $table) {
                $table->string('id_reclamation', 36)->primary();
                $table->string('numero', 30)->unique();
                $table->string('periode_id', 36)->index();
                $table->string('campagne_id', 150)->index();
                $table->string('demande_id', 150)->index();
                $table->string('etudiant_id', 150)->index();
                // Valeurs saisies par l'étudiant dans le formulaire.
                $table->string('nom_prenoms', 150);
                $table->date('date_naissance');
                $table->string('type_demande', 20);
                $table->text('motif');
                $table->string('statut', 20)->default('en_attente')->index();
                $table->text('reponse')->nullable();
                $table->string('traite_par', 150)->nullable();
                $table->string('traite_par_nom', 150)->nullable();
                $table->timestamp('traite_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamations');
        Schema::dropIfExists('reclamation_periodes');
    }
};
