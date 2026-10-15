<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class DisciplinaryReportHistoriesSeeder extends DumpTableSeeder
{
    protected const TABLE = 'disciplinary_report_histories';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_DISCIPLINARY_REPORT_HISTORIES_0'
INSERT INTO `disciplinary_report_histories` (`id_historique`, `rapport_id`, `action`, `ancien_statut`, `nouveau_statut`, `commentaire`, `auteur_id`, `created_at`) VALUES
('34053a90-a09b-4eb8-bec0-061f9fc481e5', 'e1cdb369-4478-4fd2-9fc6-81a26098d20a', 'prendre_en_charge', 'transmis', 'en_cours', 's', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-08-18 10:50:29'),
('806c7082-d384-49dc-903c-00f0e84729f8', 'e1cdb369-4478-4fd2-9fc6-81a26098d20a', 'declaration_etudiant', NULL, 'transmis', 'Incident déclaré depuis l’espace étudiant.', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '2026-08-17 17:01:32');
SQL_DUMP_DISCIPLINARY_REPORT_HISTORIES_0,
    ];
}
