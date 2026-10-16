<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('sanctions')) {
            Schema::create('sanctions', function (Blueprint $table) {
                $table->uuid('id_sanction')->primary();
                $table->string('numero_sanction', 40)->unique();
                $table->string('etudiant_id', 150)->index();
                $table->string('cite_id', 150)->nullable()->index();
                $table->enum('type', ['avertissement', 'blame', 'suspension']);
                $table->text('motif');
                $table->text('faits_reproches')->nullable();
                $table->date('date_decision');
                $table->date('date_debut')->nullable();
                $table->date('date_fin')->nullable();
                $table->enum('statut', ['active', 'levee', 'annulee'])->default('active');
                $table->text('commentaire')->nullable();
                $table->string('created_by', 150)->nullable();
                $table->string('updated_by', 150)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sanction_histories')) {
            Schema::create('sanction_histories', function (Blueprint $table) {
                $table->uuid('id_historique')->primary();
                $table->uuid('sanction_id')->index();
                $table->string('action', 60);
                $table->string('ancien_statut', 30)->nullable();
                $table->string('nouveau_statut', 30)->nullable();
                $table->text('details')->nullable();
                $table->string('auteur_id', 150)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sanction_histories');
        Schema::dropIfExists('sanctions');
    }
};
