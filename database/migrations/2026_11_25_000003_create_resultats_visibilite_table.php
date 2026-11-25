<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Campagnes dont les résultats sont consultables sur `/resultats`, cochées dans le menu « Résultats » ; aucune ligne : page
// masquée.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resultats_visibilite')) {
            Schema::create('resultats_visibilite', function (Blueprint $t) {
                $t->string('campagne_id', 36)->primary();
                $t->string('modifie_par')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resultats_visibilite');
    }
};
