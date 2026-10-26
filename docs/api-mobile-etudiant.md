# Documentation des API du projet


Référence des routes déclarées dans routes/api.php, vérifiée dans le code le 25 septembre 2026.
Les API mobiles, les routes historiques, le service sécurité et les notifications prestataires sont inclus.
Les accès indiqués correspondent au code actuel ; les limites connues sont signalées à la fin.

## Sommaire

- [Authentification](#authentification) et [inscription](#inscription-et-précisions-dauthentification)
- [Demandes de sortie](#demandes-de-sortie)
- [Maintenances](#maintenances)
- [Paiements et moyens de paiement](#paiements-et-moyens-de-paiement)
- [États des lieux](#états-des-lieux--liste-entrée-et-sortie)
- [Documents et téléchargements](#documents-et-téléchargements)
- [Conventions et formats de réponse](#conventions-et-formats-de-réponse)
- [Référentiels et annonces](#référentiels-et-annonces)
- [Campagnes et contrats](#campagnes-et-contrats)
- [Entrée en logement, dossiers et réadmission](#entrée-en-logement-dossiers-et-réadmission)
- [Carte et géolocalisation](#carte-et-géolocalisation)
- [Incidents disciplinaires](#incidents-disciplinaires)
- [Ancienne création d'ordre de travail](#ancienne-création-dordre-de-travail)
- [Notifications des prestataires](#notifications-des-prestataires-de-paiement)
- [Exemples HTTP](#exemples-dutilisation-http)
- [Limites constatées](#limites-constatées-lors-de-linventaire)
- [Inventaire exhaustif](#inventaire-exhaustif-des-routes-api)

Base URL : `/api/V1`

Toutes les routes étudiant protégées utilisent l’en-tête :

```http
Authorization: Bearer {token}
Accept: application/json
```

## Authentification

### `POST /auth/authenticate`

```json
{
  "email": "etudiant@example.com",
  "password": "mot-de-passe",
  "device_name": "Android de Koffi"
}
```

Le jeton se trouve dans `auth.token`, avec `auth.token_type = Bearer`.

### `POST /auth/logout`

Révoque uniquement le jeton Bearer utilisé par l’appareil courant.

## Demandes de sortie

### `GET /etudiant/demandes-sortie`

Retourne l’historique des demandes de l’étudiant connecté.

### `POST /etudiant/demandes-sortie`

```json
{
  "date_sortie_prevue": "2026-12-01",
  "motif": "fin_etudes",
  "observations": "Départ définitif",
  "conditions_acceptees": true
}
```

Motifs : `fin_etudes`, `demenagement`, `stage`, `raison_personnelle`, `autre`.
La date doit respecter un préavis minimum de 15 jours et une seule demande en attente est autorisée.

## État des lieux de sortie

### `GET /etudiant/etat-des-lieux-sortie`

Retourne le dernier état des lieux de sortie assigné à l’étudiant connecté, avec pièces, éléments et photos.

### `PATCH /etudiant/etat-des-lieux-sortie/{inspection}/approuver`

```json
{
  "signature_etudiant": "data:image/png;base64,..."
}
```

L’état des lieux doit appartenir à l’étudiant, être de type `sortie` et avoir déjà été signé par le chef de service Logement. Une seconde approbation renvoie `409`.

## Maintenances

### `GET /etudiant/maintenances?statut=en_attente&per_page=15`

Retourne uniquement les maintenances de l’étudiant connecté, avec pagination. Le filtre `statut` est facultatif.

### `POST /etudiant/maintenances`

Requête `multipart/form-data` :

```text
titre=Fuite dans la douche
description=L'eau coule sans arrêt
categorie=eau
priorite=elevee
type_intervention=urgente
date_disponibilite=2026-12-01
periode_disponibilite=Matin
photos[]=photo1.jpg
```

Catégories : `electricite`, `eau`, `sanitaire`, `mobilier`, `securite`, `chauffage`, `climatisation`, `batiment`, `autre`.

Priorités : `critique`, `elevee`, `moyenne`, `faible`.

Types : `corrective`, `preventive`, `urgente`.

Maximum 5 photos de 5 Mo chacune. La chambre, le bâtiment, la cité et l’identité de l’étudiant sont déterminés automatiquement depuis son assignation active.

## Codes principaux

- `200` : opération réussie ;
- `201` : demande créée ;
- `401` : jeton absent, invalide ou expiré ;
- `409` : demande ou approbation déjà existante ;
- `422` : données invalides ou règle métier non respectée.

## Paiements et moyens de paiement

Les endpoints ci-dessous exigent le jeton Bearer, même si une session web est ouverte.
L'activation des réseaux reste gérée dans **Paramétrage > API de paiement**.
L'application mobile ne peut pas modifier ces réglages ni recevoir les clés API.

### Liste des moyens disponibles

`GET /paiements-en-ligne/moyens`

Réponse :

```json
{"success":true,"data":[{"code":"wave","name":"Wave"}]}
```

Seuls les connecteurs de paiement activés et disposant des identifiants requis sont renvoyés :
Wave, CinetPay et MTN configuré pour la Côte d'Ivoire en production.
Un réseau désactivé est immédiatement retiré de cette liste et ne permet plus de lancer de paiement.
La liste vide est une réponse normale (HTTP 200).
Les autres réseaux présents dans le paramétrage n'ont pas encore de connecteur de paiement disponible ici.

### Liste des loyers et paiements

`GET /paiement?per_page=10&page=1`

Alias : `GET /paiements`.
L'ancien lien `GET /paiement/{etudiant_id}` reste disponible, mais l'identifiant doit correspondre au porteur du jeton (sinon 404).

La structure historique est conservée à la racine : `etudiant`, `loyers`, `paiements`, `stats`, `impayes`, `prochainPaiement`.
Les lignes se trouvent dans `paiements.data`, avec `current_page`, `last_page`, `total`, `next_page_url` dans `paiements`.
Suivre `next_page_url` en conservant l'en-tête Authorization.
`per_page` est compris entre 1 et 100. Les paiements annulés sont exclus.

Un reçu validé ou remboursé contient `receipt_url`, téléchargeable avec le même jeton.
`prochainPaiement` désigne le plus ancien loyer restant dû (ou null).
Une erreur serveur n'est plus remplacée silencieusement par une liste vide.

### Lancer un paiement

`POST /paiements-en-ligne`

```json
{
  "loyer_id": "identifiant-du-loyer",
  "provider": "wave",
  "phone": "2250500000000"
}
```

`provider` : `wave`, `mtn` ou `cinetpay`.
`phone` est requis pour MTN au format 225 suivi des 10 chiffres.
Le montant et l'étudiant sont déterminés côté serveur : seul le solde du loyer de la chambre actuellement occupée est payable.
Ne pas transmettre de montant ni utiliser un statut envoyé par le mobile comme preuve de paiement.

HTTP 200 retourne `success` et `data` contenant :
`id`, `reference`, `provider_code`, `loyer_id`, `amount`, `currency`, `status`, `label`, `checkout_url`, `paiement_id`, `receipt_url`.
Une transaction déjà en attente pour ce loyer est réutilisée.
Ouvrir `checkout_url` lorsqu'il est disponible (il peut être null, notamment pour MTN).
Le retour du prestataire utilise la page web existante ; le mobile suit le résultat via les endpoints ci-dessous.
Le serveur doit disposer d'une URL publique HTTPS dans APP_URL pour recevoir les notifications.

### Historique, détail et vérification

- `GET /paiements-en-ligne?per_page=15&page=1` : historique paginé dans `data.data`.
- `GET /paiements-en-ligne/{payment}` : détail local, sans déclencher de vérification externe.
- `POST /paiements-en-ligne/{payment}/verifier` : demander la vérification auprès du prestataire.

Statuts : `initializing`, `pending`, `succeeded`, `failed`, `review`.
Seul `succeeded` confirme le paiement comptabilisé. `review` nécessite une vérification par le service financier.
Une transaction commencée avant désactivation reste consultable et vérifiable avec ses identifiants enregistrés.
Création et vérification : maximum 10 requêtes par minute ; la vérification respecte aussi le délai interne entre deux interrogations.
Les notifications prestataires existantes restent sous `/api/payments/notifications` et ne sont pas des endpoints mobiles.

## États des lieux : liste, entrée et sortie

- `GET /etat-des-lieux?type_etat=entree&per_page=15&page=1` : liste paginée des états des lieux personnels, filtre facultatif `entree` ou `sortie`.
- `GET /etat-des-lieux/{inspection}` : détail avec pièces, éléments et photos.
- `POST /etat-des-lieux/eld` : créer l'état des lieux d'entrée.
- `PUT /etat-des-lieux/eld/{inspection}` : modifier une entrée non signée par le CROU.
- `GET /etudiant/etat-des-lieux-sortie` : dernière sortie, `data: null` s'il n'en existe pas.
- `POST` ou `PATCH /etudiant/etat-des-lieux-sortie/{inspection}/approuver` : ajouter uniquement la signature de l'étudiant.

Exemple de création d'entrée :

```json
{
  "type_etat": "entree",
  "date_etat": "2026-09-25",
  "nbre_cle_recus": 2,
  "observation_generale": "Chambre propre",
  "pieces": [{
    "name_piece": "Chambre",
    "elements": [
      {"name_element": "Sol", "etat": "bon"},
      {"name_element": "Porte", "etat": "mauvais", "observation": "Serrure abîmée"}
    ],
    "photos": []
  }]
}
```

La chambre, le bâtiment, la cité et l'étudiant viennent de l'assignation active.
`signature_crou` est interdit. Un état de sortie est établi par le CROU.
`pieces_json` est accepté comme chaîne JSON à la place de `pieces`, avec la même structure.
Les éléments acceptent `bon`, `moyen`, `mauvais` ; une observation est obligatoire pour `mauvais`.
Photos : data URLs PNG/JPEG/WebP, 6 maximum par pièce, 2 Mo par image, 12 Mo de nouvelles images par requête.
Une mise à jour peut conserver les chemins de photos déjà rattachées à cet état des lieux.
Sans champ `pieces`, les pièces existantes sont conservées.

Signature étudiant : image PNG/JPEG valide sous forme `data:image/png;base64,...`, 2 Mo maximum.
Une entrée existante ou verrouillée et une sortie déjà approuvée renvoient 409.
L'approbation exige d'abord la signature CROU (sinon 422).
Toutes les lectures et écritures utilisent l'identité du jeton ; un identifiant étudiant fourni dans le corps ne change pas le propriétaire.

## Documents et téléchargements

Référence détaillée des variables attendues et constats de revue au 6 octobre 2026 : [API des documents](api-documents.md).

- `GET /documents?per_page=15&page=1` : documents de la résidence publiés, à partir de leur version validée.
  Réponse paginée dans `data` ; chaque ligne fournit `id`, `type`, `title`, `reference`, `version_label`, `pdf_url`.
- `GET /documents/{document}/pdf` : PDF de la version publiée ; les brouillons et versions non publiées sont inaccessibles.
- `GET /mes-documents` : reçus validés/remboursés, états des lieux, justificatifs et décomptes de sortie personnels.
  Réponse `{success: true, data: [...]}` ; chaque ligne fournit `id`, `type`, `title`, `date`, `url`.
- `GET /mes-documents/recu/{id}` : reçu PDF.
- `GET /mes-documents/etat-des-lieux/{id}` : état des lieux PDF.
- `GET /mes-documents/justificatif/{id}` : justificatif (PDF/image ou redirection vers le stockage configuré).
- `GET /mes-documents/decompte/{id}` : décompte PDF.

Les URLs renvoyées sont des URLs **API**, sans besoin de session web.
Le client doit télécharger les fichiers avec Authorization: Bearer, puis ouvrir le fichier local.
Une simple ouverture dans un navigateur externe sans jeton renvoie 401.
Ne pas transmettre le jeton de l'application à un autre domaine en cas de redirection de stockage.
Les fichiers d'un autre étudiant renvoient 404.
Les réponses privées ne doivent pas être mises en cache.

## Erreurs complémentaires

- 404 : ressource absente ou n'appartenant pas à l'étudiant ;
- 422 : validation, réseau indisponible, absence de chambre active ou règle métier ;
- 429 : trop de tentatives ;
- 500 : erreur serveur à traiter comme telle, et non comme une liste vide.

Les anciennes URLs de demandes de sortie et maintenances avec `/{etudiant_id}` restent acceptées pour le propriétaire du jeton.

## Conventions et formats de réponse

Cette référence décrit le code local de `routes/api.php` et ses contrôleurs au 25 septembre 2026.
Les chemins des sections fonctionnelles sont relatifs à `/api/V1`, sauf les notifications de paiement.
Les identifiants entre accolades sont à remplacer par ceux retournés par l'application ; ne pas supposer qu'ils sont tous numériques.

| Accès | Signification |
|---|---|
| Public | Aucun middleware d'authentification déclaré sur la route API. |
| Bearer étudiant | Jeton émis par la connexion mobile ; étudiant actif, jeton non expiré. |
| Session admin | Cookies de connexion administrateur et contrôle de permission ; le jeton étudiant ne suffit pas. |
| Prestataire | Signature ou jeton de notification de paiement, distinct du jeton étudiant. |

Pour les requêtes JSON, ajouter `Content-Type: application/json`.
Pour les fichiers, utiliser `multipart/form-data` et laisser le client HTTP générer la frontière multipart.
Toujours envoyer `Accept: application/json` pour les opérations JSON : certains contrôleurs partagés avec le web renvoient une page ou une redirection sans cet en-tête.

Les enveloppes ne sont pas uniformes :

| Famille | Structure de succès |
|---|---|
| Référentiels | Tableau JSON directement à la racine |
| Connexion | Résultat du service étudiant, `derniere_campagne` et `auth` |
| Loyers/paiements | `etudiant`, `loyers`, `paiements`, `stats`, `impayes`, `prochainPaiement` à la racine |
| API mobiles récentes | `success`, `data`, parfois `message` |
| Dossiers/réadmission | `data`, avec message/success selon l'action |
| Incidents | Ressource Laravel : `data`, plus `links` et `meta` pour les listes |
| PDF/justificatifs/CSV | Réponse fichier, pas de JSON de succès |

Les objets issus directement des modèles peuvent contenir d'autres champs. Les exemples illustrent la structure sans garantir des données présentes dans une installation.
Validation Laravel habituelle : HTTP 422 avec `message` et `errors`.
Les anciens contrôleurs d'authentification, d'affectation et d'ordre de travail utilisent aussi HTTP 400.

## Inscription et précisions d'authentification

### `POST /auth/register`

Accès public. Crée le compte, une demande et traite les justificatifs, puis tente la finalisation automatique.
Utiliser multipart lorsqu'il y a des documents à joindre.

| Champs | Obligatoire / validation du formulaire |
|---|---|
| `genre` | Oui, identifiant présent dans Genre |
| `name` | Oui, texte, 255 caractères maximum |
| `date_naissance` | Oui, date antérieure à aujourd'hui |
| `lieu_naissance` | Oui, texte, 255 caractères maximum |
| `telephone` | Oui, unique, exactement 10 chiffres (espaces, points, tirets et indicatif `+225` retirés avant contrôle ; enregistré sous la forme `0700000000`) |
| `email` | Oui, email unique |
| `type_piece`, `num_piece`, `date_exp_piece` | Oui ; numéro de pièce unique |
| `matricule` | Oui, unique |
| `region`, `ville` | Oui, identifiants existants |
| `adresse`, `nationalite` | Oui ; maximum 255 et 100 caractères |
| `id_campagne` | Oui, campagne existante |
| `annee_academique_actuel` | Oui, texte, 9 caractères maximum |
| `moyenne` | Facultatif/nullable ; numérique entre 1 et 500 selon la validation actuelle |
| `redoublement`, `boursier`, `pmr` | Facultatifs/nullable ; 0 ou 1 |
| `cas_social` | Facultatif, texte, 500 caractères maximum |
| `save_as_draft` | Facultatif, booléen |
| `groupe_sanguin`, `distance` | Facultatifs, sans contrainte de type dans ce formulaire |

Le dépôt de demande consomme également `niveau`, `niveau_precedent`, `annee_academique_precedent`, `filiere`, `credit_valider`.
Le dépôt lit directement `moyenne` et `credit_valider` : inclure ces deux clés, éventuellement null, même si le formulaire ne les impose pas toutes.
Les fichiers sont transmis avec le slug du type de document comme nom de champ, obtenu via `GET /parametrage/type-document`.

Exemple de corps sans fichiers, avec identifiants à remplacer :

```json
{
  "genre": 1,
  "name": "Étudiant Exemple",
  "date_naissance": "2003-05-12",
  "lieu_naissance": "Bondoukou",
  "telephone": "0700000000",
  "email": "etudiant@example.com",
  "type_piece": "IDENTIFIANT_TYPE",
  "num_piece": "EXEMPLE-001",
  "date_exp_piece": "2030-12-31",
  "matricule": "MAT-EXEMPLE-001",
  "region": 1,
  "ville": 1,
  "adresse": "Bondoukou",
  "nationalite": "Ivoirienne",
  "id_campagne": "IDENTIFIANT_CAMPAGNE",
  "annee_academique_actuel": "2026-2027",
  "niveau": "IDENTIFIANT_NIVEAU",
  "moyenne": 12,
  "credit_valider": 60
}
```


HTTP 200 : `message`, `eligible`, `etat_dossier`, `demande_id`.
HTTP 422 : validation initiale ; HTTP 400 : échec traité par le contrôleur.
L'inscription ne renvoie pas de jeton et ne définit pas de champ `password` dans ce formulaire.
Un compte existant doit utiliser la connexion puis le parcours de réadmission.

### Connexion : réponse et durée du jeton

`POST /auth/authenticate` est public. `email` et `password` sont obligatoires ; `device_name` est facultatif, avec `mobile` par défaut.
La recherche utilise l'email principal de l'étudiant (`email1` en base).
Le compte doit avoir `statut = 1`.

Extrait de réponse HTTP 200 :

```json
{
  "success": true,
  "data": {},
  "derniere_campagne": {},
  "auth": {
    "token": "JETON_A_CONSERVER",
    "token_type": "Bearer",
    "expires_at": "2026-12-24T12:00:00+00:00"
  }
}
```


`data` et `derniere_campagne` ci-dessus sont schématiques et dépendent des services.
Chaque connexion crée un jeton valable 90 jours. Aucun endpoint de renouvellement de jeton n'est déclaré.
Mauvais identifiants ou compte désactivé : HTTP 400 avec `message`.
Déconnexion : HTTP 200 `{success: true, message: ...}`.

## Référentiels et annonces

Accès actuellement public pour toutes les routes de cette section.

| Méthode / chemin | Entrées | Réponse |
|---|---|---|
| `GET /parametrage/region` | Aucune | Tableau des régions |
| `GET /parametrage/ville/{region_id}` | Région dans le chemin | Tableau des villes de la région |
| `GET /parametrage/type-document` | Aucune | Types de documents actifs, notamment leurs slugs |
| `GET /parametrage/type-chanbre` | Aucune | Tableau des types de chambre |
| `GET /parametrage/niveau-etude` | Aucune | Tableau des niveaux |
| `GET /parametrage/type-piece` | Aucune | Tableau des types de pièce d'identité |
| `GET /parametrage/groupe-sanguin` | Aucune | Tableau des groupes sanguins |
| `GET /parametrage/filiere` | Aucune | Tableau des filières |
| `GET /parametrage/genre` | Aucune | Tableau des genres |
| `GET /parametrage/config` | Aucune | Objet décrit ci-dessous |
| `GET /parametrage/annonces/{etudiant_id}` | `canal`, `priorite`, `search` facultatifs en query | `{success: true, data: [...]}` |

Le chemin existant est bien `type-chanbre` (orthographe du code).
Les listes de référentiels renvoient 200, ou un message avec HTTP 400 en cas d'erreur interceptée.

`config` renvoie les clés `config`, `profil`, `notif`, `paiement`, `taxes`, `devises`, `integrations`, `securite`, qui contiennent actuellement le même objet de configuration. Ce n'est pas la liste des moyens de paiement disponibles.

Les annonces sont filtrées selon l'étudiant indiqué, triées par publication décroissante et limitées à 5 résultats.
`search` cherche dans le titre et le contenu. Cette route ne vérifie pas l'identité par jeton dans son implémentation actuelle.

## Campagnes et contrats

### `GET /campagne/derniere-campagne`

Accès public. Aucune entrée. HTTP 200 `{message: ...}`, où `message` contient le résultat structuré du service de campagnes actives avec statistiques, et non nécessairement une chaîne.
Malgré le nom au singulier, le contrôleur demande la liste des campagnes actives.

### `POST /campagne/demande/acceptation_refus`

Accès Bearer étudiant.

| Champ | Règle |
|---|---|
| `action` | Requis : `accepter` ou `refuser` |
| `assignation_id` | Requis, chaîne |
| `demande_id` | Requis |
| `motif_refus` | Requis si refus, chaîne, maximum 500 caractères |
| `etudiant_id` | Facultatif ; l'identité effective vient du jeton |

```json
{
  "action": "accepter",
  "assignation_id": "IDENTIFIANT_ASSIGNATION",
  "demande_id": "IDENTIFIANT_DEMANDE"
}
```


Succès 200 : `{message: ...}`.
Le contrôleur utilise 400 pour les erreurs de validation ou de traitement, 404 si la demande n'est pas trouvée et 403 pour un accès non autorisé.

### Contrats et approbation — mise à jour du 10 octobre 2026

Les routes /contrat/{id?} et /campagne/contrat/{id} sont désormais protégées par Bearer, vérifient le propriétaire et utilisent le service de contrat actuel. Elles ont des noms internes distincts.

Pour l’intégration mobile, utiliser GET /contrats/actuel, GET /contrats/{contract}/pdf et POST ou PATCH /contrats/{contract}/approuver, avec l’empreinte document_hash et le consentement explicite. La signature d’un état des lieux d’entrée ou de sortie peut être envoyée séparément à POST ou PATCH /etat-des-lieux/{inspection}/approuver.

Voir [la référence complète des états des lieux, photos et approbations](api-etats-des-lieux-approbations.md) pour les payloads, les réponses et les codes d’erreur.

## Entrée en logement, dossiers et réadmission

Toutes les routes de cette section utilisent Bearer étudiant.

### `GET /entree-logement?page=1`

Aucun corps. Retourne `messages` (pagination de 20 par page) et `appointments` (liste des rendez-vous de l'étudiant, du plus récent au plus ancien).
Envoyer Accept JSON pour éviter le rendu de la page web.

### `GET /dossiers/{demande}`

HTTP 200 : `{data: ...}`, avec le dossier, `transitions` et `proposition.chambre`.
Le dossier doit appartenir à l'étudiant. Accès à un autre propriétaire : 403 ; identifiant introuvable : 404.

### `POST /dossiers/{demande}/transition`

```json
{
  "etat": "accepte",
  "assignation_id": "IDENTIFIANT_ASSIGNATION",
  "motif": "Proposition acceptée"
}
```


- `etat` requis : `soumis`, `accepte`, `refuse`, `annule`.
- `motif` facultatif, texte, 5 000 caractères maximum.
- `assignation_id` requis pour accepter ou refuser.

Le service vérifie que la transition est possible depuis l'état courant. Réponse 200 : `{data: ...}` avec dossier et relations rechargées. Une valeur autorisée dans le formulaire ne garantit pas qu'elle soit autorisée depuis tout état du dossier.

### `GET /readmission`

HTTP 200 : `{data: {campagnes: [...], niveaux: [...]}}`.
Les campagnes retournées sont actives ; les niveaux sont triés par nom.

### `POST /readmission`

```json
{
  "campagne_id": "IDENTIFIANT_CAMPAGNE",
  "niveau": "IDENTIFIANT_NIVEAU",
  "moyenne": 12.5,
  "credit_valider": 60,
  "boursier": false,
  "cas_social": null
}
```


Campagne et niveau existants obligatoires. Moyenne facultative entre 0 et 20 ; crédits facultatifs non négatifs ; boursier booléen facultatif ; cas social de 2 000 caractères maximum.
Le séjour précédent doit être clôturé ou l'inscription archivée. La campagne doit autoriser le dépôt et la réadmission ; pas de séjour/réservation encore en cours, ni de dossier concurrent actif pour cette campagne. Les sanctions bloquantes sont vérifiées.
HTTP 201 : `{success: true, message: ..., data: dossier}`.
Refus métier : HTTP 422, généralement dans `errors.campagne_id`.

## Carte et géolocalisation

Toutes ces routes sont actuellement publiques, en GET.

| Chemin | Paramètres query | Réponse |
|---|---|---|
| `/map` | Aucun | `campuses`, `cites`, `batiments` |
| `/map/campuses` | `region_id` facultatif | `success`, `count`, `data` |
| `/map/cites` | `campus_code`, `region_id` facultatifs | `success`, `count`, `data` |
| `/map/batiments` | `code_cite`, `code_campus` facultatifs | `success`, `count`, `data` |
| `/map/stats` | Aucun | `success`, `data` |
| `/map/nearby` | latitude, longitude, rayon | `success`, `radius_km`, `center`, `count`, `data` |
| `/map/export` | `format=json` par défaut ou `csv` | JSON ou fichier CSV |

Les listes géographiques retiennent les coordonnées renseignées. Les listes campus/cités excluent le statut `ferme` ; statistiques, proximité et export utilisent le filtre `supprimé`. Les bâtiments doivent être valides.

Les objets de campus comprennent notamment `id_campus`, `code_campus`, `name`, `latitude`, `longitude`, `adresse`, `telephone1`, `email1`, `description`.
Les cités ajoutent leurs codes, campus, capacité et type. Les bâtiments ajoutent les codes cité/campus, type d'occupation, total d'étages et de chambres.

Recherche à proximité :

```http
GET /api/V1/map/nearby?latitude=6.877&longitude=-6.45&radius_km=10
Accept: application/json
```


Latitude obligatoire entre -90 et 90, longitude entre -180 et 180 ; rayon facultatif entre 1 et 50 km, 10 par défaut.
Retourne les campus par distance croissante, avec `distance_km` arrondi à deux décimales. Paramètres invalides : 422.

Statistiques : `campuses_total`, `campuses_with_coords`, `cites_total`, `cites_with_coords`, `batiments_total`, `batiments_with_coords`.
Export JSON : `success`, `campuses`, `cites`, `batiments`.
Export CSV : fichier `map-data.csv`, colonnes Type, Code, Nom, Latitude, Longitude, Adresse.

Limite actuelle : le filtre régional des cités retient uniquement le premier campus trouvé dans la région ; si aucun n'est trouvé, ce filtre n'est pas appliqué.

## Incidents disciplinaires

### Routes étudiant historiques

Aucun middleware Bearer n'est déclaré sur ce groupe.

| Route déclarée | Fonction / état |
|---|---|
| `GET /incidents-disciplinaires/{etudiant_id}` | Liste du déclarant ; filtres `statut`, `per_page`, `page` |
| `POST /incidents-disciplinaires/{etudiant_id}` | Déclarer un incident |
| `GET /incidents-disciplinaires/{report}` | Route de détail déclarée mais méthode `show` absente du contrôleur |

Les deux routes GET ont la même forme d'URL. Ne pas considérer le détail comme opérationnel : il entre en conflit avec la liste et sa méthode n'existe pas.
La liste prévoit 15 éléments par défaut, `per_page` ramené dans l'intervalle 1–50, tri par date d'incident décroissante.

Déclaration :

```json
{
  "date_incident": "2026-09-24 20:00:00",
  "nature": "tapage",
  "lieu": "Couloir du bâtiment",
  "description": "Nuisances sonores répétées dans le couloir de la résidence.",
  "temoins": null,
  "gravite": "moyenne",
  "confidentiel": false
}
```


Date requise et non future ; lieu requis, maximum 255 caractères ; description requise, 20–5 000 caractères ; témoins facultatifs, maximum 2 000.
Nature : `tapage`, `violence`, `harcelement`, `degradation`, `vol`, `non_respect_reglement`, `autre`.
Gravité : `faible`, `moyenne`, `grave`, `critique`. Confidentialité booléenne facultative.
Une chambre active est nécessaire. Le rapport est créé au statut `transmis` et un événement d'historique est enregistré.
HTTP 201 : `data` (ressource du rapport) et `message` ; validation ou absence de chambre : 422.

Champs de la ressource : `id`, `numero`, `source`, `confidentiel`, `nature`, `description`, `temoins`, `lieu`, `gravite`, `statut`, `decision`, `sanction_decidee`, dates de suivi/création/modification. `residence`, `etudiant`, `historique`, `sanction` apparaissent lorsque leurs relations sont chargées.

### Service sécurité

Session administrateur nécessaire ; contrôle `permission:discipline.manage`. Le middleware tient aussi compte de ses règles d'administrateur et de configuration initiale.
La transition vérifie en plus explicitement `hasPermission('discipline.manage')`.

| Route | Description |
|---|---|
| `GET /service-securite/incidents-disciplinaires` | Liste ; filtres `statut`, `gravite`, `per_page`, `page` |
| `GET /service-securite/incidents-disciplinaires/{report}` | Détail, historique et sanction |
| `PATCH /service-securite/incidents-disciplinaires/{report}/transition` | Changement de statut |

Pagination : 15 par défaut, maximum 50. Les listes utilisent `data`, `links`, `meta`.

| Action | États de départ autorisés | Nouvel état |
|---|---|---|
| transmettre | brouillon, retourne | transmis |
| prendre_en_charge | transmis | en_cours |
| retourner | transmis, en_cours | retourne |
| decider | en_cours | decision_prise |
| cloturer | decision_prise | cloture |

```json
{
  "action": "decider",
  "commentaire": "Dossier examiné.",
  "decision": "Avertissement.",
  "sanction": "avertissement",
  "etudiant_sanctionne_id": "IDENTIFIANT_ETUDIANT"
}
```


`commentaire` requis pour retourner/décider, `decision` requise pour décider ; textes limités à 5 000 caractères.
`sanction` requise pour décider : `aucune`, `avertissement`, `blame`, `suspension`, `expulsion`.
Pour une sanction autre qu'aucune, `etudiant_sanctionne_id` doit identifier un étudiant existant.
Pour une suspension, `sanction_fin` est requise, aujourd'hui ou après.
HTTP 200 : ressource avec historique, sanction et message. Transition incompatible : 422 ; accès refusé : 401/403.

Le groupe utilise le middleware web du projet. Sa classe personnalisée `VerifyCsrfToken` ne vérifie actuellement pas de jeton CSRF ; cette description ne suppose pas la protection standard Laravel.

## Ancienne création d'ordre de travail

### `POST /ordre-travail/store`

Accès actuellement public. Le contrôleur lit `etudiant_id` dans le corps, récupère le dossier et sa première assignation, puis transmet les données au service de création.
Les champs usuels sont `titre`, `description`, `categorie`, `priorite`, `type_intervention`, `etudiant_id`.
Le statut initial est `en_attente`.
Le contrôleur reçoit une Request générique : les règles de `OrdreTravailRequest` ne sont pas appliquées par cette route.
HTTP 200 : `{message: ...}` incluant le numéro de suivi ; erreurs interceptées : 400.
Pour l'intégration mobile, utiliser `POST /etudiant/maintenances`, documenté plus haut, qui valide les entrées et déduit l'identité du jeton.

## Notifications des prestataires de paiement

Ces chemins sont complets, hors du préfixe V1. Limite commune : 120 requêtes/minute.
Ils servent aux prestataires ; ils ne remplacent pas la vérification d'un paiement par le mobile.

### `POST /api/payments/notifications/wave`

Corps JSON brut signé ; en-tête `Wave-Signature`.
Le contrôleur exige `data.client_reference`, chaîne de 40 caractères maximum, associée à un paiement Wave.
Le secret utilisé est celui enregistré avec la transaction. Taille du corps limitée à 65 536 octets.
Signature invalide : 403 ; référence invalide : 422 ; paiement inconnu : 404 ; corps trop volumineux : 413.

### `GET|POST /api/payments/notifications/{payment}/{token}`

`payment` est l'identifiant du paiement en ligne ; `token` est le jeton spécifique généré au lancement.
Concerne MTN et CinetPay. Type de prestataire ou jeton incorrect : 403.
GET accuse réception uniquement.
POST CinetPay exige `cpm_trans_id` égal à la référence ; `cpm_site_id`, s'il est présent, doit correspondre à la clé marchand de la transaction. Écart : 422.

Succès pour les notifications : HTTP 200 `{"received":true}`.
Le serveur programme une vérification externe pour les transactions non finalisées ; il ne comptabilise pas directement le montant ou statut reçu dans la notification.

## Exemples d'utilisation HTTP

Remplacer le domaine et les valeurs d'exemple. Ces commandes sont au format shell compatible Bash ; dans PowerShell, utiliser `curl.exe` et adapter les continuations de ligne.

```bash
curl -X POST "https://votre-domaine/api/V1/auth/authenticate" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"etudiant@example.com","password":"MOT_DE_PASSE","device_name":"Mobile"}'

curl "https://votre-domaine/api/V1/paiement?per_page=10&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer VOTRE_JETON"

curl "https://votre-domaine/api/V1/mes-documents/recu/IDENTIFIANT" \
  -H "Authorization: Bearer VOTRE_JETON" \
  -o recu.pdf
```


Pour les champs de dates d'exemple, choisir une valeur respectant les contraintes au moment de l'appel.
En particulier, la sortie doit être au moins 15 jours après la date serveur et la disponibilité de maintenance ne doit pas être passée.

## Limites constatées lors de l'inventaire

- Incidents étudiant : deux routes GET concurrentes et méthode de détail absente.
- Contrats : deux noms internes identiques et absence de middleware Bearer sur les routes API historiques.
- Annonces, anciens incidents et ancien ordre de travail : identifiant étudiant fourni par le client, sans authentification déclarée sur ces routes.
- Réponses : pagination, enveloppes et codes d'erreur différents selon les générations de contrôleurs.
- Inscription : le dépôt consomme des champs supplémentaires qui ne sont pas tous imposés par le formulaire initial.
- Cette documentation décrit les déclarations et le code lus ; elle ne constitue pas une vérification en production de chaque endpoint.

## Inventaire exhaustif des routes API

Le tableau suivant provient de `php artisan route:list --path=api --json`.
Les chemins sont complets. GET inclut également HEAD lorsque Laravel le déclare.

| Méthodes | Chemin complet | Accès | Action |
|---|---|---|---|
| POST | `/api/V1/auth/authenticate` | Public (déclaration actuelle) | `api\etudiant\auth\authController@authenticate` |
| POST | `/api/V1/auth/logout` | Bearer étudiant | `api\etudiant\auth\authController@logout` |
| POST | `/api/V1/auth/register` | Public (déclaration actuelle) | `api\etudiant\auth\authController@postRegister` |
| GET / HEAD | `/api/V1/campagne/contrat/{id}` | Bearer étudiant, propriétaire | `api\etudiant\ContractApprovalController@legacyPdf` |
| POST | `/api/V1/campagne/demande/acceptation_refus` | Bearer étudiant | `api\campagne\campagneController@AcceptOrRefuseDemande` |
| GET / HEAD | `/api/V1/campagne/derniere-campagne` | Public (déclaration actuelle) | `api\campagne\campagneController@index` |
| GET / HEAD | `/api/V1/contrat/{id?}` | Bearer étudiant, propriétaire | `api\etudiant\ContractApprovalController@legacyPdf` |
| GET / HEAD | `/api/V1/documents` | Bearer étudiant | `api\etudiant\DocumentController@index` |
| GET / HEAD | `/api/V1/documents/{document}/pdf` | Bearer étudiant | `etudiant\DocumentController@pdf` |
| GET / HEAD | `/api/V1/dossiers/{demande}` | Bearer étudiant | `DossierWorkflowController@show` |
| POST | `/api/V1/dossiers/{demande}/transition` | Bearer étudiant | `DossierWorkflowController@student` |
| GET / HEAD | `/api/V1/entree-logement` | Bearer étudiant | `HousingEntryController@student` |
| GET / HEAD | `/api/V1/etat-des-lieux` | Bearer étudiant | `api\etudiant\InspectionController@index` |
| POST | `/api/V1/etat-des-lieux/eld` | Bearer étudiant | `api\etudiant\InspectionController@store` |
| PUT | `/api/V1/etat-des-lieux/eld/{inspection}` | Bearer étudiant | `api\etudiant\InspectionController@update` |
| GET / HEAD | `/api/V1/etat-des-lieux/{inspection}` | Bearer étudiant | `api\etudiant\InspectionController@show` |
| POST | `/api/V1/etudiant/demandes-sortie` | Bearer étudiant | `api\etudiant\StudentMobileController@submitDepartureRequest` |
| GET / HEAD | `/api/V1/etudiant/demandes-sortie/{etudiant_id?}` | Bearer étudiant | `api\etudiant\StudentMobileController@departureRequests` |
| GET / HEAD | `/api/V1/etudiant/etat-des-lieux-sortie` | Bearer étudiant | `api\etudiant\InspectionController@latestExit` |
| POST / PATCH | `/api/V1/etudiant/etat-des-lieux-sortie/{inspection}/approuver` | Bearer étudiant | `api\etudiant\InspectionController@approve` |
| POST | `/api/V1/etudiant/maintenances` | Bearer étudiant | `api\etudiant\StudentMobileController@submitMaintenance` |
| GET / HEAD | `/api/V1/etudiant/maintenances/{etudiant_id?}` | Bearer étudiant | `api\etudiant\StudentMobileController@maintenances` |
| GET / HEAD | `/api/V1/incidents-disciplinaires/{etudiant_id}` | Public (déclaration actuelle) | `api\discipline\StudentDisciplinaryReportController@index` |
| POST | `/api/V1/incidents-disciplinaires/{etudiant_id}` | Public (déclaration actuelle) | `api\discipline\StudentDisciplinaryReportController@store` |
| GET / HEAD | `/api/V1/incidents-disciplinaires/{report}` | Public (déclaration actuelle) | `api\discipline\StudentDisciplinaryReportController@show` |
| GET / HEAD | `/api/V1/map` | Public (déclaration actuelle) | `api\campagne\MapController@index` |
| GET / HEAD | `/api/V1/map/batiments` | Public (déclaration actuelle) | `api\campagne\MapController@getBatiments` |
| GET / HEAD | `/api/V1/map/campuses` | Public (déclaration actuelle) | `api\campagne\MapController@getCampuses` |
| GET / HEAD | `/api/V1/map/cites` | Public (déclaration actuelle) | `api\campagne\MapController@getCites` |
| GET / HEAD | `/api/V1/map/export` | Public (déclaration actuelle) | `api\campagne\MapController@exportData` |
| GET / HEAD | `/api/V1/map/nearby` | Public (déclaration actuelle) | `api\campagne\MapController@searchNearby` |
| GET / HEAD | `/api/V1/map/stats` | Public (déclaration actuelle) | `api\campagne\MapController@getStats` |
| GET / HEAD | `/api/V1/mes-documents` | Bearer étudiant | `api\etudiant\DocumentController@personal` |
| GET / HEAD | `/api/V1/mes-documents/decompte/{id}` | Bearer étudiant | `etudiant\DocumentController@settlement` |
| GET / HEAD | `/api/V1/mes-documents/etat-des-lieux/{id}` | Bearer étudiant | `etudiant\DocumentController@inventory` |
| GET / HEAD | `/api/V1/mes-documents/justificatif/{id}` | Bearer étudiant | `etudiant\DocumentController@attachment` |
| GET / HEAD | `/api/V1/mes-documents/recu/{id}` | Bearer étudiant | `etudiant\DocumentController@receipt` |
| POST | `/api/V1/ordre-travail/store` | Public (déclaration actuelle) | `api\ordreTravail\OrdreTravailController@store` |
| GET / HEAD | `/api/V1/paiement/{etudiant_id?}` | Bearer étudiant | `api\paiement\paiementController@getPaiementData` |
| GET / HEAD | `/api/V1/paiements` | Bearer étudiant | `api\paiement\paiementController@getPaiementData` |
| GET / HEAD | `/api/V1/paiements-en-ligne` | Bearer étudiant | `api\paiement\OnlinePaymentController@index` |
| POST | `/api/V1/paiements-en-ligne` | Bearer étudiant | `api\paiement\OnlinePaymentController@store` |
| GET / HEAD | `/api/V1/paiements-en-ligne/moyens` | Bearer étudiant | `api\paiement\OnlinePaymentController@providers` |
| GET / HEAD | `/api/V1/paiements-en-ligne/{payment}` | Bearer étudiant | `api\paiement\OnlinePaymentController@show` |
| POST | `/api/V1/paiements-en-ligne/{payment}/verifier` | Bearer étudiant | `api\paiement\OnlinePaymentController@check` |
| GET / HEAD | `/api/V1/parametrage/annonces/{etudiant_id}` | Public (déclaration actuelle) | `api\parametrage\parametrageController@annonces` |
| GET / HEAD | `/api/V1/parametrage/config` | Public (déclaration actuelle) | `api\parametrage\parametrageController@config` |
| GET / HEAD | `/api/V1/parametrage/filiere` | Public (déclaration actuelle) | `api\parametrage\parametrageController@Filiere` |
| GET / HEAD | `/api/V1/parametrage/genre` | Public (déclaration actuelle) | `api\parametrage\parametrageController@genre` |
| GET / HEAD | `/api/V1/parametrage/groupe-sanguin` | Public (déclaration actuelle) | `api\parametrage\parametrageController@groupeSanguin` |
| GET / HEAD | `/api/V1/parametrage/niveau-etude` | Public (déclaration actuelle) | `api\parametrage\parametrageController@niveauEtude` |
| GET / HEAD | `/api/V1/parametrage/region` | Public (déclaration actuelle) | `api\parametrage\parametrageController@region` |
| GET / HEAD | `/api/V1/parametrage/type-chanbre` | Public (déclaration actuelle) | `api\parametrage\parametrageController@typeChanbre` |
| GET / HEAD | `/api/V1/parametrage/type-document` | Public (déclaration actuelle) | `api\parametrage\parametrageController@typeDocument` |
| GET / HEAD | `/api/V1/parametrage/type-piece` | Public (déclaration actuelle) | `api\parametrage\parametrageController@TypePiece` |
| GET / HEAD | `/api/V1/parametrage/ville/{region_id}` | Public (déclaration actuelle) | `api\parametrage\parametrageController@ville` |
| GET / HEAD | `/api/V1/readmission` | Bearer étudiant | `etudiant\ReadmissionController@show` |
| POST | `/api/V1/readmission` | Bearer étudiant | `etudiant\ReadmissionController@store` |
| GET / HEAD | `/api/V1/service-securite/incidents-disciplinaires` | Session admin | `api\discipline\SecurityDisciplinaryReportController@index` |
| GET / HEAD | `/api/V1/service-securite/incidents-disciplinaires/{report}` | Session admin | `api\discipline\SecurityDisciplinaryReportController@show` |
| PATCH | `/api/V1/service-securite/incidents-disciplinaires/{report}/transition` | Session admin | `api\discipline\SecurityDisciplinaryReportController@transition` |
| POST | `/api/payments/notifications/wave` | Prestataire | `api\paiement\OnlinePaymentNotificationController@wave` |
| GET / POST / HEAD | `/api/payments/notifications/{payment}/{token}` | Prestataire | `api\paiement\OnlinePaymentNotificationController@notification` |

### Photos facultatives par élément (9 octobre 2026)

Chaque élément d’un état des lieux accepte un champ `photo` facultatif : omis ou `null` pour ne pas joindre de photo, ou une image JPEG, PNG ou WebP encodée en data URL base64 (2 Mo maximum après préparation). La réponse fournit le chemin `photo` et son `photo_url`. Pour conserver une image lors d’une modification, renvoyer son chemin déjà rattaché au même constat ; pour la retirer, envoyer `null`. La limite commune de 12 Mo concerne toutes les nouvelles photos du constat, pièces et éléments compris. Les règles de signature et de propriété du constat s’appliquent aussi aux photos.

Sur le web, chaque élément propose « Prendre une photo » et « Choisir une photo », un aperçu et « Retirer ». L’entrée est saisie par l’étudiant ; la sortie est saisie par le CROU. Les images d’entrée ne sont pas automatiquement reprises comme photos de sortie. Le PDF associe chaque photo à sa pièce et à son élément.

## Compléments API — 10 octobre 2026

La référence à jour est [API des états des lieux et approbation des documents](api-etats-des-lieux-approbations.md). La liste et le détail d’un état des lieux renvoient les URL des photos de pièces et d’éléments. Les réponses exposent les actions autorisées et les liens PDF et approbation. Les contrats personnels enregistrés figurent désormais dans /mes-documents.
