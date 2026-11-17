<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ville d'obtention du baccalauréat, saisie à l'étape « Études » du formulaire public de demande d'hébergement.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demande') && ! Schema::hasColumn('demande', 'ville_bac')) {
            Schema::table('demande', function (Blueprint $t) {
                $t->string('ville_bac', 150)->nullable()->after('credit_valider');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('demande', 'ville_bac')) {
            Schema::table('demande', fn (Blueprint $t) => $t->dropColumn('ville_bac'));
        }
    }
};
