<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class TypeEquipementSeeder extends DumpTableSeeder
{
    protected const TABLE = 'type_equipement';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_TYPE_EQUIPEMENT_0'
INSERT INTO `type_equipement` (`id_type_equipement`, `code_type`, `name`, `categorie`, `description`, `marque`, `modele`, `duree_vie_estimee`, `cout_moyen`, `cout_maintenance`, `est_consommable`, `unite_mesure`, `specifications_techniques`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('78345357-fdce-11f0-82f8-fa163e85dd73', NULL, 'Lit', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-01-30 11:15:03', '2026-01-30 11:26:07'),
('783454c5-fdce-11f0-82f8-fa163e85dd73', NULL, 'Table', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-01-30 11:14:54', '2026-01-30 11:26:07'),
('78345544-fdce-11f0-82f8-fa163e85dd73', NULL, 'Chaise', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-01-30 11:15:12', '2026-01-30 11:26:07'),
('8b004b87-0a23-4cd5-93e3-2297c3ecdf0b', NULL, 'Armoire', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-01-30 11:36:48', '2026-01-30 11:36:48'),
('c83f684e-370f-465b-b0d1-1e2f91fae2a2', NULL, 'Commode', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-01-30 11:37:48', '2026-01-30 11:37:48'),
('d3ec21d1-1fb2-405e-b849-5144baf69c93', NULL, 'Climatiseur', NULL, 'climatiseur', NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-02-06 16:36:05', '2026-02-06 16:36:05'),
('0fa67b04-7f52-45e9-882a-d0915bf1f607', NULL, 'Lavabo', NULL, 'Juste au cas où', NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-03-11 15:33:17', '2026-03-11 15:33:17'),
('a62e4005-91fe-4f37-bd88-28fb1abf81fc', NULL, 'Climatiseur', NULL, 'Climatiseur', NULL, NULL, NULL, 0.00, 0.00, 0, NULL, NULL, 1, NULL, NULL, '2026-05-19 17:34:15', '2026-05-19 17:34:15');
SQL_DUMP_TYPE_EQUIPEMENT_0,
    ];
}
