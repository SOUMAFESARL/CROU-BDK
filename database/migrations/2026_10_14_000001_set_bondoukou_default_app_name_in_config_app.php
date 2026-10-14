<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Valeur par défaut de `config_app.app_name` : « Crou de Bondoukou » au lieu de
 * « Crou de Daloa » (schéma hérité du dump SQL). Les valeurs existantes ont
 * déjà été renommées par 2026_10_13_000002_rename_daloa_to_bondoukou_in_config_app.
 */
return new class extends Migration {
    public function up(): void
    {
        $this->setDefault('Crou de Bondoukou');
    }

    public function down(): void
    {
        $this->setDefault('Crou de Daloa');
    }

    private function setDefault(string $value): void
    {
        // ALTER … SET DEFAULT n'existe pas en SQLite (tests) : MySQL uniquement.
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasColumn('config_app', 'app_name')) {
            return;
        }

        DB::statement('ALTER TABLE `config_app` ALTER COLUMN `app_name` SET DEFAULT '.DB::getPdo()->quote($value));
    }
};
