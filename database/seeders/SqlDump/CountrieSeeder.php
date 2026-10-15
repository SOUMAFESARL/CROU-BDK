<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class CountrieSeeder extends DumpTableSeeder
{
    protected const TABLE = 'countrie';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_COUNTRIE_0'
INSERT INTO `countrie` (`id`, `name`, `code`, `created_at`, `updated_at`) VALUES
(1, 'Côte d\'Ivoire', 'CI', NULL, NULL),
(2, 'Togo', 'TG', '2025-12-31 13:09:02', '2025-12-31 13:09:02'),
(4, 'Cameroun', 'Cam', '2026-03-11 16:17:03', '2026-03-11 16:17:11');
SQL_DUMP_COUNTRIE_0,
    ];
}
