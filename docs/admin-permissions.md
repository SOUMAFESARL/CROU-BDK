# Permissions de l'administration

Les routes de `routes/admin.php` passent par `CheckAdminAccess`. Leur règle est définie dans `config/admin_permissions.php` : une permission, une liste de permissions alternatives, ou une liste vide pour les pages accessibles à tout administrateur connecté (accueil personnalisé et déconnexion). Les routes publiques de connexion sont déclarées séparément. Une route absente de ce registre est refusée.

Les menus, cartes, formulaires et actions utilisent `@adminroute('admin.nom.route')`. Le contrôle serveur reste obligatoire, y compris pour les requêtes AJAX et les accès par URL directe. Ajouter une route nécessite une règle et, si nécessaire, une nouvelle permission dans une migration.

Le rôle d'identifiant **1** conserve tous les droits. Le nom d'un rôle ne donne aucun privilège. Les autres rôles doivent posséder la permission et celle-ci doit appartenir à un module actif. Aucune autorisation globale n'est accordée lorsque la matrice est vide.

La migration `2026_09_29_000001_complete_admin_permissions.php` complète les actions existantes et ajoute les modules manquants. Elle préserve les affectations existantes et n'accorde les nouvelles permissions qu'au rôle 1. Sa méthode de retour conserve volontairement les permissions pour ne pas supprimer des affectations personnalisées.

La migration `2026_11_10_000002_add_eligibilite_permissions.php` crée le socle des droits du paramétrage des campagnes (lot L1 de `docs/notes/plan-integration-campagne-criteres.pdf`), accordé au seul rôle 1 :

| Module | Permission | Usage |
| --- | --- | --- |
| `hebergement.eligibilite.quotas` | `.view`, `.configure` | Consulter / saisir les places et quotas par cité |
| `hebergement.eligibilite.regles` | `.view`, `.configure` | Consulter / paramétrer le paramétrage général et les critères d’admission et de réadmission |
| `hebergement.eligibilite` | `.generate` | Générer la liste provisoire et la simulation à blanc |

Les critères d’une campagne se définissent en une fois, sur la page « Critères de la campagne » du paramétrage hébergement (paramétrage général, puis un niveau admission et/ou réadmission selon le type de campagne) (voir `docs/notes/eligibilite-criteres-campagne.md`). Les anciennes pages « Règles d’éligibilité » et « Scoring » (barème de score) sont supprimées.

| Route | Permission(s) |
| --- | --- |
| `admin.hebergement.parametrage-herb.criteres-campagne.index` | `hebergement.parametrage.view` ou un des droits `eligibilite.regles.view/.configure`, `eligibilite.quotas.view/.configure` |
| `admin.hebergement.eligibilite.criteres.update`, `.abandonner` (paramètre `{volet}` : `general`, `admission` ou `readmission`) | `regles.configure` ou `quotas.configure` ; à l’enregistrement, chaque bloc envoyé exige son propre droit (403 sinon) |
| `admin.hebergement.eligibilite.criteres.appliquer` | `hebergement.eligibilite.regles.configure` |

La migration `2026_11_13_000002_add_discipline_configure_permission.php` ajoute au module « Discipline » le droit **`hebergement.discipline.configure`** (« Paramétrer les sanctions et la note de conduite »), accordé au seul rôle 1. La page « Paramétrage des sanctions » (paramétrage hébergement, voir `docs/parametrage-sanctions.md`) se consulte avec le droit de consulter la discipline ou le paramétrage ; sans `configure`, elle s’affiche en lecture seule (aucune action envoyée au navigateur).

| Route | Permission(s) |
| --- | --- |
| `admin.hebergement.parametrage-herb.sanctions.index` | `hebergement.discipline.view`, `hebergement.discipline.configure` ou `hebergement.parametrage.view` |
| `admin.hebergement.parametrage-herb.sanctions.note`, `.store`, `.update`, `.destroy` | `hebergement.discipline.configure` |

Les décisions disciplinaires (rapports d’incident, sanctions directes) gardent leurs droits (`hebergement.discipline.create`, `.update`) : elles proposent les types actifs du paramétrage.

La migration `2026_11_23_000003_add_types_paiement_permissions.php` ajoute le module **« Types de paiement »** (`hebergement.types-paiement`) et ses droits `.view`, `.create`, `.update`, `.delete` pour la page du même nom (paramétrage hébergement, rubrique « Parc immobilier », voir `docs/types-paiement.md`). Ils sont accordés au rôle 1, et repris des droits `hebergement.parametrage.view` (→ `view`) et `.configure` (→ `create`, `update`, `delete`) pour les rôles qui les avaient.

