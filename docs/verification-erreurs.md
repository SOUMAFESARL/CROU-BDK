# Vérification de la gestion des erreurs — 6 octobre 2026

Les tests ciblés `UserFacingErrorsTest`, `AppFeedbackTest` et `EquipmentAllocationTest` passent : **30 tests, 158 assertions**. Ils couvrent notamment les messages JSON/HTML, les erreurs de champs en français, la conservation des codes HTTP, les données de formulaire, la confidentialité en mode debug et le chargement du script de gestion des erreurs navigateur.

La syntaxe de 327 fichiers PHP a été vérifiée, ainsi que celle de `public/js/user-errors.js` avec Node.

La suite Feature complète a exécuté **293 tests : 284 réussites, 9 échecs**. Les échecs à examiner concernent :

- `AdminStudentContractTest` (3) : la base de test ne crée pas `student_contract_approvals` pour les téléchargements ; le contrôle du lien de contrat ne retrouve pas le lien attendu.
- `AnnonceDiffusionTest` (1) : création testée avec une méthode HTTP refusée (405).
- `AttributionSecurityTest` (3) : conservation de redirection après déconnexion.
- `ExampleTest` (1) : la base de test ne contient pas `config_app` pour la page d’accueil.
- `LegalDocumentsTest` (1) : attente d’un code 419, alors que la requête renvoie une redirection 302.

Les vérifications ciblées passent ; la suite globale n’est donc pas entièrement verte. Les règles métier, les permissions et les données n’ont pas été assouplies pour rendre ces tests verts. Les tests complets utilisent une limite mémoire PHP de 512 Mo pour supporter les rendus PDF ; cette limite est appliquée à la commande de test uniquement.
