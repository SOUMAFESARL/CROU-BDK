<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** « J'aime » des étudiants sur les annonces (un par étudiant et par annonce) ; `annonces.reactions` en garde le total. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annonce_likes', function (Blueprint $table) {
            $table->id();
            $table->string('annonce_id', 64)->index();
            $table->string('etudiant_id', 64)->index();
            $table->timestamps();
            $table->unique(['annonce_id', 'etudiant_id'], 'annonce_like_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annonce_likes');
    }
};
