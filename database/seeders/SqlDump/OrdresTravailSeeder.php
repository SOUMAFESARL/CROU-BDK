<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class OrdresTravailSeeder extends DumpTableSeeder
{
    protected const TABLE = 'ordres_travail';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_ORDRES_TRAVAIL_0'
INSERT INTO `ordres_travail` (`id_ordres_travail`, `numero_ot`, `cite_id`, `batiment_id`, `chambre_id`, `etudiant_id`, `equipement_id`, `type_intervention`, `categorie`, `priorite`, `titre`, `description`, `cause`, `solution_apportee`, `date_disponibilite`, `periode_disponibilite`, `date_debut_intervention`, `date_fin_intervention`, `date_echeance`, `date_resolution`, `date_mise_a_jour`, `statut`, `assigne_a`, `assigne_par`, `instructions`, `cout_estime`, `cout_reel`, `temps_passe`, `photos`, `pieces_jointes`, `notification_envoyee`, `taux_satisfaction`, `commentaire_satisfaction`, `created_by`, `updated_by`, `deleted_by`, `deleted_at`, `created_at`, `updated_at`) VALUES
('50247af4-b86b-4706-b4da-993aa6271154', 'OT2026-0001', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', NULL, 'corrective', 'plomberie', 'moyenne', NULL, 'dsjhbnkm,f.dcx', NULL, NULL, '2026-07-08 00:00:00', 'Matin (8h-12h)', '2026-07-07 18:00:00', '2026-07-13 11:47:56', NULL, '2026-07-13 11:47:56', '2026-07-13 11:47:56', 'termine', '9ab7fa4c-27f5-40dd-8dbd-b0249b2324f4', NULL, 'test', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-07 15:05:23', '2026-07-13 11:47:56'),
('5412097f-bb90-432a-9c79-067d7b0ed917', 'OT2026-0003', '359b0f79-8bc9-4340-8c5e-3dffbbafa6b5', 'c1123cbd-658c-483a-a246-dc6726039649', '804ca038-ad2f-43dd-8a2c-2491f0f67fca', 'af4266f0-2a8f-44ae-a9b5-c97981e9b50c', NULL, 'corrective', 'plomberie', 'moyenne', NULL, 'test', NULL, NULL, '2026-07-16 00:00:00', 'Matin (8h-12h)', '2026-08-03 16:48:00', '2026-08-21 16:48:00', NULL, NULL, '2026-08-03 16:48:35', 'en_cours', '9ab7fa4c-27f5-40dd-8dbd-b0249b2324f4', NULL, 'frter', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, '2026-07-15 16:42:49', '2026-08-03 16:48:35'),
('e8c0633a-9a1c-49f4-b20b-4fdd5799c3d5', 'OT2026-0002', NULL, NULL, NULL, NULL, NULL, 'corrective', 'electricite', 'moyenne', NULL, 'sdadsdds', NULL, NULL, NULL, NULL, '2026-07-08 11:47:00', '2026-07-08 11:47:00', NULL, NULL, '2026-07-08 11:48:38', 'en_attente', '9ab7fa4c-27f5-40dd-8dbd-b0249b2324f4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'cb9995ba-6a55-11f1-aaa2-fa163e85dd73', NULL, NULL, NULL, '2026-07-08 11:48:38', '2026-07-08 11:48:38');
SQL_DUMP_ORDRES_TRAVAIL_0,
    ];
}
