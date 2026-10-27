<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// Date de la validation automatique (barème) d'une campagne : vide = chaque dossier est décidé dès sa réception ;
// renseignée = les dossiers soumis attendent cette date et sont décidés en lot par `dossiers:validation-auto`.
return new class extends Migration {
    public function up(): void {
        Schema::table('campagne', function (Blueprint $t) {
            if (!Schema::hasColumn('campagne', 'date_validation_auto')) $t->date('date_validation_auto')->nullable();
            if (!Schema::hasColumn('campagne', 'validation_auto_effectuee_at')) $t->timestamp('validation_auto_effectuee_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('campagne', function (Blueprint $t) {
            foreach (['date_validation_auto', 'validation_auto_effectuee_at'] as $colonne) {
                if (Schema::hasColumn('campagne', $colonne)) $t->dropColumn($colonne);
            }
        });
    }
};
