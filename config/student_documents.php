<?php
return [
    // Origine des anciens justificatifs dont la base contient une URL complète.
    'legacy_origin' => env('STUDENT_DOCUMENTS_LEGACY_ORIGIN', 'https://croudedaloa.soumafe.ci'),
    // Racine web où écrire admin/assets/images/etudiant-document (défaut : dossier du contrôleur frontal).
    'upload_root' => env('STUDENT_DOCUMENTS_UPLOAD_ROOT'),
];
