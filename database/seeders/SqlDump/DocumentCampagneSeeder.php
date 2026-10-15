<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class DocumentCampagneSeeder extends DumpTableSeeder
{
    protected const TABLE = 'document_campagne';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_DOCUMENT_CAMPAGNE_0'
INSERT INTO `document_campagne` (`id_document_campagne`, `id_campagne`, `document_id`, `created_at`, `updated_at`) VALUES
('ffc5b00c-878d-48b5-92c8-3e55cf6175fc', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '1384772c-86c2-435e-a53d-105295261d0e', '2026-08-03 15:56:34', '2026-08-03 15:56:34'),
('eabaf9bf-d9fc-4ee0-b782-c1d188beb6c6', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'fda508f3-b146-49dd-899c-8ab347f5e796', '2026-08-03 15:56:34', '2026-08-03 15:56:34'),
('1b362048-b2d3-466e-acd4-e6ba506bd533', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '56ca2bfa-db92-4356-9727-08da7f904e45', '2026-08-03 15:56:34', '2026-08-03 15:56:34');
SQL_DUMP_DOCUMENT_CAMPAGNE_0,
    ];
}
