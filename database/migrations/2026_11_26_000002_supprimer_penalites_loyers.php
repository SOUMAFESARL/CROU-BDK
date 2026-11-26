<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aucune pénalité de retard ne s'applique aux loyers : les pénalités déjà calculées sont retirées du solde des loyers
 * et la pénalité journalière est remise à zéro. Opération de données non réversible (down sans effet).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('loyers') || ! Schema::hasColumn('loyers', 'total_penalites')) {
            return;
        }

        DB::table('loyers')->where('total_penalites', '>', 0)->update([
            'solde_restant' => DB::raw('CASE WHEN solde_restant - total_penalites < 0 THEN 0 ELSE solde_restant - total_penalites END'),
            'total_penalites' => 0,
        ]);

        if (Schema::hasColumn('loyers', 'penalite_journaliere')) {
            DB::table('loyers')->where('penalite_journaliere', '>', 0)->update(['penalite_journaliere' => 0]);
        }
    }

    public function down(): void
    {
        // Les pénalités retirées ne sont pas restaurées.
    }
};
