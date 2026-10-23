<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Idempotente : le dump de base contient déjà `departure_requests.decision` ; seules les colonnes absentes sont ajoutées. */
    public function up(): void
    {
        $this->ajouter('departure_requests', [
            'decision' => fn (Blueprint $table) => $table->string('decision', 30)->default('en_attente')->after('statut')->index(),
            'motif_decision' => fn (Blueprint $table) => $table->text('motif_decision')->nullable()->after('decision'),
            'date_decision' => fn (Blueprint $table) => $table->timestamp('date_decision')->nullable()->after('motif_decision'),
            'traite_par' => fn (Blueprint $table) => $table->string('traite_par', 150)->nullable()->after('date_decision'),
        ]);

        $this->ajouter('departure_settlements', [
            'caution_disponible' => fn (Blueprint $table) => $table->decimal('caution_disponible', 12, 2)->default(0)->after('impute_caution'),
            'caution_a_restituer' => fn (Blueprint $table) => $table->decimal('caution_a_restituer', 12, 2)->default(0)->after('caution_disponible'),
            'caution_restituee' => fn (Blueprint $table) => $table->decimal('caution_restituee', 12, 2)->default(0)->after('caution_a_restituer'),
            'mode_restitution' => fn (Blueprint $table) => $table->string('mode_restitution', 50)->nullable()->after('caution_restituee'),
            'paiement_restitution_id' => fn (Blueprint $table) => $table->string('paiement_restitution_id', 150)->nullable()->after('mode_restitution'),
            'date_restitution' => fn (Blueprint $table) => $table->timestamp('date_restitution')->nullable()->after('paiement_restitution_id'),
        ]);
    }

    /** @param  array<string, callable(Blueprint): mixed>  $colonnes */
    private function ajouter(string $table, array $colonnes): void
    {
        foreach ($colonnes as $colonne => $definition) {
            if (! Schema::hasColumn($table, $colonne)) {
                Schema::table($table, fn (Blueprint $blueprint) => $definition($blueprint));
            }
        }
    }

    public function down(): void
    {
        Schema::table('departure_settlements', function (Blueprint $table) {
            $table->dropColumn(['caution_disponible', 'caution_a_restituer', 'caution_restituee', 'mode_restitution', 'paiement_restitution_id', 'date_restitution']);
        });
        Schema::table('departure_requests', function (Blueprint $table) {
            // `decision` vient du dump de base : elle est conservée.
            $table->dropColumn(['motif_decision', 'date_decision', 'traite_par']);
        });
    }
};
