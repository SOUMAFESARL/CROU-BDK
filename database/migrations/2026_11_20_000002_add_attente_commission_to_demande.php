<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mise en attente par la commission depuis une liste provisoire : la demande rejoint la « Décision de la commission »
// sans changer l'état du dossier.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demande') && ! Schema::hasColumn('demande', 'date_attente_commission')) {
            Schema::table('demande', function (Blueprint $t) {
                $t->timestamp('date_attente_commission')->nullable();
                $t->string('attente_commission_par', 64)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('demande', 'date_attente_commission')) {
            Schema::table('demande', fn (Blueprint $t) => $t->dropColumn(['date_attente_commission', 'attente_commission_par']));
        }
    }
};
