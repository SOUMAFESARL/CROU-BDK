<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remplace « Daloa » par « Bondoukou » dans la configuration de l'application
 * (nom affiché sur les impressions, PDF, e-mails…) et rattache la configuration
 * à la ville de Bondoukou (id 50, Région du Gontougou id 13) au lieu de Daloa (id 62, région 16).
 */
return new class extends Migration {
    private const TEXT_COLUMNS = ['app_name', 'company_name', 'address'];

    public function up(): void
    {
        if (!Schema::hasTable('config_app')) {
            return;
        }

        $this->replaceText('Daloa', 'Bondoukou');

        if (Schema::hasColumn('config_app', 'ville_id')) {
            DB::table('config_app')->where('ville_id', '62')->update(['ville_id' => '50']);
        }
        if (Schema::hasColumn('config_app', 'region_id')) {
            DB::table('config_app')->where('region_id', '16')->update(['region_id' => '13']);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('config_app')) {
            return;
        }

        $this->replaceText('Bondoukou', 'Daloa');

        if (Schema::hasColumn('config_app', 'ville_id')) {
            DB::table('config_app')->where('ville_id', '50')->update(['ville_id' => '62']);
        }
        if (Schema::hasColumn('config_app', 'region_id')) {
            DB::table('config_app')->where('region_id', '13')->update(['region_id' => '16']);
        }
    }

    private function replaceText(string $from, string $to): void
    {
        foreach (self::TEXT_COLUMNS as $column) {
            if (Schema::hasColumn('config_app', $column)) {
                DB::table('config_app')
                    ->where($column, 'like', "%{$from}%")
                    ->update([$column => DB::raw('REPLACE('.$column.', '.DB::getPdo()->quote($from).', '.DB::getPdo()->quote($to).')')]);
            }
        }
    }
};
