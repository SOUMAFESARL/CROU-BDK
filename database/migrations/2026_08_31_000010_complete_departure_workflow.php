<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('departure_requests', function (Blueprint $table) {
            $table->string('decision', 30)->default('en_attente')->after('statut')->index();
            $table->text('motif_decision')->nullable()->after('decision');
            $table->timestamp('date_decision')->nullable()->after('motif_decision');
            $table->string('traite_par', 150)->nullable()->after('date_decision');
        });

        Schema::table('departure_settlements', function (Blueprint $table) {
            $table->decimal('caution_disponible', 12, 2)->default(0)->after('impute_caution');
            $table->decimal('caution_a_restituer', 12, 2)->default(0)->after('caution_disponible');
            $table->decimal('caution_restituee', 12, 2)->default(0)->after('caution_a_restituer');
            $table->string('mode_restitution', 50)->nullable()->after('caution_restituee');
            $table->string('paiement_restitution_id', 150)->nullable()->after('mode_restitution');
            $table->timestamp('date_restitution')->nullable()->after('paiement_restitution_id');
        });
    }

    public function down(): void
    {
        Schema::table('departure_settlements', function (Blueprint $table) {
            $table->dropColumn(['caution_disponible', 'caution_a_restituer', 'caution_restituee', 'mode_restitution', 'paiement_restitution_id', 'date_restitution']);
        });
        Schema::table('departure_requests', function (Blueprint $table) {
            $table->dropColumn(['decision', 'motif_decision', 'date_decision', 'traite_par']);
        });
    }
};
