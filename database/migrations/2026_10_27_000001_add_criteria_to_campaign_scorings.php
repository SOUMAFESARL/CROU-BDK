<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// Critères ajoutés par l'admin au barème, en plus des quatre volets fixes : chacun s'appuie sur une donnée du dossier
// (âge, crédits validés, genre, niveau d'étude…) et attribue des points par tranche ou par valeur.
// [{"key":"c_ab12cd34ef","label":"Tranche d'âge","source":"age","weight":10,"other_points":0,
//   "bands":[{"min":null,"max":22,"points":100},{"min":22,"max":null,"points":40}],"values":[]}]
// Null pour les versions existantes : aucun critère ajouté.
return new class extends Migration {
    public function up(): void {
        if (Schema::hasColumn('campaign_scorings', 'criteria')) return;
        Schema::table('campaign_scorings', function (Blueprint $t) {
            $t->json('criteria')->nullable()->after('distance_thresholds');
        });
    }
    public function down(): void {
        if (!Schema::hasColumn('campaign_scorings', 'criteria')) return;
        Schema::table('campaign_scorings', function (Blueprint $t) { $t->dropColumn('criteria'); });
    }
};
