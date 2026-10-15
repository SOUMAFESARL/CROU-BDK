<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class InterventionsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'interventions';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_INTERVENTIONS_0'
INSERT INTO `interventions` (`id_intervention`, `id_ordres_travail`, `date_intervention`, `duree`, `description`, `technicien_id`, `equipe`, `materiel_utilise`, `pieces_detachees`, `resultat`, `remarques`, `photos_avant`, `photos_apres`, `date_creation`, `date_mise_a_jour`, `taux_satisfaction`, `commentaire_satisfaction`, `date_satisfaction`, `created_by`, `updated_by`, `deleted_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
('5dce42bf-b77d-4075-902b-2defa7456246', '50247af4-b86b-4706-b4da-993aa6271154', '2026-07-13 11:30:00', 45, 'sa', NULL, NULL, NULL, NULL, 'resolu', NULL, NULL, NULL, '2026-07-13 11:40:37', '2026-07-13 11:40:37', NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, NULL, '2026-07-13 11:40:37', '2026-07-13 11:40:37'),
('9cd061ab-d565-42b6-8ec7-0fc64959c5d5', '50247af4-b86b-4706-b4da-993aa6271154', '2026-07-13 11:30:00', 45, 'sa', NULL, NULL, NULL, NULL, 'resolu', NULL, NULL, NULL, '2026-07-13 11:40:51', '2026-07-13 11:40:51', NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, NULL, '2026-07-13 11:40:51', '2026-07-13 11:40:51'),
('ef107d86-ab78-431f-9046-c6a18962a20f', '50247af4-b86b-4706-b4da-993aa6271154', '2026-07-13 11:30:00', 45, 'sa', NULL, NULL, NULL, NULL, 'resolu', NULL, NULL, NULL, '2026-07-13 11:39:06', '2026-09-14 16:04:55', 4, 'test eytsdhjb', '2026-09-14 16:04:55', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-13 11:39:06', '2026-09-14 16:04:55');
SQL_DUMP_INTERVENTIONS_0,
    ];
}
