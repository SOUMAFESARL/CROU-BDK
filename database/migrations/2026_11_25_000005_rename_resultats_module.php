<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Le module « Résultats » s'appelle « Résultats d’admission » dans « Rôles et permissions », comme son menu.
 * Seul le libellé change (droits et rôles inchangés). Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->renommer('Résultats', 'Résultats d’admission');
    }

    public function down(): void
    {
        $this->renommer('Résultats d’admission', 'Résultats');
    }

    private function renommer(string $ancien, string $nouveau): void
    {
        DB::transaction(function () use ($ancien, $nouveau) {
            DB::table('modules')->where('slug', 'hebergement.resultats')->where('name', $ancien)->update(['name' => $nouveau, 'updated_at' => now()]);
            DB::table('permissions')->where('slug', 'like', 'hebergement.resultats.%')->where('module', $ancien)->update(['module' => $nouveau, 'updated_at' => now()]);
        });
    }
};
