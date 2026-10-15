<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class BatimentSeeder extends DumpTableSeeder
{
    protected const TABLE = 'batiment';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_BATIMENT_0'
INSERT INTO `batiment` (`id_batiment`, `campus_id`, `responsable_id`, `cite_id`, `statut`, `name`, `type_architecture`, `type_occupation_id`, `etat_initial`, `longitude`, `latitude`, `description`, `plan_file`, `total_chambres`, `total_etages`, `total_ailes`, `services_acces_handicape`, `services_ascenseur`, `services_gardiennage`, `is_valid`, `created_by`, `updated_by`, `deleted_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
('c1123cbd-658c-483a-a246-dc6726039649', '3c11a70c-a236-4177-a98c-a77b5c45530c', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'actif', 'BATIMENT-A', 'simple', 'db7245ea-fde0-11f0-82f8-fa163e85dd73', 'operationnel', -6.45365, 6.89706, '', NULL, 50, 5, 1, 1, 1, 1, 1, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, '2026-07-06 08:50:13', '2026-07-06 08:50:14', NULL);
SQL_DUMP_BATIMENT_0,
    ];
}
