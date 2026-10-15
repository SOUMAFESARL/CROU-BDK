<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class DepartementSeeder extends DumpTableSeeder
{
    protected const TABLE = 'departement';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_DEPARTEMENT_0'
INSERT INTO `departement` (`id_departement`, `code_departement`, `name`, `abreviation`, `description`, `campus_code`, `directeur_code`, `directeur_adjoint_code`, `adresse`, `telephone`, `email`, `site_web`, `annee_creation`, `nombre_filieres`, `nombre_enseignants`, `nombre_etudiants`, `superficie`, `laboratoires`, `equipements_principaux`, `partenariats`, `budget_annuel`, `logo`, `couleur_hex`, `statut`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '69HXXxJMnivT8eumK5vcNjN1MWpiWHuKp5QuAI7crKBVSW4UAd', 'Département math-info', 'Math-info', 'math-info', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'actif', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-01-14 13:06:30', '2026-03-11 16:30:20', NULL),
(3, 'RrFb7DMX9LZ4rLVf9H7DBTm7Qo0kxg5BYlF1nA0CGLo6p5qqhK', 'Sciences économiques', 'Sciences éco', 'Sciences de l\'économie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'actif', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, NULL, '2026-03-11 16:25:41', '2026-03-11 16:25:41', NULL),
(4, '2x8TewJizkR5HRTelW4PaQIq9LvTbNEAE9BRr2NsQILjiLhNO8', 'Département Réseaux & Telecommunications', 'RTEL', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'actif', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, NULL, '2026-03-11 16:26:42', '2026-03-11 16:26:42', NULL);
SQL_DUMP_DEPARTEMENT_0,
    ];
}
