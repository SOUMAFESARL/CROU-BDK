# Réclamations sur les demandes d'hébergement

Pendant une période ouverte par l'administration, un étudiant peut contester le traitement de sa demande depuis la page de connexion. Code : `App\Http\Services\ReclamationService`, modèles `Reclamation` et `ReclamationPeriode`, migrations `2026_11_24_000005`, `000007` et `000008` (`*_reclamations*`). L'API mobile n'est pas concernée.

Le menu « Réclamations » ouvre une page de cartes, comme l'accueil du paramétrage hébergement (composant commun `rubriques-modules.tsx`) : « Liste des réclamations » et « Périodes de réclamation ».

## Configuration (Réclamations › Périodes de réclamation)

Une période = une campagne, une date de début, une date de fin (bornes comprises). Règles :

- la période commence **après la date de fin de la campagne** (au plus tôt le lendemain) ; une campagne sans date de fin est refusée, et une campagne ne peut plus être prolongée au-delà du début de sa période de réclamation ;
- deux périodes ne se chevauchent pas, car le formulaire public ne demande pas la campagne ;
- la campagne d'une période n'est plus modifiable, et la période n'est plus supprimable, dès qu'une réclamation y a été déposée (avancer la date de fin pour la fermer) ;
- une période dont la campagne a été supprimée n'ouvre plus le formulaire.

## Dépôt (côté étudiant)

`/login` affiche « Une campagne de réclamation est en cours. Faire une réclamation » (`reclamationUrl`), au même endroit que le lien de demande d'hébergement. La campagne concernée étant terminée, le lien remplace celui de la demande ; les deux ne s'affichent ensemble que si une autre campagne est ouverte.

`/reclamation` (`web\ReclamationPubliqueController`, 10 envois par minute) : nom et prénoms, numéro de table au bac ou matricule (un seul champ obligatoire, 50 caractères), type de demande (volets ouverts par la campagne), motif (10 à 2 000 caractères).

La réclamation est rattachée à la demande de l'étudiant pour la campagne de la période :

- nom complet identique à celui de la fiche (ordre des mots, casse et accents indifférents) ; brouillons exclus ;
- le numéro saisi est celui de la demande (`demande.num_table_bac`) ou le matricule de la fiche (`etudiants.matricule`), espaces, casse et ponctuation indifférents ; une fiche sans aucun numéro connu est rattachée par le nom seul, s'il est sans homonyme ;
- le type choisi doit être celui de la demande (`CriteresCampagne::voletEtudiant`) ;
- sans correspondance, ou si les homonymes ne sont pas départagés, le dépôt est refusé avec un message sous le champ ;
- une seule réclamation non traitée par demande ; une nouvelle est possible après une décision.

Un numéro `REC-AAAA-00000` est donné à l'étudiant.

## Traitement

La liste (Réclamations › Liste des réclamations) a un onglet par statut : `en_attente` (statut de dépôt, que l'agent peut aussi choisir), `ouverte` (non traitée, comme `en_attente`), `acceptee`, `rejetee`. Recherche par nom, numéro de table au bac ou matricule saisi, numéro de réclamation ou de demande ; filtres par campagne et type. La colonne « Demande » n'affiche que le numéro et le type ; le motif n'est pas dans le tableau (fiche de la demande ou fenêtre « Traiter »). Traiter une réclamation = choisir son statut ; la saisie et l'affichage d'une réponse sont retirés pour l'instant (la colonne `reclamations.reponse` et les réponses déjà enregistrées sont conservées).

Une réclamation ne change jamais l'état du dossier : la décision se prend sur la fiche de la demande (dérogation, transitions de `DossierWorkflow`).

L'œil (ou un clic sur la ligne) ouvre la fiche de la demande (`campagnes_admissions/{etudiant}?origine=reclamations`) : elle affiche « Motif de la réclamation » sous « Documents fournis », avec le statut et le bouton « Traiter ».

## Droits (module `hebergement.reclamations`)

| Permission | Usage |
| --- | --- |
| `hebergement.reclamations.view` | Liste, motif sur la fiche, ouverture de la fiche des étudiants qui ont une réclamation |
| `hebergement.reclamations.traiter` | Traiter (statut) |
| `hebergement.reclamations.configure` | Périodes de réclamation |

Les blocs de la fiche gardent leurs propres droits : `hebergement.admissions.personal`, `.documents` et `.history` (les trois pour les critères d'éligibilité).

Test : `php artisan test --filter=ReclamationsTest`
