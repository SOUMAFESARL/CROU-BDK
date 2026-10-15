<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class AssignationChambresSeeder extends DumpTableSeeder
{
    protected const TABLE = 'assignation_chambres';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_ASSIGNATION_CHAMBRES_0'
INSERT INTO `assignation_chambres` (`id_assignation`, `etudiant_id`, `chambre_id`, `campagne_id`, `date_assignation`, `date_fin`, `statut`, `type_assignation`, `commentaires`, `created_by`, `updated_by`, `created_at`, `updated_at`, `lit_id`) VALUES
('bdc6e064-477e-4bbf-97a3-233965e42a50', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '2026-08-03 16:35:48', NULL, 'confirmee', 'manuelle', NULL, NULL, NULL, '2026-08-03 16:35:38', '2026-09-21 17:24:29', 13);
SQL_DUMP_ASSIGNATION_CHAMBRES_0,
    ];
}
