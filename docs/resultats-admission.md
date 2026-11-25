# Résultats d'admission

Depuis le 25/11/2026, publier les listes définitives n'envoie plus aucun message aux étudiants : chacun consulte son résultat sur la plateforme. Code : `App\Http\Services\Eligibilite\ResultatsAdmission`, `web\ResultatPublicController`, page React `Etudiant/Auth/Resultat`, migration `2026_11_25_000002`. L'API mobile n'est pas concernée.

## À la publication

« Publier les listes » (Campagnes & admission › Listes définitives), dans la même transaction que la publication :

- **fige le résultat** de chaque demande des volets publiés (table `resultats_admission`, une ligne par demande, mise à jour si la liste est publiée à nouveau) ;
- **fait passer dans « Admis à loger »** les demandes validées publiées : transition `eligible → confirme` par le système (`DossierWorkflow`), possible même si la campagne est terminée. L'étudiant n'a plus à confirmer sa demande. Une sanction disciplinaire bloquante empêche ce passage : l'étudiant est cité dans le message de publication.

Les étudiants admis restent dans la liste définitive, avec leur rang, y compris une fois logés.

| Résultat | Demandes |
| --- | --- |
| `admis` | Liste définitive, onglet « Demandes validées », approuvées avant la publication de leur volet |
| `recevable_non_admis` | File d'attente ; demandes recevables non validées |
| `non_recevable` | Demandes irrecevables (dont celles écartées par la liste élargie), refusées, ou dont une information manquante empêche d'établir la recevabilité |
| aucun | Demande validée pas encore approuvée ; brouillon ; demande annulée par l'étudiant ou expirée ; volet non publié |

## Consultation (`/resultats`)

La page de connexion étudiant affiche « Les résultats d'admission sont publiés. Consulter mon résultat » dès qu'une liste est publiée (`resultatsUrl`).

- Un seul champ : numéro de table au bac (`demande.num_table_bac`) ou matricule (`etudiants.matricule`) ; casse, espaces, tirets, points et barres indifférents. 60 consultations par minute et par adresse IP.
- Le résultat est celui de la **dernière demande** déposée par l'étudiant. Sans résultat publié pour cette demande : « pas encore publié ».
- Même numéro pour deux étudiants : celui de la campagne publiée ; si les deux y ont une demande, renvoi au service Logement.
- Admis : invitation à payer à la comptabilité pour avoir accès à la chambre (chambre déjà attribuée : message adapté). Une admission remise en cause depuis la publication (refus, annulation, réexamen, expiration) n'est plus affichée.
- Non admis : lien vers la réclamation pendant une période ouverte (`docs/reclamations.md`).

## Identifiants de l'espace étudiant

Aucun identifiant n'est envoyé à la validation ni à la publication. L'envoi est prévu après le paiement de la caution et de l'avance, pour les nouvelles demandes d'admission seulement (pas encore développé ; `IdentifiantsEtudiant` et le job `EnvoyerIdentifiantsEtudiant` sont conservés). Seul un brouillon reçoit encore ses identifiants à l'inscription.

Paramétrage › Configuration des messages est conservé mais sans effet (bandeau « Envoi suspendu ») ; la commande `admissions:notifier` n'est plus planifiée.

## Déploiement

1. `php artisan migrate` : crée `resultats_admission` et retire de la file les envois d'identifiants en attente.
2. `php artisan admissions:appliquer-publications` : une fois, pour les listes publiées avant cette mise à jour (résultats et passage dans « Admis à loger »). La commande peut être relancée.

Test : `php artisan test --filter=ResultatsAdmissionTest`
