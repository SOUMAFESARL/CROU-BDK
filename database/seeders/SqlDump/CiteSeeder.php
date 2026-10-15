<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class CiteSeeder extends DumpTableSeeder
{
    protected const TABLE = 'cite';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_CITE_0'
INSERT INTO `cite` (`id_cite`, `code_cite`, `name`, `type_cite_id`, `campus_id`, `adresse`, `capacite_max`, `nombre_batiments`, `superficie`, `annee_construction`, `responsable_id`, `telephone1`, `telephone2`, `telephone3`, `email1`, `email2`, `longitude`, `latitude`, `reglement_interieur_id`, `statut`, `wifi_gratuit`, `plan`, `salle_etude`, `infirmerie`, `restaurant`, `created_by`, `deleted_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'Kalilou', 'cite kalilou', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'cite kalilou', 1000, 10, NULL, '2026', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '0701010190', NULL, NULL, 'cite@kalilou.ci', NULL, -6.45365, 6.89706, NULL, 'ouvert', 1, NULL, 1, 1, 1, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '2026-06-18 17:56:34', '2026-06-18 22:44:14', NULL);
SQL_DUMP_CITE_0,
    ];
}
