<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class EquipementAffectationsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'equipement_affectations';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_EQUIPEMENT_AFFECTATIONS_0'
INSERT INTO `equipement_affectations` (`id`, `equipement_id`, `cite_id`, `batiment_id`, `chambre_id`, `quantite`, `etat`, `notes`, `created_by`, `returned_at`, `created_at`, `updated_at`) VALUES
('01a0e91d-9a5a-701e-8c45-65412726d3ae', 'e7f61afa-e64f-40c1-8c6a-3884399a5c66', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', NULL, NULL, 150, 'bon', NULL, NULL, NULL, '2026-09-28 17:43:46', '2026-09-28 17:43:46');
SQL_DUMP_EQUIPEMENT_AFFECTATIONS_0,
    ];
}
