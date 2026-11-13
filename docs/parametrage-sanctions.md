# Paramétrage des sanctions et note de conduite

Chaque étudiant dispose d’une **note de conduite**. Elle part d’une note initiale commune (20 par défaut) et perd des points à chaque sanction qui le vise. Les sanctions et leurs points se paramètrent une seule fois ; elles sont ensuite utilisées dans tout le système : décision d’un incident disciplinaire, sanction directe, blocages d’admission et d’attribution, critère de réadmission « Conduite en cité ».

## Page

Paramétrage hébergement (`/admin/gestion-hebergement/parametrage-herb`), rubrique « Campagnes & admissions », module **Paramétrage des sanctions** (placé avant « Critères de la campagne ») : route `admin.hebergement.parametrage-herb.sanctions.index`, page React `Admin/Hebergement/Parametrage/Sanctions` (`ParametrageSanctionsController`).

- **Note de conduite initiale** : de 1 à 100 points (`PUT …/sanctions/note-conduite`, route `.note`).
- **Types de sanction** : libellé, points retirés (0 à 100, deux décimales), effet, description, proposé ou non. Ajout et modification par un assistant en trois étapes (sanction, effet, vérification) ; suppression confirmée (`POST …/types`, `PUT|DELETE …/types/{type}`).

| Effet | Conséquence |
| --- | --- |
| `aucun` | Retrait de points uniquement |
| `suspension` | Bloque les nouvelles admissions, attributions et confirmations jusqu’à la date de fin (période obligatoire à la décision) |
| `expulsion` | Bloque, annule les réservations et prépare la sortie du logement ; décidée uniquement depuis un rapport disciplinaire |

Le comportement du système dépend de l’**effet**, jamais du code : un type ajouté se comporte comme ceux d’origine.

Barème d’origine (repris par la migration, modifiable) : avertissement 2 points, blâme 5, suspension 10, expulsion 20.

Règles de gestion (`App\Http\Services\CatalogueSanctions`) :

- le code d’un type est déduit de son libellé à la création (valeur enregistrée dans `sanctions.type`) et ne change jamais ; deux types ne peuvent pas porter le même libellé ; `aucune` est réservé à la décision « Aucune sanction » ;
- un type **déjà appliqué** ne se supprime pas (il se désactive : il n’est plus proposé mais reste lisible dans l’historique) et son effet est figé ;
- un nouveau nombre de points ne s’applique qu’aux sanctions suivantes : chaque sanction individuelle fige ses points à sa création (`sanctions.points_retires`).

Droits (`docs/admin-permissions.md`) : consultation avec `hebergement.discipline.view` (ou `hebergement.parametrage.view`), modifications avec `hebergement.discipline.configure` (rôle 1 seulement par défaut).

## Note de conduite d’un étudiant

`App\Http\Services\NoteConduite` : note initiale − points retirés par les sanctions **individuelles** de l’étudiant, sans descendre sous 0.

- points d’une sanction : ceux figés à sa création, sinon (sanctions antérieures au paramétrage) le barème actuel de son type ;
- une sanction **annulée** restitue ses points ; une sanction **levée** a été appliquée : ses points restent retirés ;
- les sanctions collectives (chambre, bâtiment, cité) ne modifient pas la note individuelle.

La note est cumulée sur l’historique de l’étudiant (pas de remise à zéro par année universitaire).

## Utilisation dans le système

- **Incidents disciplinaires** (`DisciplinaryReportController`, et l’API du Service Sécurité qui partage `DisciplinaryImpactService::decisionRules()`) : la décision propose « Aucune sanction » et les types actifs, avec les points retirés ; la date de fin est exigée pour un type à effet `suspension`.
- **Sanctions directes** (`SanctionController`) : types actifs hors effet `expulsion` ; filtre du registre sur tous les types.
- **Blocages** (`DisciplinaryImpactService::statut()/verifier()`, `AttributionRules`, `EtudiantRepository::verifierEligibilite`) : une sanction active à effet `suspension` ou `expulsion` bloque, quel que soit son code.
- **Critère de réadmission « Conduite en cité »** (`docs/notes/eligibilite-criteres-campagne.md`) : coché, il exige une **note de conduite minimale** (`readmission.conduite.note_min`, de 0 à la note initiale ; 10 dans le modèle CROU). `NoteConduite::motifRejet($regles, $etudiantId)` renvoie le motif du rejet (« Note de conduite insuffisante : 7/20 pour un minimum de 10. ») ou `null` : c’est la règle que lira le moteur de la liste provisoire.

## Espace étudiant

`App\Services\EtudiantConduite` construit la note, la jauge et l’historique des sanctions qui visent l’étudiant (type, date, période, motif, points, statut ; une sanction annulée est affichée « points restitués »).

- **Mon profil** (`/etudiant/profil?onglet=conduite`) : onglet « Ma conduite » avec la note et l’historique des retraits de points.
- **Conduite et signalements** (ancien « Signaler un incident », `/etudiant/dashboard?section=incidents`), en deux onglets (`&onglet=conduite|signalements`, « Conduite » par défaut ; la pagination et la redirection après déclaration rouvrent « Signalements ») : **Conduite** = note de conduite et sanctions appliquées à l’étudiant ; **Signalements** = déclaration d’un incident et suivi des signalements comme auparavant (pour un signalement, ni la décision ni la sanction ne sont montrées : elles peuvent viser un autre résident).

## Données

Migration `2026_11_13_000001_create_parametrage_sanctions_tables.php` : tables `types_sanction` (types et barème d’origine) et `parametres_conduite` (ligne unique, note initiale), colonne `sanctions.points_retires`. Tant que ces tables n’existent pas, le barème d’origine et la note de 20 s’appliquent.

Tests : `tests/Feature/ParametrageSanctionsTest.php`, `StudentIncidentsPageTest`, `CriteresCampagneTest` (note minimale).
