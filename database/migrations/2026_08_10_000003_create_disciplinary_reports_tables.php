<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('disciplinary_reports')) {
            Schema::create('disciplinary_reports', function (Blueprint $table) {
                $table->uuid('id_rapport')->primary();
                $table->string('numero_rapport', 40)->unique();
                $table->string('etudiant_id', 150)->index();
                $table->string('cite_id', 150)->index();
                $table->string('batiment_id', 150)->index();
                $table->string('chambre_id', 150)->index();
                $table->dateTime('date_incident');
                $table->string('lieu', 255)->nullable();
                $table->string('nature', 100);
                $table->text('description');
                $table->text('temoins')->nullable();
                $table->enum('gravite', ['faible', 'moyenne', 'grave', 'critique'])->default('moyenne');
                $table->enum('statut', ['brouillon', 'transmis', 'en_cours', 'retourne', 'decision_prise', 'cloture'])->default('brouillon');
                $table->text('decision')->nullable();
                $table->timestamp('transmis_at')->nullable();
                $table->timestamp('pris_en_charge_at')->nullable();
                $table->timestamp('decision_at')->nullable();
                $table->string('created_by', 150)->nullable();
                $table->string('updated_by', 150)->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('disciplinary_report_histories')) {
            Schema::create('disciplinary_report_histories', function (Blueprint $table) {
                $table->uuid('id_historique')->primary();
                $table->uuid('rapport_id')->index();
                $table->string('action', 60);
                $table->string('ancien_statut', 30)->nullable();
                $table->string('nouveau_statut', 30);
                $table->text('commentaire')->nullable();
                $table->string('auteur_id', 150)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_report_histories');
        Schema::dropIfExists('disciplinary_reports');
    }
};
