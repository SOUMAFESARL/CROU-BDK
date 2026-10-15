<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class HousingEntrySettingsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'housing_entry_settings';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_HOUSING_ENTRY_SETTINGS_0'
INSERT INTO `housing_entry_settings` (`id`, `values`, `created_at`, `updated_at`) VALUES
(1, '{\"enabled\":false,\"started_at\":\"2026-10-10 09:47:01\"}', '2026-10-10 09:47:01', '2026-10-10 09:47:01');
SQL_DUMP_HOUSING_ENTRY_SETTINGS_0,
    ];
}
