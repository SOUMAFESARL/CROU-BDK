<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Paiements de plusieurs types enregistrés ensemble (« Nouveau paiement ») : identifiant d'encaissement commun, qui
 * permet d'établir un reçu unique. Vide pour un paiement enregistré seul.
 */
return new class extends Migration
{
    /** « Nouveau paiement » accepte plusieurs types depuis cette date : rien à regrouper avant. */
    private const DEBUT = '2026-11-23 00:00:00';

    public function up(): void
    {
        if (! Schema::hasTable('paiements') || Schema::hasColumn('paiements', 'encaissement_id')) {
            return;
        }

        Schema::table('paiements', function (Blueprint $table) {
            $table->string('encaissement_id', 36)->nullable()->index();
        });

        // Paiements déjà enregistrés ensemble : même étudiant, agent, mode de règlement et instant, types tous différents.
        DB::table('paiements')
            ->where('created_at', '>=', self::DEBUT)
            ->whereNull('deleted_at')
            ->where('statut_paiement', 'valide')
            ->get(['id_paiement', 'resident_id', 'created_by', 'methode_paiement', 'created_at', 'type_paiement', 'updated_at'])
            ->groupBy(fn ($p) => implode('|', [$p->resident_id, $p->created_by, $p->methode_paiement, $p->created_at]))
            ->filter(fn ($groupe) => $groupe->count() > 1 && $groupe->pluck('type_paiement')->unique()->count() === $groupe->count())
            ->each(function ($groupe) {
                $encaissement = (string) Str::uuid();
                foreach ($groupe as $paiement) {
                    // `updated_at` (mise à jour automatique MySQL) garde sa valeur.
                    DB::table('paiements')->where('id_paiement', $paiement->id_paiement)
                        ->update(['encaissement_id' => $encaissement, 'updated_at' => $paiement->updated_at]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('paiements') && Schema::hasColumn('paiements', 'encaissement_id')) {
            Schema::table('paiements', function (Blueprint $table) {
                $table->dropIndex(['encaissement_id']);
                $table->dropColumn('encaissement_id');
            });
        }
    }
};
