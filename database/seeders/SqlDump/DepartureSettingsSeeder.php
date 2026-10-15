<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class DepartureSettingsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'departure_settings';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_DEPARTURE_SETTINGS_0'
INSERT INTO `departure_settings` (`id`, `notice_days`, `late_notice_penalty`, `unpaid_blocking_threshold`, `damage_retention_grid`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 30, 0.00, 0.00, '[]', NULL, '2026-08-10 23:41:20', '2026-08-10 23:41:20');
SQL_DUMP_DEPARTURE_SETTINGS_0,
    ];
}
