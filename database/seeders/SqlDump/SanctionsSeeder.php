<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class SanctionsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'sanctions';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_SANCTIONS_0'
INSERT INTO `sanctions` (`id_sanction`, `numero_sanction`, `scope_type`, `etudiant_id`, `cite_id`, `batiment_id`, `chambre_id`, `type`, `motif`, `faits_reproches`, `date_decision`, `date_debut`, `date_fin`, `statut`, `commentaire`, `created_by`, `updated_by`, `created_at`, `updated_at`, `rapport_id`) VALUES
('40348a62-b83a-461c-b789-c16881f7a387', 'SAN-20260810-TS7XH', 'etudiant', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', 'blame', 'tetgdhsjzxcmn/,dmxcz/.,', 'sz;kldm,./dzxcm', '2026-08-10', NULL, NULL, 'active', 'vcghds', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-08-10 22:01:31', '2026-08-10 22:01:31', NULL);
SQL_DUMP_SANCTIONS_0,
    ];
}
