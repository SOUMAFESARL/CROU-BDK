<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class TypeChambreSeeder extends DumpTableSeeder
{
    protected const TABLE = 'type_chambre';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_TYPE_CHAMBRE_0'
INSERT INTO `type_chambre` (`id_type_chambre`, `name`, `description`, `montant`, `caution`, `avance`, `superficie`, `equipement`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
('8fce98d7-fde1-11f0-82f8-fa163e85dd73', 'Individuelle', '—', 12000, 0, 6, 0, '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Climatiseur\":\"Climatiseur\"}', 1, NULL, NULL, '2026-01-19 19:33:27', '2026-07-21 14:50:09'),
('bd97dbc7-68a4-4fb8-8cc8-f1edb10baa48', 'Double', 'Pour deux personnes', 6000, 0, 6, 0, '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Ventilateur\":\"Ventilateur\"}', 1, NULL, NULL, '2026-01-30 14:15:50', '2026-07-21 14:49:57'),
('3a1a5998-d79e-44ba-82dc-bc4f9ae0823a', 'Triple', 'Pour trois personnes, il y aura trois lits', 4000, 0, 6, 0, '{\"Chaise\":\"Chaise\",\"Table\":\"Table\",\"Courant\":\"Courant\",\"Eau\":\"Eau\",\"Cuisine\":\"Cuisine\",\"Placard\":\"Placard\",\"Matelas\":\"Matelas\",\"Prise\":\"Prise\",\"Douche\":\"Douche\",\"WC\":\"WC\",\"Climatiseur\":\"Climatiseur\",\"Ventilateur\":\"Ventilateur\"}', 1, NULL, NULL, '2026-01-30 14:16:14', '2026-07-21 14:50:20');
SQL_DUMP_TYPE_CHAMBRE_0,
    ];
}
