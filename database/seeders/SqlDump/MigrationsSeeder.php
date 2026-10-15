<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class MigrationsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'migrations';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_MIGRATIONS_0'
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_08_10_000001_create_sanctions_tables', 1),
(2, '2026_08_10_000002_add_scope_to_sanctions_table', 2),
(3, '2026_08_10_000003_create_disciplinary_reports_tables', 3),
(4, '2026_08_10_000004_create_departure_settings_table', 4),
(5, '2026_08_10_000005_create_departure_requests_and_settlements', 5),
(6, '2026_08_11_000001_create_role_permissions_tables', 6),
(7, '2026_08_11_000002_create_modules_table', 7),
(8, '2026_08_11_000003_generate_module_permissions', 8),
(9, '2026_08_17_000001_add_student_declaration_fields_to_disciplinary_reports', 9),
(10, '2026_08_18_000001_create_activity_logs_table', 10),
(11, '2026_08_18_000002_add_activity_log_permission', 11),
(12, '2026_08_18_000003_add_allowed_ips_to_config_app', 12),
(13, '2026_09_19_000001_create_payment_providers_table', 13),
(14, '2026_09_19_000002_add_payment_provider_permission', 13),
(15, '2026_09_19_000003_add_specific_payment_credentials', 14),
(16, '2026_09_19_000004_create_online_payments_table', 15),
(17, '2026_09_20_000001_create_legal_documents_tables', 16),
(18, '2026_09_20_000002_add_legal_documents_permissions', 16),
(19, '2026_09_20_000003_add_legal_document_body_format', 17),
(20, '2026_09_20_000004_create_annonce_deliveries', 18),
(21, '2026_09_21_000001_add_pmr_confirmation_to_etudiants', 19),
(22, '2026_09_22_000001_create_lits', 20),
(23, '2026_09_23_000001_create_admission_derogations', 21),
(24, '2026_09_23_000002_create_student_contract_approvals', 22),
(25, '2026_09_23_000003_create_campaign_scorings', 23),
(26, '2026_09_23_000004_link_disciplinary_decisions_to_sanctions', 24),
(27, '2026_09_23_000005_allow_expulsion_assignment_status', 25),
(28, '2026_09_23_000006_create_dossier_workflow', 26),
(29, '2026_09_24_000001_create_housing_entry_workflow', 27),
(30, '2026_08_27_100000_create_student_api_tokens_table', 28),
(31, '2026_09_28_000002_create_equipement_affectations', 29),
(32, '2026_09_29_000001_complete_admin_permissions', 30),
(33, '2026_09_29_000002_add_admin_view_permissions', 31),
(34, '2026_10_09_000001_add_photo_to_edl_elements', 32),
(35, '2026_09_11_000001_add_statut_to_batiment_and_chambre', 33),
(36, '2026_10_12_000001_add_uploaded_file_to_legal_documents', 34),
(37, '2026_10_13_000001_add_address_to_config_app', 35);
SQL_DUMP_MIGRATIONS_0,
    ];
}
