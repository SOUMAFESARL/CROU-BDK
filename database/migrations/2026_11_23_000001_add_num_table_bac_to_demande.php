<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Numéro de table au baccalauréat, demandé à l'étape « Études » du formulaire public quand le niveau précédent est la Terminale.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demande') && ! Schema::hasColumn('demande', 'num_table_bac')) {
            Schema::table('demande', function (Blueprint $t) {
                $t->string('num_table_bac', 50)->nullable()->after('ville_bac');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('demande', 'num_table_bac')) {
            Schema::table('demande', fn (Blueprint $t) => $t->dropColumn('num_table_bac'));
        }
    }
};
