<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class EdlPieceSeeder extends DumpTableSeeder
{
    protected const TABLE = 'edl_piece';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_EDL_PIECE_0'
INSERT INTO `edl_piece` (`edl_piece_id`, `id_etat_des_lieux`, `name_piece`, `ordre`, `created_by`, `updated_by`, `updated_at`, `created_at`) VALUES
('2d3d5cc6-58a2-43d8-83fd-7b8cb1aff9e3', '0d4ce841-8569-40fb-8aba-ae7435a784f5', 'Salle de bain', 3, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:31:11', '2026-08-05 15:31:11'),
('a2e7336e-a81b-4188-8923-06474143c3f3', '0d4ce841-8569-40fb-8aba-ae7435a784f5', 'Cuisine', 2, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:31:11', '2026-08-05 15:31:11'),
('87ebfffe-a32d-4d50-82c9-b96100f640ee', '50e4f9cc-e04c-4423-9a78-af5a930a4d16', 'Chambre', 1, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:25:23', '2026-08-05 15:25:23'),
('ccd7e6fa-b98d-4e77-bd49-b5e31c7b30e8', '50e4f9cc-e04c-4423-9a78-af5a930a4d16', 'Cuisine', 2, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:25:23', '2026-08-05 15:25:23'),
('6dc6d473-6158-451b-8da7-8e7f478bc21e', '50e4f9cc-e04c-4423-9a78-af5a930a4d16', 'Salle de bain', 3, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:25:23', '2026-08-05 15:25:23'),
('e518f9cd-f664-4eca-af9b-d570958dd9d9', '0d4ce841-8569-40fb-8aba-ae7435a784f5', 'Chambre', 1, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-05 15:31:11', '2026-08-05 15:31:11');
SQL_DUMP_EDL_PIECE_0,
    ];
}
