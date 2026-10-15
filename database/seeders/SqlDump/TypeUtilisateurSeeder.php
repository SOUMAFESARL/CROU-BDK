<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class TypeUtilisateurSeeder extends DumpTableSeeder
{
    protected const TABLE = 'type_utilisateur';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_TYPE_UTILISATEUR_0'
INSERT INTO `type_utilisateur` (`id`, `name`, `created_at`, `updated_at`) VALUES
(7, 'Enseignant', '2026-03-11 16:29:46', '2026-03-11 16:29:46'),
(2, 'Administratif', '2025-12-29 21:42:13', '2026-03-11 16:28:47'),
(3, 'Technique', '2025-12-29 21:42:13', '2026-03-11 16:29:04'),
(4, 'Sécurité', '2025-12-29 21:42:13', '2026-03-11 16:28:33'),
(6, 'Directrice de logements', '2026-01-16 09:10:07', '2026-03-12 10:44:52');
SQL_DUMP_TYPE_UTILISATEUR_0,
    ];
}
