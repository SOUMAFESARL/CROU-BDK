# Permissions de l'administration

Les routes de `routes/admin.php` passent par `CheckAdminAccess`. Leur règle est définie dans `config/admin_permissions.php` : une permission, une liste de permissions alternatives, ou une liste vide pour les pages accessibles à tout administrateur connecté (accueil personnalisé et déconnexion). Les routes publiques de connexion sont déclarées séparément. Une route absente de ce registre est refusée.

Les menus, cartes, formulaires et actions utilisent `@adminroute('admin.nom.route')`. Le contrôle serveur reste obligatoire, y compris pour les requêtes AJAX et les accès par URL directe. Ajouter une route nécessite une règle et, si nécessaire, une nouvelle permission dans une migration.

Le rôle d'identifiant **1** conserve tous les droits. Le nom d'un rôle ne donne aucun privilège. Les autres rôles doivent posséder la permission et celle-ci doit appartenir à un module actif. Aucune autorisation globale n'est accordée lorsque la matrice est vide.

La migration `2026_09_29_000001_complete_admin_permissions.php` complète les actions existantes et ajoute les modules manquants. Elle préserve les affectations existantes et n'accorde les nouvelles permissions qu'au rôle 1. Sa méthode de retour conserve volontairement les permissions pour ne pas supprimer des affectations personnalisées.

Attribuer les droits nécessaires dans **Paramètres → Rôles et permissions**. Créer un utilisateur exige `utilisateurs.create` et `parametrage.roles.assign`. Modifier son rôle exige aussi `parametrage.roles.assign`. Seul le rôle 1 peut affecter le rôle 1 ou modifier un compte qui le possède.

Validation ciblée :

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/AdminPermissionsTest.php tests/Feature/RoleDashboardTest.php
```

## Vues partagées

`config/admin_view_permissions.php` définit les droits de chaque bloc. Utiliser `@adminblock('housing.finance')` pour un bloc nommé, ou `@adminpermission('hebergement.paiements.view')` pour une permission. Ces directives sont disponibles dans toutes les vues. Une entrée sur une page partagée ne donne pas accès à ses autres rubriques.

La vie résidentielle filtre ses indicateurs et ses onglets (parc, finances, maintenance, discipline, contrats et départs). Les requêtes correspondant aux domaines non autorisés sont évitées. Le reporting exige à la fois son droit de consultation et le droit du domaine demandé ; ses rubriques de vie résidentielle sont filtrées séparément.

Les permissions `hebergement.admissions.personal`, `.documents`, `.history` et `utilisateurs.personal` permettent de séparer les informations personnelles, justificatifs et historiques. La migration `2026_09_29_000002_add_admin_view_permissions.php` les ajoute sans les attribuer automatiquement aux rôles existants, sauf au super-administrateur. Le tableau d’éligibilité nécessite les trois droits de lecture des admissions, car il combine ces données.

`AdminViewData` applique également le filtrage aux modèles transmis à toutes les vues `admin.*` et aux réponses JSON des routes admin. Les tableaux Alpine ne reçoivent que leurs champs affichés et les identifiants nécessaires. Les contrôles d’action restent indépendants de la lecture des blocs.

Les noms de tables utilisés dans les validations, requêtes, migrations et fixtures sont en minuscules (`roles`, `genre`, `campagne`, etc.) pour fonctionner sur les serveurs MySQL sensibles à la casse. Les noms de classes PHP gardent leur casse.

Tests des vues partagées et de l’affectation des rôles :

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/SharedAdminViewsTest.php tests/Feature/AdminPermissionsTest.php tests/Feature/RoleDashboardTest.php tests/Feature/HousingReportingTest.php
```
