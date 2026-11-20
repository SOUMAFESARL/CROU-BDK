<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Publication des listes définitives : une ligne par campagne, onglet (`validees` : « Demandes validées », `attente` :
// « File d'attente ») et volet (`admission` | `readmission`). Une liste ne se télécharge qu'une fois publiée ; une
// approbation postérieure à la publication demande de la publier à nouveau.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('publication_liste_definitive')) {
            return;
        }
        Schema::create('publication_liste_definitive', function (Blueprint $t) {
            $t->string('id', 36)->primary();
            $t->string('campagne_id', 36);
            $t->string('liste', 16);
            $t->string('volet', 16);
            $t->dateTime('date_publication');
            $t->string('publiee_par', 36)->nullable();
            $t->timestamps();
            $t->unique(['campagne_id', 'liste', 'volet'], 'publication_liste_definitive_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_liste_definitive');
    }
};
