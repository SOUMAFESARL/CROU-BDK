<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class CampagneSeeder extends DumpTableSeeder
{
    protected const TABLE = 'campagne';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_CAMPAGNE_0'
INSERT INTO `campagne` (`id_campagne`, `description`, `annee_campagne`, `annee_debut`, `annee_fin`, `is_discipline`, `is_participate`, `pourcentage_validate`, `periode_debut`, `periode_fin`, `date_echeance_demande`, `date_echeance_contrat`, `admission`, `readmission`, `statut`, `pmr`, `internationnaux`, `moyenne_min`, `redoublement`, `distance_min`, `age_L1_max`, `age_L2_max`, `age_L3_max`, `age_M1_max`, `age_M2_max`, `age_D1_max`, `age_D2_max`, `age_D3_max`, `created_by`, `updated_by`, `deleted_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
('6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', 'test test ets', '2025-2026', 2025, 2026, 1, 1, 80, '2026-07-31 00:00:00', '2026-08-31 00:00:00', '2026-09-30 00:00:00', '2026-10-21 00:00:00', 1, 1, 'terminer', 1, 1, 10, 1, 12, 18, 19, 20, 22, 23, 25, 26, 27, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, NULL, '2026-08-03 15:56:34', '2026-09-10 16:01:05');
SQL_DUMP_CAMPAGNE_0,
    ];
}
