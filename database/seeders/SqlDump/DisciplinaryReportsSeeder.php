<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class DisciplinaryReportsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'disciplinary_reports';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_DISCIPLINARY_REPORTS_0'
INSERT INTO `disciplinary_reports` (`id_rapport`, `numero_rapport`, `etudiant_id`, `declarant_id`, `source`, `cite_id`, `batiment_id`, `chambre_id`, `date_incident`, `lieu`, `nature`, `description`, `temoins`, `confidentiel`, `gravite`, `statut`, `decision`, `transmis_at`, `pris_en_charge_at`, `decision_at`, `created_by`, `updated_by`, `created_at`, `updated_at`, `sanction_decidee`) VALUES
('e1cdb369-4478-4fd2-9fc6-81a26098d20a', 'RDI-20260817-CIW1H', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'etudiant', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', '2026-08-17 17:00:00', 'le couloir', 'Tapage / nuisance sonore', 'testvdjkhdfhh;khfd;sljadsjkljadf.,sc', 'moi seul', 1, 'moyenne', 'en_cours', NULL, '2026-08-17 17:01:32', '2026-08-18 10:50:29', NULL, 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-17 17:01:32', '2026-08-18 10:50:29', NULL);
SQL_DUMP_DISCIPLINARY_REPORTS_0,
    ];
}
