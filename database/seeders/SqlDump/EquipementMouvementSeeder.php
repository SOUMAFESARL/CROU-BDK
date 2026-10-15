<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class EquipementMouvementSeeder extends DumpTableSeeder
{
    protected const TABLE = 'equipement_mouvement';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_EQUIPEMENT_MOUVEMENT_0'
INSERT INTO `equipement_mouvement` (`equipement_mouvement_id`, `equipement_id`, `type`, `quantity`, `prix`, `reason`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('5662386e-2370-4bca-a3f0-d547f0207588', 'e7f61afa-e64f-40c1-8c6a-3884399a5c66', 'entry', 500, 0, 'Entree de stock ', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-06-15 17:22:02', '2026-06-15 17:22:02'),
('007cbdde-cc1c-46da-b0aa-e979a561cb5e', 'e7f61afa-e64f-40c1-8c6a-3884399a5c66', 'exit', 200, 0, 'attribution des chambres', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-06-15 17:22:43', '2026-06-15 17:22:43'),
('6e66b413-0b44-469e-b850-a48b4e1aee1a', 'e7f61afa-e64f-40c1-8c6a-3884399a5c66', 'exit', 150, 0, 'Affectation au parc 01a0e91d-9a5a-701e-8c45-65412726d3ae', NULL, NULL, '2026-09-28 17:43:46', '2026-09-28 17:43:46');
SQL_DUMP_EQUIPEMENT_MOUVEMENT_0,
    ];
}
