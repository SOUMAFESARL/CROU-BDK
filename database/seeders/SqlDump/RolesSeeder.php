<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class RolesSeeder extends DumpTableSeeder
{
    protected const TABLE = 'roles';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_ROLES_0'
INSERT INTO `roles` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Superadmin', '2025-12-30 15:56:35', '2025-12-30 15:56:05'),
(2, 'Sous-DAL', '2026-09-23 08:55:13', '2026-09-23 08:55:13'),
(3, 'Chef du service logement', '2026-09-23 08:55:14', '2026-09-23 08:55:14'),
(4, 'conciege', '2026-09-28 14:09:56', '2026-09-28 14:09:56');
SQL_DUMP_ROLES_0,
    ];
}
