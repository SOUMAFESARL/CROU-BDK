<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class DossierTransitionsSeeder extends DumpTableSeeder
{
    protected const TABLE = 'dossier_transitions';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_DOSSIER_TRANSITIONS_0'
INSERT INTO `dossier_transitions` (`id`, `demande_id`, `ancien_etat`, `nouvel_etat`, `acteur_type`, `acteur_id`, `motif`, `details`, `created_at`) VALUES
(1, '130a5b92-86ad-4953-86d2-5d3689a11762', NULL, 'soumis', 'reprise', NULL, 'Reprise des données existantes ; aucune validation historique supplémentaire présumée.', '{\"ancien_statut\":\"En_attente\"}', '2026-09-24 09:05:55'),
(2, '596920df-512f-4178-8e6c-269b3f5cf1ea', NULL, 'attribue_definitif', 'reprise', NULL, 'Reprise des données existantes ; aucune validation historique supplémentaire présumée.', '{\"ancien_statut\":\"Accept\\u00e9e\"}', '2026-09-24 09:05:55'),
(3, 'bfb2d82e-6b3e-4c0f-889d-fd848cee838a', NULL, 'soumis', 'reprise', NULL, 'Reprise des données existantes ; aucune validation historique supplémentaire présumée.', '{\"ancien_statut\":\"En_attente\"}', '2026-09-24 09:05:55');
SQL_DUMP_DOSSIER_TRANSITIONS_0,
    ];
}
