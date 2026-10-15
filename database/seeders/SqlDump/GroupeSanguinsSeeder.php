<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class GroupeSanguinsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'groupe_sanguins';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_GROUPE_SANGUINS_0'
INSERT INTO `groupe_sanguins` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'A+', '2025-11-11 16:18:39', NULL),
(2, 'A-', '2025-11-11 16:18:39', NULL),
(3, 'B+', '2025-11-11 16:18:39', NULL),
(4, 'B-', '2025-11-11 16:18:39', NULL),
(5, 'AB+', '2025-11-11 16:18:39', NULL),
(6, 'AB-', '2025-11-11 16:18:39', NULL),
(7, 'O+', '2025-11-11 16:18:39', NULL),
(8, 'O-', '2025-11-11 16:18:39', NULL);
SQL_DUMP_GROUPE_SANGUINS_0,
    ];
}
