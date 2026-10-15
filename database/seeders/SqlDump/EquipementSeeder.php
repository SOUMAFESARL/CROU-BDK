<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class EquipementSeeder extends DumpTableSeeder
{
    protected const TABLE = 'equipement';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_EQUIPEMENT_0'
INSERT INTO `equipement` (`equipement_id`, `name`, `description`, `reference`, `type_equipement_id`, `stock_initial`, `stock_actuel`, `threshold`, `photo`, `prix`, `date_reception`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('e7f61afa-e64f-40c1-8c6a-3884399a5c66', 'chaise', NULL, 'CHA', '78345544-fdce-11f0-82f8-fa163e85dd73', 500, 150, 50, NULL, 0.00, '2026-06-15 00:00:00', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, NULL, '2026-06-15 17:22:02', '2026-09-28 17:43:46', NULL);
SQL_DUMP_EQUIPEMENT_0,
    ];
}
