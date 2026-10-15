<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class DemandeSeeder extends DumpTableSeeder
{
    protected const TABLE = 'demande';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_DEMANDE_0'
INSERT INTO `demande` (`id_demande`, `etudiant_id`, `campagne_id`, `date_demande`, `numero_demande`, `type_hebergement_souhaite`, `annee_academique_precedent`, `annee_academique_actuel`, `niveau_etude_precedent_code`, `niveau_etude_actuel_code`, `filiere_code`, `moyenne`, `credit_valider`, `boursier`, `periode`, `statut_demande`, `commentaires`, `created_by`, `updated_by`, `created_at`, `updated_at`, `etat_dossier`, `assignation_id`) VALUES
('130a5b92-86ad-4953-86d2-5d3689a11762', 'd052d932-81c1-4065-bf7a-c6bbda59d7d1', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '2026-08-03 16:28:44', 'CROU-2026-52539', NULL, '2025-2026', '2025-2026', '3df1433f-d890-4739-ba08-ca1523f4c8de', '2ba2b3e7-b046-4160-9201-47070b58ba35', '1', 12.00, 90, 0, '2026', 'En_attente', 'Éligibilité vérifiée automatiquement : score 100% (hors documents).', NULL, NULL, '2026-08-03 16:28:44', '2026-08-03 16:28:46', 'soumis', NULL),
('596920df-512f-4178-8e6c-269b3f5cf1ea', 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '2026-08-03 15:58:57', 'CROU-2026-25228', NULL, '2025-2026', '2025-2026', '3df1433f-d890-4739-ba08-ca1523f4c8de', 'cf8b2108-0ee3-4b41-9cad-67785f1c1618', '1', 12.00, 90, 0, '2026', 'Acceptée', 'l\'attribution de chambre est effective', NULL, 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '2026-08-03 15:58:57', '2026-09-24 09:05:55', 'attribue_definitif', 'bdc6e064-477e-4bbf-97a3-233965e42a50'),
('bfb2d82e-6b3e-4c0f-889d-fd848cee838a', 'a5802ce0-35ea-4649-af43-c61ecebe4f0a', '6d4cdd23-a1d8-4c20-bb14-e0d643f0d99b', '2026-08-05 11:12:54', 'CROU-2026-27389', NULL, '2025-2026', '2025-2026', '3df1433f-d890-4739-ba08-ca1523f4c8de', 'cf8b2108-0ee3-4b41-9cad-67785f1c1618', '1', 9.00, 86, 0, '2026', 'En_attente', 'Éligibilité vérifiée automatiquement : score 88% (hors documents).', NULL, 'cf04c35b-e23a-4f1f-acc4-f05d6a227d72', '2026-08-05 11:12:54', '2026-08-05 11:13:03', 'soumis', NULL);
SQL_DUMP_DEMANDE_0,
    ];
}
