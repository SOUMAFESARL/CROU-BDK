<?php

/*
|--------------------------------------------------------------------------
| Menu principal de l'espace admin (barre latérale React)
|--------------------------------------------------------------------------
|
| Définition déclarative du menu (voir docs/refonte-react-admin.md §5.3).
| Chaque entrée est filtrée côté serveur par AdminAccess avant d'être
| partagée avec Inertia : un lien non autorisé n'est jamais envoyé au
| navigateur. `icon` est le nom d'une icône Lucide déclarée dans
| resources/js/components/layout/app-sidebar.tsx. `active` liste les motifs
| de noms de routes qui rendent l'entrée active.
|
| `children` (facultatif) : sous-menu affiché dans la barre latérale
| (déplié quand la rubrique est active, menu flottant en mode replié) pour
| passer d'un module à l'autre sans revenir à la page d'accueil. Mêmes clés
| (+ `params` : paramètres de route) ; `color` et `description` servent aux
| cartes des pages d'accueil de module (admin/hebergement/index.blade.php,
| admin/parametrage/index.blade.php).
|
*/

return [
    [
        'label' => 'Tableau de bord',
        'icon' => 'layout-dashboard',
        'route' => 'admin.dashboard.index',
        'active' => ['admin.dashboard.*'],
    ],
    [
        'label' => 'Utilisateurs',
        'icon' => 'users',
        'route' => 'admin.users.index',
        'active' => ['admin.users.*'],
    ],
    [
        'label' => 'Gestion Hébergement',
        'icon' => 'building-2',
        'route' => 'admin.hebergement.index',
        'active' => ['admin.hebergement.*', 'admin.allocation.*', 'admin.housing-entry.*', 'admin.map'],
        // Ordre du cycle de vie : parc → admission → attribution → séjour → départ → pilotage.
        'children' => [
            ['label' => 'Parc immobilier', 'icon' => 'building', 'route' => 'admin.hebergement.parc-immobilier.index', 'active' => ['admin.hebergement.parc-immobilier.*', 'admin.map'], 'color' => 'green', 'description' => 'Cités, bâtiments, chambres'],
            ['label' => 'Équipements', 'icon' => 'toolbox', 'route' => 'admin.hebergement.equipements.index', 'active' => ['admin.hebergement.equipements.*'], 'color' => 'amber', 'description' => 'Inventaire et affectations'],
            ['label' => 'Campagnes & admission', 'icon' => 'clipboard-list', 'route' => 'admin.hebergement.campagnes_admission.admission', 'active' => ['admin.hebergement.campagnes_admission.*'], 'color' => 'purple', 'description' => 'Dossiers de candidature'],
            ['label' => 'Attribution des chambres', 'icon' => 'bed-double', 'route' => 'admin.allocation.index', 'active' => ['admin.allocation.*'], 'color' => 'green', 'description' => 'Affecter les lits'],
            ['label' => 'Résidents & contrats', 'icon' => 'users', 'route' => 'admin.hebergement.residents_contrat.admission', 'params' => ['etat' => 'residents'], 'active' => ['admin.hebergement.residents_contrat.*'], 'color' => 'green', 'description' => 'Suivi des résidents'],
            ['label' => 'Paiements loyer', 'icon' => 'wallet', 'route' => 'admin.hebergement.paiements.index', 'active' => ['admin.hebergement.paiements.*'], 'color' => 'green', 'description' => 'Encaissements et soldes'],
            ['label' => 'Vie résidentielle', 'icon' => 'houses', 'route' => 'admin.hebergement.vie_residentielle.index', 'active' => ['admin.hebergement.vie_residentielle.index'], 'color' => 'amber', 'description' => 'Vie dans les cités'],
            ['label' => 'Annonces', 'icon' => 'megaphone', 'route' => 'admin.hebergement.annonce.index', 'active' => ['admin.hebergement.annonce.*'], 'color' => 'amber', 'description' => 'Diffuser une information'],
            ['label' => 'Discipline', 'icon' => 'shield-alert', 'route' => 'admin.hebergement.vie_residentielle.incidents.index', 'active' => ['admin.hebergement.vie_residentielle.incidents.*', 'admin.hebergement.vie_residentielle.sanctions.*'], 'color' => 'purple', 'description' => 'Incidents et sanctions'],
            ['label' => 'Maintenance', 'icon' => 'wrench', 'route' => 'admin.hebergement.maintenance.index', 'active' => ['admin.hebergement.maintenance.*'], 'color' => 'red', 'description' => 'Interventions et pannes'],
            ['label' => 'Départs & état des lieux', 'icon' => 'door-open', 'route' => 'admin.hebergement.depart_etat_des_lieux.depart', 'active' => ['admin.hebergement.depart_etat_des_lieux.*'], 'color' => 'red', 'description' => 'Sorties et inventaires'],
            ['label' => 'Reporting & statistiques', 'icon' => 'chart-column', 'route' => 'admin.hebergement.reporting.index', 'active' => ['admin.hebergement.reporting.*'], 'color' => 'purple', 'description' => 'Indicateurs du module'],
            ['label' => 'Paramétrage', 'icon' => 'sliders-horizontal', 'route' => 'admin.hebergement.parametrage-herb.index', 'active' => ['admin.hebergement.parametrage-herb.*', 'admin.housing-entry.*'], 'color' => 'amber', 'description' => 'Configuration du module'],
        ],
    ],
    [
        'label' => 'Journal d’activité',
        'icon' => 'history',
        'route' => 'admin.activity-logs.index',
        'active' => ['admin.activity-logs.*'],
    ],
    [
        'label' => 'Paramètres',
        'icon' => 'settings',
        'route' => 'admin.parametrage.index',
        'active' => ['admin.parametrage.*'],
        'children' => [
            ['label' => 'Documents de la résidence', 'icon' => 'file-pen-line', 'route' => 'admin.parametrage.legal-documents.index', 'active' => ['admin.parametrage.legal-documents.*'], 'color' => 'green', 'description' => 'Règlement et documents légaux'],
            ['label' => 'API de paiement', 'icon' => 'credit-card', 'route' => 'admin.parametrage.payment-providers.index', 'active' => ['admin.parametrage.payment-providers.*'], 'color' => 'amber', 'description' => 'Fournisseurs de paiement en ligne'],
            ['label' => 'Gestion des modules', 'icon' => 'network', 'route' => 'admin.parametrage.modules.index', 'active' => ['admin.parametrage.modules.*'], 'color' => 'purple', 'description' => 'Activer les modules'],
            ['label' => 'Rôles et permissions', 'icon' => 'shield-check', 'route' => 'admin.parametrage.roles-permissions.index', 'active' => ['admin.parametrage.roles-permissions.*'], 'color' => 'red', 'description' => 'Droits d’accès des administrateurs'],
            ['label' => 'Pays', 'icon' => 'flag', 'route' => 'admin.parametrage.countrie.index', 'active' => ['admin.parametrage.countrie.*'], 'color' => 'green', 'description' => 'Liste des pays'],
            ['label' => 'Régions', 'icon' => 'map', 'route' => 'admin.parametrage.region.index', 'active' => ['admin.parametrage.region.*'], 'color' => 'amber', 'description' => 'Découpage régional'],
            ['label' => 'Villes', 'icon' => 'map-pin', 'route' => 'admin.parametrage.ville.index', 'active' => ['admin.parametrage.ville.*'], 'color' => 'purple', 'description' => 'Liste des villes'],
            ['label' => 'Niveaux d’étude', 'icon' => 'graduation-cap', 'route' => 'admin.parametrage.niveau-etude.index', 'active' => ['admin.parametrage.niveau-etude.*'], 'color' => 'red', 'description' => 'Cycles et niveaux'],
            ['label' => 'Filières', 'icon' => 'book-open', 'route' => 'admin.parametrage.filiere.index', 'active' => ['admin.parametrage.filiere.*'], 'color' => 'green', 'description' => 'Filières d’études'],
            ['label' => 'Départements', 'icon' => 'layers', 'route' => 'admin.parametrage.departement.index', 'active' => ['admin.parametrage.departement.*'], 'color' => 'amber', 'description' => 'Départements académiques'],
            ['label' => 'Configurations système', 'icon' => 'settings', 'route' => 'admin.parametrage.configuration.index', 'active' => ['admin.parametrage.configuration.*'], 'color' => 'purple', 'description' => 'Réglages généraux'],
            ['label' => 'Types d’utilisateurs', 'icon' => 'user-cog', 'route' => 'admin.parametrage.type-utilisateur.index', 'active' => ['admin.parametrage.type-utilisateur.*'], 'color' => 'red', 'description' => 'Profils des utilisateurs'],
        ],
    ],
];
