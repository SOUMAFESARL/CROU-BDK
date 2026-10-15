<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class AnnoncesSeeder extends DumpTableSeeder
{
    protected const TABLE = 'annonces';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_ANNONCES_0'
INSERT INTO `annonces` (`id_annonce`, `titre`, `contenu`, `canaux`, `audience`, `audience_cible`, `statut`, `priorite`, `date_publication`, `date_expiration`, `date_planification`, `vues`, `clics`, `reactions`, `pieces_jointes`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`, `diffusion_preparee_at`) VALUES
('6d26955b-0db6-4ba1-b31c-689a16d20dda', 'maintenance complet', '<p>test</p>', '[\"flux\"]', 'all', NULL, 'publie', 'normale', '2026-07-07 02:49:13', NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, '2026-07-07 01:33:22', '2026-09-20 20:38:24', NULL, '2026-09-20 20:38:24'),
('89706cbd-89b6-4296-b550-894d48bde438', 'maintenance complets deux', '<p>test tezt</p>', '[\"flux\",\"sms\"]', 'all', NULL, 'publie', 'normale', '2026-07-07 02:45:04', NULL, NULL, 0, 0, 0, '\"[\\\"\\\\\\/annonce\\\\\\/2026-07-07-6a4c5a24aea3b.jpg\\\"]\"', NULL, NULL, NULL, '2026-07-07 01:45:09', '2026-09-20 20:38:24', NULL, '2026-09-20 20:38:24');
SQL_DUMP_ANNONCES_0,
    ];
}
