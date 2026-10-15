<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class DocumentSeeder extends DumpTableSeeder
{
    protected const TABLE = 'document';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_DOCUMENT_0'
INSERT INTO `document` (`id_document`, `name`, `slug`, `description`, `delai_validite_mois`, `format_accepte`, `taille_max_mo`, `champs_requis`, `statut`, `ordre_affiche`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('56ca2bfa-db92-4356-9727-08da7f904e45', 'CNI', 'cni', NULL, NULL, 'pdf', 10, 1, 1, 5, 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', '2026-02-25 12:06:39', '2026-03-13 09:58:57', NULL),
('61fc122e-0637-4ddc-8b0e-f781815ef3cf', 'Extrait de naissance', 'extrait-de-naissance', NULL, 3, 'pdf', 10, 1, 1, 2, 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-03-17 12:02:05', '2026-03-17 12:02:05', NULL),
('c34c7ddc-cca9-4d41-bef5-8cf93b5fc0a1', 'Passeport', 'passeport', NULL, 24, 'pdf', 10, 0, 1, 3, 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-03-17 12:18:16', '2026-03-17 12:18:16', NULL),
('fda508f3-b146-49dd-899c-8ab347f5e796', 'CMU', 'cmu', NULL, 24, 'pdf', 10, 0, 1, 4, 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-03-17 12:23:09', '2026-03-17 12:23:09', NULL),
('1384772c-86c2-435e-a53d-105295261d0e', 'Carte étudiant', 'carte-etudiant', NULL, NULL, 'pdf', 10, 0, 1, 3, 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, '2026-03-17 12:25:16', '2026-03-17 12:25:16', NULL);
SQL_DUMP_DOCUMENT_0,
    ];
}
