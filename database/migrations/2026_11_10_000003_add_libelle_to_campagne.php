<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Libellé de la campagne : obligatoire dans le formulaire, vide pour les campagnes déjà créées.
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('campagne') && !Schema::hasColumn('campagne', 'libelle')) {
            Schema::table('campagne', function (Blueprint $t) {
                $t->string('libelle', 150)->nullable()->after('id_campagne');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('campagne', 'libelle')) {
            Schema::table('campagne', fn (Blueprint $t) => $t->dropColumn('libelle'));
        }
    }
};
