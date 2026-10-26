<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// Tranches de distance domicile–campus paramétrables par barème : {"proche":50,"loin":100,"tres_loin":200} (km).
// Null pour les versions existantes : les seuils par défaut (50 / 100 / 200 km) s'appliquent.
return new class extends Migration {
    public function up(): void {
        if (Schema::hasColumn('campaign_scorings', 'distance_thresholds')) return;
        Schema::table('campaign_scorings', function (Blueprint $t) {
            $t->json('distance_thresholds')->nullable()->after('points');
        });
    }
    public function down(): void {
        if (!Schema::hasColumn('campaign_scorings', 'distance_thresholds')) return;
        Schema::table('campaign_scorings', function (Blueprint $t) { $t->dropColumn('distance_thresholds'); });
    }
};
