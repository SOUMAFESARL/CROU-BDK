<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class FiliereSeeder extends DumpTableSeeder
{
    protected const TABLE = 'filiere';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_FILIERE_0'
INSERT INTO `filiere` (`id_filiere`, `code_filiere`, `name`, `description`, `departement_code`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'nFkjEt9S2M06AWcwP04T0Few3J8NFm19JBoVimN9eSu7Vfrk9i', 'Algèbre', 'Algèbre1', '69HXXxJMnivT8eumK5vcNjN1MWpiWHuKp5QuAI7crKBVSW4UAd', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', '2026-01-14 14:52:18', '2026-01-14 14:52:27'),
(2, 'py8XxzDWB0i4thJZQmQynDcW40P9IhKZk7q4c7VJXX1Ij3CMG0', 'RTEL', 'Réseaux & Telecom', '2x8TewJizkR5HRTelW4PaQIq9LvTbNEAE9BRr2NsQILjiLhNO8', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-03-11 16:27:22', '2026-03-11 16:27:22');
SQL_DUMP_FILIERE_0,
    ];
}
