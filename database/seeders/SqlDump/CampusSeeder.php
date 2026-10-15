<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class CampusSeeder extends DumpTableSeeder
{
    protected const TABLE = 'campus';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_CAMPUS_0'
INSERT INTO `campus` (`id_campus`, `code_campus`, `name`, `description`, `adresse`, `ville_id`, `region_id`, `countrie_id`, `code`, `telephone1`, `telephone2`, `telephone3`, `email1`, `email2`, `email3`, `longitude`, `latitude`, `logo`, `slogan`, `devise`, `directeur_code`, `capacite_max`, `superficie`, `date_creation`, `statut`, `created_by`, `deleted_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('3c11a70c-a236-4177-a98c-a77b5c45530c', 'Daloa_01', 'L’Université Jean Lorougnon Guédé', NULL, 'Université Jean Lorougnon Guédé', 62, 16, NULL, 'WNGuecsYGIxHuj9ye7deSnd3Obe012IXcdVDeSDForETFyCYCqJMMhmDQY7n0KWvPkGIMjGPYS1MLVLAvaTqB4UYmodCs0XeBRgvkbFmrNIY7fK2LwCzSedVNMeTp7oLMBVLqET11ML2mGhN5ImCgi', '+225 2724555409', NULL, NULL, 'info@ujlg.edu.ci', NULL, NULL, -6.4389, 6.91203, 'http://dev3.soumafe.ci/admin/assets/images/campus/6a2e2df4752616.62629474.jpg', NULL, NULL, NULL, 0, 0.00, NULL, 'actif', 'dsqjshdlhjsdhfjdsjjksqdhjqsklghsgfdlgjlqhsdhgnbnjMQSYMQKJOEANIP', NULL, NULL, '2026-06-14 04:28:36', '2026-06-14 04:28:36', NULL);
SQL_DUMP_CAMPUS_0,
    ];
}
