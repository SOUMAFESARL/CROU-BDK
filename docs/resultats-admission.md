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
| `admis` (ensuite) | Demande de la file d'attente admise à loger par un agent (voir plus bas) |
| `recevable_non_admis` | File d'attente ; demandes recevables non validées |
| `non_recevable` | Demandes irrecevables (dont celles écartées par la liste élargie), refusées, ou dont une information manquante empêche d'établir la recevabilité |
| aucun | Demande validée pas encore approuvée ; brouillon ; demande annulée par l'étudiant ou expirée ; volet non publié |

## Consultation (`/resultats`)

La page de connexion étudiant affiche « Les résultats d'admission sont publiés. Consulter mon résultat » dès que les résultats publiés d'une campagne sont visibles (`resultatsUrl`, `ResultatsAdmission::campagnesConsultables()`).

- Un seul champ : numéro de table au bac (`demande.num_table_bac`) ou matricule (`etudiants.matricule`) ; casse, espaces, tirets, points et barres indifférents. 60 consultations par minute et par adresse IP.
- Les 5 derniers numéros saisis sont **gardés dans le navigateur** (`localStorage`, clé `resultats.numeros`) et proposés dans le champ ; chacun peut être retiré (croix, ou Maj + Suppr). Rien n'est enregistré côté serveur. Code : `hooks/use-saisies-memorisees`, `ui/input-suggestions`.
- Le résultat est celui de la **dernière demande** déposée par l'étudiant. Sans résultat publié pour cette demande, ou campagne non cochée : « pas encore publié ».
- Même numéro pour deux étudiants : celui d'une campagne visible ; si les deux y ont une demande, renvoi au service Logement.
- Admis : invitation à payer à la comptabilité pour avoir accès à la chambre (chambre déjà attribuée : message adapté). Une admission remise en cause depuis la publication (refus, annulation, réexamen, expiration) n'est plus affichée.
- Non admis : lien vers la réclamation pendant une période ouverte (`docs/reclamations.md`).

## Menu « Résultats d'admission » (administration)

Gestion Hébergement › **Résultats d'admission**, après « Campagnes & admission » (`/admin/gestion-hebergement/resultats`). Code : `admin\immobilier\resultats\ResultatController`, page React `Admin/Hebergement/Resultats/Index`, migrations `2026_11_25_000003` (visibilité), `000004` (droits), `000005` (libellé du module) et `000007` (droit « Valider une demande »).

- **Visibilité de la page publique, par campagne** : le bouton « Rendre la page visible » (ou « Campagnes visibles ») ouvre la liste des campagnes publiées ; seules les campagnes **cochées** (une au moins) sont consultables (table `resultats_visibilite`, une ligne par campagne). Aucune n'est cochée par défaut : publier les listes ne suffit pas à rendre les résultats visibles. Une campagne déjà cochée le reste à la publication suivante. « Masquer la page » (avec confirmation) décoche tout : plus de lien sur la page de connexion, `/resultats` affiche « Aucun résultat n'est publié » et toute consultation est refusée ; les résultats figés, les états des dossiers et les messages de l'espace étudiant ne changent pas.
- **Demandes par statut**, pour une campagne (par défaut la dernière publiée) : « Demandes validées » (`admis`), « File d'attente » (`recevable_non_admis`), « Non recevables / rejetées » (`non_recevable`). Filtres : recherche, campagne, type de demande. Ce sont les résultats figés à la publication, tels que les étudiants les voient ; une admission remise en cause depuis est signalée.
- **Valider la demande** (file d'attente) : l'icône de la ligne, avec confirmation, fait passer la demande dans « Admis à loger » (`eligible → confirme` par l'agent, tracé dans l'historique du dossier), même si la campagne est terminée : le paiement puis l'attribution d'une chambre deviennent possibles. Son résultat devient `admis` (onglet « Demandes validées » et page `/resultats`). Icône présente seulement pour une demande recevable approuvée en commission, dernière demande de l'étudiant ; une sanction disciplinaire bloquante refuse le passage. Dans les « Listes définitives », la demande reste dans la file d'attente publiée, et une nouvelle publication garde son admission. Code : `ResultatsAdmission::admettre`, `DossierWorkflow::admettreFileAttente`.
- **Exporter** (Excel ou PDF) : toutes les demandes de l'onglet affiché, avec les filtres de l'écran, par ordre alphabétique (nom, matricule, n° de table, n° de demande, type, état du dossier).
- Droits (« Rôles et permissions », module « Résultats d'admission ») : `hebergement.resultats.view` « Consulter » (la page), `.publier` « Publier » (campagnes visibles, masquer la page), `.admettre` « Valider une demande » et `.export` « Exporter », accordés au seul rôle 1 ; les trois derniers exigent le premier. La fiche de la demande garde ses propres droits.

## Identifiants de l'espace étudiant

Aucun identifiant n'est envoyé à la validation ni à la publication. L'envoi est prévu après le paiement de la caution et de l'avance, pour les nouvelles demandes d'admission seulement (pas encore développé ; `IdentifiantsEtudiant` et le job `EnvoyerIdentifiantsEtudiant` sont conservés). Seul un brouillon reçoit encore ses identifiants à l'inscription.

Paramétrage › Configuration des messages est conservé mais sans effet (bandeau « Envoi suspendu ») ; la commande `admissions:notifier` n'est plus planifiée.

## Déploiement

1. `php artisan migrate` : crée `resultats_admission` et `resultats_visibilite`, ajoute les droits du menu « Résultats d'admission » et retire de la file les envois d'identifiants en attente.
2. `php artisan admissions:appliquer-publications` : une fois, pour les listes publiées avant cette mise à jour (résultats et passage dans « Admis à loger »). La commande peut être relancée.

3. Menu « Résultats d'admission » : cocher la ou les campagnes à rendre visibles.

Test : `php artisan test --filter="ResultatsAdmissionTest|ResultatsAdminPageTest"`
