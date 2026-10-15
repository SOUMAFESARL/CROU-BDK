<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class SanctionHistoriesSeeder extends DumpTableSeeder
{
    protected const TABLE = 'sanction_histories';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_SANCTION_HISTORIES_0'
INSERT INTO `sanction_histories` (`id_historique`, `sanction_id`, `action`, `ancien_statut`, `nouveau_statut`, `details`, `auteur_id`, `created_at`) VALUES
('65ddd924-eeaa-4673-9f80-f200b859deb3', '40348a62-b83a-461c-b789-c16881f7a387', 'creation', NULL, 'active', 'Sanction blame enregistrée.', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-10 22:01:31');
SQL_DUMP_SANCTION_HISTORIES_0,
    ];
}
