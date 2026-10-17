# Attribution des chambres — phase 2

Les écrans existants sont conservés. Une section repliable de confirmation PMR est ajoutée au formulaire d'attribution et une case au formulaire d'admission directe. L'import accepte la colonne facultative `pmr_confirme` (Oui/Non), ajoutée en fin du modèle pour préserver les colonnes existantes. L'agent qui importe confirme les informations de son fichier.

## Règles appliquées

- Chambre, bâtiment et cité doivent être actifs et cohérents. La cité doit appartenir à la campagne.
- Les restrictions hommes/femmes/mixte sont vérifiées aux trois niveaux à partir du référentiel et du genre de l'étudiant. Un référentiel manquant ou inconnu bloque l'attribution et doit être complété.
- Une chambre PMR exige `pmr_confirme`, sauf pour l'admission directe (régularisation d'un étudiant déjà installé dans la chambre, formulaire et import). Aucune conversion du champ historique `autiste` n'est effectuée. La confirmation et son retrait enregistrent l'agent et la date.
- Les assignations `en_attente` et `confirmee` sans date de fin passée consomment une place, toutes campagnes confondues. Les propositions futures sont également réservées : ce lot n'introduit pas de calendrier de séjours successifs.
- Les places occupées affichées restent les occupations confirmées en cours ; les places disponibles retranchent aussi les réservations. L'état d'une chambre partiellement occupée reste compatible avec les écrans existants.
- La création verrouille l'étudiant puis la cité, le bâtiment et la chambre, dans une transaction. Le comptage des places utilise une lecture verrouillée pour éviter un instantané périmé sous MySQL REPEATABLE READ.
- La confirmation revérifie les règles et exclut sa propre réservation du comptage. Une réservation annulée ou expirée ne peut pas être confirmée. La confirmation répétée est sans effet supplémentaire.
- Une réaffectation échouée restaure l'ancienne attribution par annulation de la transaction. Une annulation ne supprime pas les états maintenance/fermeture.
- Les admissions directes et les imports utilisent le même contrôle. Une réservation empêche aussi la désactivation du parc concerné.

## Installation

Migration additive à appliquer lorsque MySQL est disponible :

```powershell
C:\xampp\php\php.exe artisan migrate --path=database/migrations/2026_09_21_000001_add_pmr_confirmation_to_etudiants.php --force
```

Les dossiers existants ont `pmr_confirme = false` par défaut. Leur confirmation nécessite une vérification par un agent ; aucun statut PMR n'est présumé.

## Vérification

Tests ciblés : `tests/Feature/AttributionSecurityTest.php` et `tests/Feature/ParcMaintenanceTest.php`. Ils utilisent une base SQLite en mémoire, sans modifier les données métier.

Une recette avec deux agents simultanés sur MySQL reste nécessaire pour valider les verrouillages dans l'environnement réel. Ce lot ne met pas en place une expiration automatique des réservations, l'attribution par lit individualisé ou la séparation complète des permissions par profil métier.
