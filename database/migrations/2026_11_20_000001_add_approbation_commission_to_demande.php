<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Approbation en commission de la « Liste validée en commission » : la demande approuvée rejoint la « Liste définitive ».
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demande') && ! Schema::hasColumn('demande', 'date_approbation_commission')) {
            Schema::table('demande', function (Blueprint $t) {
                $t->timestamp('date_approbation_commission')->nullable();
                $t->string('approbation_commission_par', 64)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('demande', 'date_approbation_commission')) {
            Schema::table('demande', fn (Blueprint $t) => $t->dropColumn(['date_approbation_commission', 'approbation_commission_par']));
        }
    }
};
