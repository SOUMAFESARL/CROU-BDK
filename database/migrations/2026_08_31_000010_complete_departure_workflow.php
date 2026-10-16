<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('departure_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('departure_requests', 'decision')) {
                $table->string('decision', 30)->default('en_attente')->after('statut')->index();
            }
            if (! Schema::hasColumn('departure_requests', 'motif_decision')) {
                $table->text('motif_decision')->nullable()->after('decision');
            }
            if (! Schema::hasColumn('departure_requests', 'date_decision')) {
                $table->timestamp('date_decision')->nullable()->after('motif_decision');
            }
            if (! Schema::hasColumn('departure_requests', 'traite_par')) {
                $table->string('traite_par', 150)->nullable()->after('date_decision');
            }
        });

        Schema::table('departure_settlements', function (Blueprint $table) {
            if (! Schema::hasColumn('departure_settlements', 'caution_disponible')) {
                $table->decimal('caution_disponible', 12, 2)->default(0)->after('impute_caution');
            }
            if (! Schema::hasColumn('departure_settlements', 'caution_a_restituer')) {
                $table->decimal('caution_a_restituer', 12, 2)->default(0)->after('caution_disponible');
            }
            if (! Schema::hasColumn('departure_settlements', 'caution_restituee')) {
                $table->decimal('caution_restituee', 12, 2)->default(0)->after('caution_a_restituer');
            }
            if (! Schema::hasColumn('departure_settlements', 'mode_restitution')) {
                $table->string('mode_restitution', 50)->nullable()->after('caution_restituee');
            }
            if (! Schema::hasColumn('departure_settlements', 'paiement_restitution_id')) {
                $table->string('paiement_restitution_id', 150)->nullable()->after('mode_restitution');
            }
            if (! Schema::hasColumn('departure_settlements', 'date_restitution')) {
                $table->timestamp('date_restitution')->nullable()->after('paiement_restitution_id');
            }
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
