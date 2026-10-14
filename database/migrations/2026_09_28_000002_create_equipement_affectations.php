<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipement_affectations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('equipement_id', 150)->index();
            $t->string('cite_id', 150)->index();
            $t->string('batiment_id', 150)->nullable()->index();
            $t->string('chambre_id', 150)->nullable()->index();
            $t->unsignedInteger('quantite');
            $t->string('etat', 30);
            $t->text('notes')->nullable();
            $t->string('created_by', 150)->nullable();
            $t->timestamp('returned_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('equipement_affectations'); }
};
