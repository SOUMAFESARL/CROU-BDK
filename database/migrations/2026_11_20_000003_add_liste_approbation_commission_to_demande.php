<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Liste d'origine de l'approbation en commission (`validees` : « Demandes validées », `attente` : « File d'attente ») :
// les listes définitives reprennent ces deux onglets.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demande') && ! Schema::hasColumn('demande', 'liste_approbation_commission')) {
            Schema::table('demande', function (Blueprint $t) {
                $t->string('liste_approbation_commission', 16)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('demande', 'liste_approbation_commission')) {
            Schema::table('demande', fn (Blueprint $t) => $t->dropColumn('liste_approbation_commission'));
        }
    }
};
