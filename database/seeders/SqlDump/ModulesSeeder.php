<?php

namespace Database\Seeders\SqlDump;

// Generated from kkfvkcpk_croudedaloa_db (2).sql; regenerate with scripts/generate-dump-seeders.py.
class ModulesSeeder extends DumpTableSeeder
{
    protected const TABLE = 'modules';
    protected const STATEMENTS = [
        <<<'SQL_DUMP_MODULES_0'
INSERT INTO `modules` (`id`, `parent_id`, `name`, `slug`, `description`, `icon`, `route_name`, `menu_key`, `submenu_key`, `position`, `level`, `actions`, `is_active`, `show_in_menu`, `is_system`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Tableau de bord', 'dashboard', NULL, 'fa-solid fa-table-columns', 'admin.dashboard.index', 'dashboard', NULL, 1, 0, '[\"view\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:28'),
(2, NULL, 'Utilisateurs', 'utilisateurs', NULL, 'fa-solid fa-users', 'admin.users.index', 'users', NULL, 2, 0, '[\"view\",\"create\",\"delete\",\"update\",\"personal\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 17:45:40'),
(3, NULL, 'Gestion Hébergement', 'hebergement', NULL, 'fa-solid fa-building', 'admin.hebergement.index', 'hebergement', NULL, 3, 0, '[\"view\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(4, NULL, 'Paramétrage général', 'parametrage', NULL, 'fa-solid fa-gear', 'admin.parametrage.index', 'parametres', NULL, 4, 0, '[\"view\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(5, 3, 'Parc immobilier', 'hebergement.parc', NULL, 'fa-solid fa-building', 'admin.hebergement.parc-immobilier.index', 'hebergement', 'parc-immobilier', 1, 1, '[\"view\",\"create\",\"update\",\"delete\",\"export\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(6, 3, 'Équipements', 'hebergement.equipements', NULL, 'fa-solid fa-toolbox', 'admin.hebergement.equipements.index', 'hebergement', 'equipements', 2, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(7, 3, 'Campagnes et admissions', 'hebergement.admissions', NULL, 'fa-solid fa-bullhorn', 'admin.hebergement.campagnes_admission.admission', 'hebergement', 'campagnes', 3, 1, '[\"view\",\"create\",\"update\",\"validate\",\"personal\",\"documents\",\"history\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 17:45:39'),
(8, 3, 'Attribution des chambres', 'hebergement.attributions', NULL, 'fa-solid fa-bed', 'admin.allocation.index', 'hebergement', 'residents', 4, 1, '[\"view\",\"assign\",\"validate\",\"export\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:28'),
(9, 3, 'Résidents et contrats', 'hebergement.residents', NULL, 'fa-solid fa-users', 'admin.hebergement.residents_contrat.admission', 'hebergement', 'residents', 5, 1, '[\"view\",\"create\",\"update\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(10, 3, 'Vie résidentielle', 'hebergement.vie-residentielle', NULL, 'fa-solid fa-city', 'admin.hebergement.vie_residentielle.index', 'hebergement', 'vie-cite', 6, 1, '[\"view\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(11, 3, 'Départs et états des lieux', 'hebergement.departs', NULL, 'fa-solid fa-door-open', 'admin.hebergement.depart_etat_des_lieux.depart', 'hebergement', 'departs', 7, 1, '[\"view\",\"create\",\"update\",\"close\",\"export\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:28'),
(12, 3, 'Paiements des loyers', 'hebergement.paiements', NULL, 'fa-solid fa-money-bill-wave', 'admin.hebergement.paiements.index', 'hebergement', 'finances', 8, 1, '[\"view\",\"create\",\"validate\",\"export\",\"update\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(13, 3, 'Reporting et statistiques', 'hebergement.reporting', NULL, 'fa-solid fa-chart-bar', 'admin.hebergement.reporting.index', 'hebergement', 'reporting', 9, 1, '[\"view\",\"export\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(14, 3, 'Paramétrage hébergement', 'hebergement.parametrage', NULL, 'fa-solid fa-tools', 'admin.hebergement.parametrage-herb.index', 'hebergement', 'parametrage-herb', 10, 1, '[\"view\",\"configure\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(15, 4, 'Gestion des modules', 'parametrage.modules', NULL, 'fa-solid fa-sitemap', 'admin.parametrage.modules.index', 'parametres', 'modules', 1, 1, '[\"view\",\"create\",\"update\",\"delete\",\"configure\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(16, 4, 'Rôles et permissions', 'parametrage.roles', NULL, 'fa-solid fa-user-shield', 'admin.parametrage.roles-permissions.index', 'parametres', 'roles', 2, 1, '[\"view\",\"create\",\"update\",\"delete\",\"assign\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(17, 4, 'gestions des pays', 'parametrage.countrie', NULL, 'fa-solid fa-list', 'admin.parametrage.countrie.index', 'parametres', 'referentiels', 3, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-08-11 11:53:03'),
(18, 4, 'Configuration système', 'parametrage.configuration', NULL, 'fa-solid fa-scroll', 'admin.parametrage.configuration.index', 'parametres', 'parametrage', 9, 1, '[\"view\",\"configure\"]', 1, 1, 1, '2026-08-11 08:47:14', '2026-09-29 11:16:29'),
(19, 4, 'Gestions des regions', 'parametrage.region', NULL, 'fa-solid fa-map', 'admin.parametrage.region.index', 'parametres', 'regions', 4, 1, '[\"view\",\"create\",\"update\"]', 1, 1, 0, '2026-08-11 10:35:17', '2026-08-11 11:53:24'),
(20, 4, 'Gestions des villes', 'parametrage.ville', NULL, 'fa-solid fa-city', 'admin.parametrage.ville.index', 'parametres', 'villes', 5, 1, '[\"view\",\"create\",\"update\"]', 1, 1, 0, '2026-08-11 10:42:19', '2026-08-11 11:53:43'),
(21, 4, 'Gestion des niveaux d’études', 'parametrage.niveau', NULL, 'fa-solid fa-graduation-cap', 'admin.parametrage.niveau-etude.index', 'parametres', 'niveaux-etude', 6, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 0, '2026-08-11 11:42:29', '2026-08-11 11:54:02'),
(22, 4, 'geation des filières', 'parametrage.filiere', NULL, 'fa-solid fa-diagram-project', 'admin.parametrage.filiere.index', 'parametres', 'filieres', 7, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 0, '2026-08-11 11:44:14', '2026-08-11 11:54:26'),
(23, 4, 'Gestion des départements', 'parametrage.departement', NULL, 'fa-solid fa-layer-group', 'admin.parametrage.departement.index', 'parametres', 'departements', 8, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 0, '2026-08-11 11:46:52', '2026-08-11 11:54:49'),
(24, 4, 'Gestion des types d\'utilisateurs', 'parametrage.type-utilisateur', NULL, 'fa-solid fa-user-gear', 'admin.parametrage.type-utilisateur.index', 'parametres', 'type-utilisateur', 10, 1, '[\"view\",\"create\",\"update\",\"delete\"]', 1, 1, 0, '2026-08-11 11:50:26', '2026-08-11 11:55:25'),
(25, NULL, 'Journal d\'activité', 'activity', 'Consulter les actions enregistrées dans l’application.', 'fa-solid fa-clock-rotate-left', 'admin.activity-logs.index', 'activity', NULL, 5, 0, '[\"view\"]', 1, 1, 1, '2026-08-18 10:14:01', '2026-09-29 11:16:28'),
(26, 4, 'API de paiement', 'parametrage.payment-providers', 'Configurer les réseaux de paiement et leur visibilité étudiant.', 'fa-solid fa-credit-card', 'admin.parametrage.payment-providers.index', 'parametres', 'payment-providers', 15, 1, '[\"configure\"]', 1, 1, 1, '2026-09-19 20:13:51', '2026-09-29 11:16:29'),
(27, 4, 'Signatures et documents', 'parametrage.legal-documents', 'Configurer les signataires et rédiger les documents de la résidence.', 'fa-solid fa-file-signature', 'admin.parametrage.legal-documents.index', 'parametres', 'legal-documents', 16, 1, '[\"manage\",\"signatures\"]', 1, 1, 1, '2026-09-20 11:00:09', '2026-09-29 11:16:29'),
(28, 7, 'Dérogations d’admission', 'hebergement.admissions.derogations', NULL, NULL, NULL, NULL, NULL, 0, 2, '[\"validate\"]', 1, 0, 1, '2026-09-23 08:55:12', '2026-09-29 11:16:28'),
(29, 3, 'Annonces', 'hebergement.annonces', NULL, NULL, NULL, NULL, NULL, 0, 1, '[\"create\",\"delete\",\"update\",\"validate\",\"view\"]', 1, 0, 1, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(30, 3, 'Discipline', 'hebergement.discipline', NULL, NULL, NULL, NULL, NULL, 0, 1, '[\"create\",\"update\",\"view\"]', 1, 0, 1, '2026-09-29 11:16:28', '2026-09-29 11:16:28'),
(31, 3, 'Maintenance', 'hebergement.maintenance', NULL, NULL, NULL, NULL, NULL, 0, 1, '[\"assign\",\"create\",\"update\",\"view\"]', 1, 0, 1, '2026-09-29 11:16:29', '2026-09-29 11:16:29'),
(32, 4, 'parametrage.referentiels', 'parametrage.referentiels', NULL, NULL, NULL, NULL, NULL, 0, 1, '[\"create\",\"delete\",\"update\",\"view\"]', 1, 0, 1, '2026-09-29 11:16:29', '2026-09-29 11:16:29');
SQL_DUMP_MODULES_0,
    ];
}
