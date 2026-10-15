<?php

namespace Database\Seeders\SqlDump;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

abstract class DumpTableSeeder extends Seeder
{
    protected const TABLE = '';
    protected const STATEMENTS = [];

    public function run(): void
    {
        if (static::STATEMENTS === []) {
            return; // No data was provided for this table in the source dump.
        }
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('SQL dump seeders require MySQL or MariaDB.');
        }
        if (! Schema::hasTable(static::TABLE)) {
            throw new RuntimeException('Missing table: '.static::TABLE.'. Prepare the schema first.');
        }
        if (DB::table(static::TABLE)->exists()) {
            $this->command?->warn('Skipped non-empty table: '.static::TABLE);
            return;
        }

        // Preserve session settings and explicit zero IDs from the original dump.
        $settings = DB::selectOne('SELECT @@SESSION.sql_mode AS mode, @@SESSION.foreign_key_checks AS fk');
        $modes = array_filter(explode(',', $settings->mode));
        $modes[] = 'NO_AUTO_VALUE_ON_ZERO';
        try {
            DB::statement('SET SESSION sql_mode = ?', [implode(',', array_unique($modes))]);
            DB::statement('SET SESSION foreign_key_checks = 0');
            foreach (static::STATEMENTS as $statement) {
                DB::unprepared($statement);
            }
        } finally {
            DB::statement('SET SESSION foreign_key_checks = '.(int) $settings->fk);
            DB::statement('SET SESSION sql_mode = ?', [$settings->mode]);
        }
    }
}
