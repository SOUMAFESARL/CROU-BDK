<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Critères d'éligibilité d'une campagne (entonnoir E0 → E6, docs/notes/entonnoir-eligibilite-provisoire.pdf),
 * enregistrés en jeu de règles versionné : un brouillon au plus, une seule version « en vigueur », les autres archivées.
 * `parametres` = règles (recevabilité, préalables, catégories, admission, classement) ; `places` = places par cité (E0).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campagne_regles')) {
            return;
        }

        Schema::create('campagne_regles', function (Blueprint $t) {
            $t->string('id', 36)->primary();
            $t->string('campagne_id', 36)->index();
            $t->unsignedInteger('version');
            $t->string('statut', 20)->default('brouillon'); // brouillon | en_vigueur | archivee
            $t->json('parametres');
            $t->json('places')->nullable();
            $t->text('motif')->nullable();
            $t->string('created_by', 36)->nullable();
            $t->string('created_by_name', 150)->nullable();
            $t->string('updated_by_name', 150)->nullable();
            $t->string('applique_par', 36)->nullable();
            $t->string('applique_par_name', 150)->nullable();
            $t->timestamp('applique_le')->nullable();
            $t->timestamps();
            $t->unique(['campagne_id', 'version']);
            $t->index(['campagne_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagne_regles');
    }
};
