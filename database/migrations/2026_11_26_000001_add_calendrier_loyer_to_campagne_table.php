<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calendrier des loyers d'une campagne (onglet « Calendrier des loyers » de Paiements) : les 10 mois de loyer
 * et la date d'échéance de chacun, `[{"mois": "aaaa-mm", "echeance": "aaaa-mm-jj"}, …]` (App\Services\CalendrierLoyer).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campagne') && ! Schema::hasColumn('campagne', 'calendrier_loyer')) {
            Schema::table('campagne', function (Blueprint $table) {
                $table->json('calendrier_loyer')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('campagne') && Schema::hasColumn('campagne', 'calendrier_loyer')) {
            Schema::table('campagne', function (Blueprint $table) {
                $table->dropColumn('calendrier_loyer');
            });
        }
    }
};
