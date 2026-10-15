<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class MaintenanciersSeeder extends DumpTableSeeder
{
    protected const TABLE = 'maintenanciers';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_MAINTENANCIERS_0'
INSERT INTO `maintenanciers` (`id_maintenancier`, `name`, `tel`, `email`, `specialite`, `cite_id`, `notes`, `adresse`, `statut`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('9ab7fa4c-27f5-40dd-8dbd-b0249b2324f4', 'Mainenancier', '0987654321', NULL, 'multiservices', NULL, NULL, 'Abidjan', 1, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-07 17:07:26', '2026-07-07 17:07:26', NULL);
SQL_DUMP_MAINTENANCIERS_0,
    ];
}
