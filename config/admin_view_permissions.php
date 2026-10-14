<?php

// Lists are alternatives. Opening a shared page never grants its section permissions.
return [
    'housing.occupation' => ['hebergement.parc.view'],
    'housing.finance' => ['hebergement.paiements.view'],
    'housing.maintenance' => ['hebergement.maintenance.view'],
    'housing.discipline' => ['hebergement.discipline.view'],
    'housing.contracts' => ['hebergement.residents.view'],
    'housing.inspections' => ['hebergement.departs.view'],
    'housing.departures' => ['hebergement.departs.view'],
    'housing.announcements' => ['hebergement.annonces.view'],
    'admissions.personal' => ['hebergement.admissions.personal'],
    'admissions.documents' => ['hebergement.admissions.documents'],
    'admissions.history' => ['hebergement.admissions.history'],
    'admissions.eligibility' => ['all' => ['hebergement.admissions.personal', 'hebergement.admissions.documents', 'hebergement.admissions.history']],
    'users.personal' => ['utilisateurs.personal'],
    'roles.assignments' => ['parametrage.roles.assign', 'utilisateurs.view'],
    'settings.secrets' => ['parametrage.configuration.configure'],
];
