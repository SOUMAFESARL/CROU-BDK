<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class PaiementRepartitionsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'paiement_repartitions';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_PAIEMENT_REPARTITIONS_0'
INSERT INTO `paiement_repartitions` (`id_repartition`, `paiement_id`, `type`, `montant`, `mois`, `annee`, `description`, `created_at`, `updated_at`) VALUES
('019f9409-6438-702c-91f1-24cf4dfa6597', '66b1b3ca-d0b0-4230-a97c-92db417932d6', 'mois_cours', 6000.00, '08', '2026', 'Paiement du mois en cours', '2026-07-24 12:11:11', '2026-07-24 12:11:11'),
('019f9410-a3fd-73fa-8213-f7fc32e17b1a', 'd5848c1f-0a17-4121-9a3b-2abb50dee4af', 'mois_cours', 6000.00, '09', '2026', 'Paiement du mois en cours', '2026-07-24 12:19:06', '2026-07-24 12:19:06'),
('019f9414-9150-7302-8379-53b7a374232e', 'ce172d90-42e9-475f-8b82-cd193a4d666e', 'mois_cours', 6000.00, '10', '2026', 'Paiement du mois en cours', '2026-07-24 12:23:23', '2026-07-24 12:23:23'),
('019f9418-ab04-71b1-93b5-9c7c255c21ba', '4ebf9aa7-2b01-46f0-984d-cffbe1c19c33', 'mois_cours', 6000.00, '11', '2026', 'Paiement du mois en cours', '2026-07-24 12:27:52', '2026-07-24 12:27:52'),
('019f9419-0ce2-7104-b139-101cea8bc6cb', '2d73b8d9-ba99-46fc-9547-f01fbd75eeaf', 'mois_cours', 6000.00, '12', '2026', 'Paiement du mois en cours', '2026-07-24 12:28:17', '2026-07-24 12:28:17'),
('019f941a-862f-7125-96a8-11c28a4f4a2b', 'a85f1bec-df0a-4273-a7e1-8be1f1f55da3', 'mois_cours', 6000.00, '01', '2027', 'Paiement du mois en cours', '2026-07-24 12:29:54', '2026-07-24 12:29:54'),
('019f9420-9be9-7245-9896-95e2476f1cfe', '0f0e1551-018c-4fda-80ac-d0457dabb1ac', 'mois_cours', 6000.00, '02', '2027', 'Paiement du mois en cours', '2026-07-24 12:36:32', '2026-07-24 12:36:32'),
('019f9488-0e0f-7189-953c-618ae0bd34a7', '0b77853c-82ef-4a63-83d4-e15de74137af', 'mois_cours', 6000.00, '03', '2027', 'Paiement du mois en cours', '2026-07-24 14:29:32', '2026-07-24 14:29:32'),
('019f9488-4387-732f-a3eb-28ba4c2f6361', '9ba90227-09cc-44c8-a767-da7217fab45f', 'mois_cours', 6000.00, '04', '2027', 'Paiement du mois en cours', '2026-07-24 14:29:45', '2026-07-24 14:29:45'),
('019f9488-4388-73e9-ae8a-79489b13811c', '9ba90227-09cc-44c8-a767-da7217fab45f', 'avance', 6000.00, '5', '2027', 'Paiement anticipé - loyer_id 019f9488-4385-72cb-bd95-a253bbb5fde6', '2026-07-24 14:29:45', '2026-07-24 14:29:45'),
('019f9490-8bfb-725c-ab94-c6b84e7fb30f', 'a8918c28-b0e2-43a7-8514-50baa76fb0c4', 'mois_cours', 6000.00, '06', '2027', 'Paiement du mois en cours', '2026-07-24 14:38:48', '2026-07-24 14:38:48'),
('019f9491-45c7-729c-9111-f20efab32950', '6a6a7eff-380e-45df-aa9b-05e3858f06fd', 'mois_cours', 6000.00, '07', '2027', 'Paiement du mois en cours', '2026-07-24 14:39:36', '2026-07-24 14:39:36'),
('019f9492-0875-71a7-b884-4cb057775651', 'aadd8b66-fa09-4984-9a41-e83b3dc5bb49', 'mois_cours', 6000.00, '08', '2027', 'Paiement du mois en cours', '2026-07-24 14:40:26', '2026-07-24 14:40:26');
SQL_DUMP_PAIEMENT_REPARTITIONS_0,
    ];
}
