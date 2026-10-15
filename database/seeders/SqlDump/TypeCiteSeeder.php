<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class TypeCiteSeeder extends DumpTableSeeder
{
    protected const TABLE = 'type_cite';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_TYPE_CITE_0'
INSERT INTO `type_cite` (`id_type_cite`, `code_type`, `code`, `name`, `description`, `categorie`, `public_cible`, `standard_equipements`, `services_inclus`, `conditions_acces`, `duree_min_sejour`, `duree_max_sejour`, `age_min`, `age_max`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('db724325-fde0-11f0-82f8-fa163e85dd73', NULL, NULL, 'Mixte', NULL, 'universitaire', 'etudiant', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, NULL, NULL, '2026-01-30 09:57:42', '2026-01-30 13:37:44'),
('db72456e-fde0-11f0-82f8-fa163e85dd73', NULL, NULL, 'Uniquement Homme', NULL, 'universitaire', 'etudiant', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, NULL, NULL, '2026-01-30 09:57:52', '2026-01-30 13:37:44'),
('db7245ea-fde0-11f0-82f8-fa163e85dd73', NULL, NULL, 'Uniquement Femme', NULL, 'universitaire', 'etudiant', NULL, NULL, NULL, NULL, NULL, 0, 0, 1, NULL, NULL, '2026-01-30 09:57:59', '2026-01-30 13:37:44');
SQL_DUMP_TYPE_CITE_0,
    ];
}
