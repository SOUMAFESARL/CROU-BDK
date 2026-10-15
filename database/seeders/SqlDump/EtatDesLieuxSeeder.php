<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class EtatDesLieuxSeeder extends DumpTableSeeder
{
    protected const TABLE = 'etat_des_lieux';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_ETAT_DES_LIEUX_0'
INSERT INTO `etat_des_lieux` (`etat_des_lieux_id`, `id_cite`, `id_batiment`, `id_chambre`, `id_etudiant`, `type_etat`, `date_etat`, `nbre_cle_recus`, `observation_generale`, `signature_crou`, `signature_etudiant`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('0d4ce841-8569-40fb-8aba-ae7435a784f5', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'sortie', '2026-08-05 00:00:00', 1, 'dfsdf', 'signatures/21c5cb4c-2a41-46fc-9e56-6e6844301091.png', 'signatures/0b6b79b0-19a9-4609-9ad4-0cb2e4368248.png', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '2026-08-05 15:31:11', '2026-08-05 15:59:28'),
('50e4f9cc-e04c-4423-9a78-af5a930a4d16', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '1edb4ef8-3b43-4287-88c4-c1e1959e8ffa', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', 'entree', '2026-08-05 00:00:00', 12, 'dssadvfd', 'signatures/56ebc833-8e06-42ba-b66c-6d35b1537dfc.png', 'signatures/89e82521-c6c9-489f-8441-0e175326ce1b.png', 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '2026-08-05 15:21:44', '2026-08-05 15:25:23');
SQL_DUMP_ETAT_DES_LIEUX_0,
    ];
}