La migration `2026_11_24_000006_add_reclamations_permissions.php` ajoute le module **« Réclamations »** (`hebergement.reclamations`) et ses droits `.view`, `.traiter` (nommé `.update` avant la migration `2026_11_24_000009`) et `.configure` (périodes), accordés au seul rôle 1 (voir `docs/reclamations.md`). `.view` ouvre aussi la fiche de la demande des étudiants qui ont déposé une réclamation ; ses blocs personnels, documents et historique gardent leurs droits.

| Route | Permission(s) |
| --- | --- |
| `admin.hebergement.reclamations.index` (cartes) | `hebergement.reclamations.view` ou `.configure` ; chaque carte suit le droit de sa page |
| `admin.hebergement.reclamations.liste` | `hebergement.reclamations.view` |
| `admin.hebergement.reclamations.configuration`, `.periodes.store`, `.periodes.update`, `.periodes.destroy` | `hebergement.reclamations.configure` |
| `admin.hebergement.reclamations.traiter` | `hebergement.reclamations.traiter` (« Favorable » : et `hebergement.resultats.admettre`) |

La migration `2026_11_25_000004_add_resultats_permissions.php` ajoute le module **« Résultats d'admission »** (`hebergement.resultats`) et ses droits `.view`, `.publier` et `.export`, accordés au seul rôle 1 (libellé du module : `2026_11_25_000005` ; droit `.admettre` : `2026_11_25_000007` ; voir `docs/resultats-admission.md`). Publier, admettre et exporter exigent aussi `.view`.

| Route | Permission(s) |
| --- | --- |
| `admin.hebergement.resultats.index` | `hebergement.resultats.view` |
| `admin.hebergement.resultats.visibilite` (campagnes visibles, page masquée) | `hebergement.resultats.publier` |
| `admin.hebergement.resultats.admettre` (file d'attente → « Admis à loger ») | `hebergement.resultats.admettre` |
| `admin.hebergement.resultats.export` (Excel, PDF) | `hebergement.resultats.export` |

La migration `2026_11_25_000001_ranger_droits_admissions_en_sous_modules.php` range les droits du module **« Campagnes et admissions »** (`hebergement.admissions`) : le module ne garde que ses droits de base (`view`, `create`, `update`, `delete`, `validate`, `personal`, `documents`, `history`) et chaque onglet devient un sous-module, comme « Dérogations d’admission » (sauf « Campagnes » : la liste des campagnes s’ouvre avec « Consulter » du module, `hebergement.admissions.view`). Les droits existants sont déplacés (les rôles les gardent) : `hebergement.admissions.commission_validees_view` devient `hebergement.admissions.commission-validees.view`, et « Valider » y est l’action commune `validate`. Les slugs se lisent toujours par `App\Services\AdmissionPermissions::slug($onglet, $action)`.

| Sous-module (`hebergement.admissions.…`) | Droits |
| --- | --- |
| `derogations` — Dérogations d’admission | `deroger` (bouton « Déroger » de la fiche, appliqué sur-le-champ, et levée des refus automatiques dans les listes consultables) |
| `demandes` — Liste des demandes (onglet « Toutes les demandes ») | `view` |
| ↳ `recevables` — Demandes recevables | `view` |
| ↳ ↳ `recevables-demandes` — Liste des demandes recevables | `view` |
| ↳ ↳ `provisoire` — Listes provisoires (admissions et réadmissions) | `view`, `validate`, `refuser`, `attente`, `export` |
| ↳ `rejetees` — Demandes irrecevables | `view` |
| ↳ `refusees` — Demandes refusées | `view` |
| `commission` — Décision de la commission | `view`, `approuver` (bouton « Approuver la liste ») |
| ↳ `commission-validees` — Demandes validées | `view`, `refuser`, `attente` |
| ↳ `commission-attente` — File d’attente | `view`, `validate`, `refuser` |
| `definitives` — Listes définitives (demandes validées et file d’attente) | `view`, `publier`, `export` |
| `dossier` — Dossier de candidature | `traiter`, `document`, `decision` |

Un sous-onglet exige aussi le droit « Consulter » de ses onglets parents (`AdmissionPermissions::PARENTS`) ; les onglets de la « Liste des demandes » s’ouvrent chacun avec leur propre droit « Consulter ». Désactiver un module coupe aussi les droits de ses sous-modules (`ApplicationModule::estActif()` ; les grands modules à la racine ne sont pas pris en compte).

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
