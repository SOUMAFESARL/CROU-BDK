# API des états des lieux et approbation des documents — CROU de Bondoukou

Mise à jour : **10 octobre 2026**. Document d’intégration mobile, basé sur les routes, contrôleurs et tests du projet.

## Authentification

Tous les chemins ci-dessous sont relatifs à **`/api/V1`** ; `V1` est sensible à la casse.

```http
Authorization: Bearer VOTRE_TOKEN
Accept: application/json
Content-Type: application/json
```

Le token est retourné par `POST /auth/authenticate`, dans `auth.token`. L’étudiant doit être actif et le token valide. Le propriétaire des constats et contrats est déterminé par le token : ne pas envoyer un identifiant étudiant pour choisir le propriétaire.

Les succès JSON utilisent `{"success": true, "data": ...}`. Les téléchargements PDF renvoient les octets du fichier, sans enveloppe JSON. Les réponses de consultation et d’approbation portent `Cache-Control: private, no-store`.

## 1. API d’états des lieux

| Méthode | Route | Fonction |
| --- | --- | --- |
| GET | `/etat-des-lieux` | Liste personnelle paginée, avec pièces, éléments et photos |
| GET | `/etat-des-lieux/{inspection}` | Affichage détaillé d’une entrée ou d’une sortie personnelle |
| POST | `/etat-des-lieux/eld` | Création de l’entrée du logement actif |
| PUT | `/etat-des-lieux/eld/{inspection}` | Modification d’une entrée non verrouillée |
| POST ou PATCH | `/etat-des-lieux/{inspection}/approuver` | Signature étudiante distincte d’une entrée ou d’une sortie |
| GET | `/etudiant/etat-des-lieux-sortie` | Dernière sortie personnelle ; `data: null` si absente |
| POST ou PATCH | `/etudiant/etat-des-lieux-sortie/{inspection}/approuver` | Ancienne route conservée, réservée aux sorties |
| GET | `/mes-documents/etat-des-lieux/{id}` | PDF du constat, avec les photos |

`inspection` et `id` désignent `etat_des_lieux_id`, pas l’identifiant de chambre, de pièce ou d’élément. La liste accepte `type_etat` (`entree` ou `sortie`), `per_page` (entier 1–100, défaut 15) et `page` (utiliser un entier positif).

L’étudiant saisit l’entrée. Le CROU établit la sortie sur son interface ; l’API étudiant permet sa consultation et sa signature, sans modifier ses constatations.

### Les API d’affichage ramènent-elles les photos ?

**Oui : le détail, la liste et la dernière sortie retournent les photos des pièces et des éléments. Le JSON contient leurs chemins et leurs URL, pas les images binaires.** L’application charge les URL pour afficher les images.

```http
GET /api/V1/etat-des-lieux/IDENTIFIANT_CONSTAT
```

Exemple partiel de réponse :

```json
{
  "success": true,
  "data": {
    "etat_des_lieux_id": "IDENTIFIANT_CONSTAT",
    "type_etat": "entree",
    "pieces": [{
      "name_piece": "Chambre",
      "elements": [
        {
          "name_element": "Sol",
          "etat": "bon",
          "observation": null,
          "photo": "edl/photos/photo-sol.jpg",
          "photo_url": "https://votre-domaine/storage/app/public/edl/photos/photo-sol.jpg"
        },
        {
          "name_element": "Porte",
          "etat": "bon",
          "photo": null,
          "photo_url": null
        }
      ],
      "images": [{
        "chemin_fichier": "edl/photos/vue-chambre.jpg",
        "url": "https://votre-domaine/storage/app/public/edl/photos/vue-chambre.jpg"
      }]
    }],
    "can_edit": true,
    "can_approve": true,
    "approval_status": "pending_student",
    "pdf_url": "https://votre-domaine/api/V1/mes-documents/etat-des-lieux/IDENTIFIANT_CONSTAT",
    "approval_url": "https://votre-domaine/api/V1/etat-des-lieux/IDENTIFIANT_CONSTAT/approuver"
  }
}
```

| Image | Chemin dans le détail JSON | Champ à utiliser pour l’affichage |
| --- | --- | --- |
| Photo d’un élément | `data.pieces[].elements[]` | `photo_url` |
| Photo générale d’une pièce | `data.pieces[].images[]` | `url` |

Dans une liste paginée, les constats sont dans `data.data[]` : les photos d’éléments se trouvent donc dans `data.data[].pieces[].elements[].photo_url`.

Exemple JavaScript, `constat` étant l’objet `data` du détail :

```javascript
const images = constat.pieces.flatMap(piece => [
  ...piece.elements.filter(element => element.photo_url).map(element => ({
    label: `${piece.name_piece} — ${element.name_element}`,
    url: element.photo_url,
  })),
  ...piece.images.map(image => ({ label: piece.name_piece, url: image.url })),
]);
```

Utiliser les URL retournées telles quelles. Elles suivent la configuration `app.storage_public` du CROU, avec repli sur le chemin Laravel standard si cette configuration est vide. Ne pas reconstruire une URL en ajoutant arbitrairement `/storage/`. Les fichiers des photos utilisent actuellement le stockage public ; l’API JSON, elle, exige Bearer et filtre les constats par propriétaire.

### Création et modification de l’entrée

Les identifiants étudiant, chambre, bâtiment et cité viennent de l’assignation active. Ne pas envoyer `signature_crou`. `type_etat`, s’il est envoyé, doit valoir `entree`.

```json
{
  "type_etat": "entree",
  "date_etat": "2026-10-10",
  "nbre_cle_recus": 1,
  "observation_generale": "Constat à l’arrivée",
  "pieces": [{
    "name_piece": "Chambre",
    "ordre": 1,
    "elements": [
      {"name_element": "Sol", "etat": "bon", "ordre": 1, "photo": null},
      {"name_element": "Porte", "etat": "mauvais", "observation": "Serrure abîmée", "ordre": 2, "photo": "data:image/jpeg;base64,CONTENU_BASE64"}
    ],
    "photos": []
  }]
}
```

`CONTENU_BASE64` est un emplacement d’exemple, à remplacer par les octets base64 d’une véritable image.

| Champ | Règle |
| --- | --- |
| `date_etat` | Facultatif ; date valide |
| `nbre_cle_recus` | Facultatif ; entier 0–100 |
| `observation_generale` | Facultative ; 10 000 caractères maximum |
| `signature_etudiant` | Facultative sur création/modification ; PNG/JPEG en data URL, ou `null` |
| `pieces` | Facultatif ; 1–30 pièces lorsqu’il est envoyé |
| `pieces[].name_piece` | Obligatoire ; 255 caractères maximum |
| `pieces[].ordre` | Facultatif ; entier positif ou nul |
| `pieces[].elements` | Facultatif ; 100 éléments maximum par pièce |
| `elements[].name_element` | Obligatoire ; 255 caractères maximum |
| `elements[].etat` | Obligatoire : `bon`, `moyen` ou `mauvais` |
| `elements[].observation` | 2 000 caractères maximum ; obligatoire si l’état est `mauvais` |
| `elements[].ordre` | Facultatif ; entier positif ou nul |
| `elements[].photo` | Une photo facultative par élément, ou `null` |
| `pieces[].photos` | Facultatif ; 6 photos générales maximum par pièce |

Succès de création : `201`. Une entrée existe déjà pour ce logement : `409`, utiliser ensuite la modification. Une entrée signée par le CROU est verrouillée : modification refusée avec `409`.

**Lors d’une modification, l’envoi de `pieces` remplace toutes les pièces et tous les éléments.** Renvoyer le tableau complet à conserver. Pour modifier uniquement les clés ou l’observation générale, omettre `pieces`. Le champ historique `pieces_json` reste accepté comme chaîne JSON du tableau, uniquement lorsque `pieces` est absent.

### Ajout, conservation et retrait d’une photo

- Ajout/remplacement : `photo` contient une data URL JPEG, PNG ou WebP. Ces routes ne traitent pas un fichier multipart brut.
- Conservation : renvoyer le chemin `photo` déjà rattaché au même constat, pas son URL `photo_url`.
- Retrait : envoyer `photo: null` dans l’élément, avec les autres pièces et éléments à conserver.
- Sans photo : omettre `photo` ou envoyer `null`. L’image reste facultative, même en mauvais état ; le commentaire reste obligatoire dans ce cas.

Limites : **2 Mo par image enregistrée**, **24 millions de pixels**, chaîne base64 de 2 800 000 caractères maximum et **12 Mo au total pour toutes les nouvelles photos de la requête**, pièces et éléments compris. Une fausse image, une URL externe ou un chemin non rattaché au constat est refusé avant écriture des photos.

Le payload de saisie ne doit pas reprendre tous les champs de la réponse. Ne pas envoyer `photo_url`, les identifiants d’éléments ou leurs dates : reconstruire les objets avec les champs admis ci-dessus. Les photos générales de pièce sont envoyées dans `photos`, puis retournées dans `images`.

### Approbation / signature de l’état des lieux

```http
POST /api/V1/etat-des-lieux/IDENTIFIANT_CONSTAT/approuver
```

```json
{"signature_etudiant": "data:image/png;base64,CONTENU_BASE64"}
```

La signature est obligatoire : véritable PNG ou JPEG, **2 Mo**, **4 millions de pixels** et **3 000 000 caractères** maximum.

- Entrée : l’étudiant signe avant le verrouillage CROU. Une entrée déjà signée CROU renvoie `409`.
- Sortie : la signature CROU doit déjà être enregistrée, sinon `422`.
- Une seconde signature étudiante renvoie `409`. Après une interruption réseau, relire le détail avant de recommencer.
- Seuls les constats personnels sont accessibles ; un autre propriétaire renvoie `404`.

L’approbation ajoute uniquement la signature étudiant et son auteur. `signature_crou`, `pieces`, `pieces_json`, `type_etat`, `date_etat`, `observation_generale` et `nbre_cle_recus` sont interdits sur cette route. Les photos et constatations ne sont pas modifiées.

Les réponses incluent `can_edit`, `can_approve`, `approval_status`, `pdf_url` et `approval_url`. Utiliser `can_approve` pour afficher le bouton ; le serveur vérifie à nouveau les règles à l’envoi. Les statuts sont `pending_student`, `pending_crou` et `approved` lorsque les deux signatures existent. Une ancienne entrée signée CROU sans signature étudiant peut rester `pending_student` tout en étant verrouillée : suivre `can_approve`.

L’ancienne route `/etudiant/etat-des-lieux-sortie/{inspection}/approuver` reste disponible en POST/PATCH et refuse une entrée avec `404`.

## 2. API de contrat et approbation documentaire

| Méthode | Route | Fonction |
| --- | --- | --- |
| GET | `/contrats/actuel` | Contrat du logement confirmé actuel, HTML, empreinte et état d’approbation |
| GET | `/contrats/{contract}/pdf` | PDF d’une version personnelle enregistrée |
| POST ou PATCH | `/contrats/{contract}/approuver` | Approbation explicite de cette version |

`contract` est l’UUID `id` de la réponse, distinct des identifiants étudiant, assignation ou document institutionnel.

### Consulter puis approuver

```http
GET /api/V1/contrats/actuel
```

Exemple de réponse partielle :

```json
{
  "success": true,
  "data": {
    "id": "UUID_CONTRAT",
    "assignment_id": "IDENTIFIANT_ASSIGNATION",
    "student_name": "Nom du résident",
    "document_hash": "EMPREINTE_SHA256_RENVOYEE_PAR_LE_SERVEUR",
    "document_html": "<html><body>Contrat présenté</body></html>",
    "approved": false,
    "approved_at": null,
    "consent_text": "Je confirme avoir lu le contrat de logement présenté et en approuver les conditions.",
    "pdf_url": "https://votre-domaine/api/V1/contrats/UUID_CONTRAT/pdf",
    "approval_url": "https://votre-domaine/api/V1/contrats/UUID_CONTRAT/approuver"
  }
}
```

La consultation prépare ou actualise la version non approuvée. Sans assignation confirmée actuelle, ou si elle est terminée, elle renvoie `404`. Une version approuvée conserve son contenu et sa date.

Afficher `document_html` ou télécharger le PDF avec Bearer. Présenter le texte de consentement puis envoyer :

```http
POST /api/V1/contrats/UUID_CONTRAT/approuver
```

```json
{
  "document_hash": "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef",
  "consent": true
}
```

L’empreinte de cet exemple illustre le format. Utiliser celle du serveur : **64 caractères hexadécimaux minuscules**. Ne pas la recalculer depuis le PDF, dont les octets diffèrent du HTML.

`consent` est obligatoire et accepté : utiliser le booléen JSON `true`. Le contrat utilise un consentement explicite à une version ; une signature dessinée n’est pas requise.

Le serveur contrôle le propriétaire, l’assignation actuelle, son expiration, l’empreinte et le contenu actuel. En cas de version modifiée depuis la lecture : `422`, consulter à nouveau le contrat, afficher la nouvelle version et demander une nouvelle confirmation.

Succès : `200` avec `approved: true` et `approved_at`. Une répétition avec la même version déjà approuvée est **idempotente** : `200`, sans changer la date ni créer de nouvelle approbation. Le PDF approuvé inclut le nom, la date et le consentement enregistrés.

### Anciennes routes PDF de contrat

| Route conservée | Comportement actuel |
| --- | --- |
| `GET /contrat/{id?}` | PDF du contrat actuel du propriétaire Bearer ; `id`, s’il est fourni, désigne cet étudiant |
| `GET /campagne/contrat/{id}` | Même service PDF et même contrôle du propriétaire |

Ces routes exigent désormais Bearer et renvoient `404` pour un autre étudiant. Leurs noms internes sont distincts. Pour une nouvelle intégration, utiliser `/contrats/actuel` et les URL retournées. Le rendu historique de campagne n’est plus utilisé par cette route API.

## 3. Autres documents

`GET /mes-documents` inclut désormais les contrats **déjà enregistrés** du propriétaire, avec `type: contrat`, `id`, `title`, `date`, `url` (PDF), `approved`, `document_hash` et `approval_url`. Cette liste ne prépare pas automatiquement de contrat ; ouvrir `/contrats/actuel` pour démarrer son approbation. Un contrat historique reste téléchargeable par son propriétaire, mais ne peut pas être approuvé s’il ne correspond plus au logement actuel.

`GET /documents` et `GET /documents/{document}/pdf` servent les documents institutionnels publiés : règlement intérieur, guide du résident, procédures de sécurité et calendrier des visites. Leur validation/publication relève de l’administration. Aucune route d’approbation ou d’accusé de lecture étudiant de ces documents communs n’a été ajoutée. Les reçus, justificatifs et décomptes ne sont pas approuvés par ces routes de contrat ou d’état des lieux.

Pour ouvrir un PDF, télécharger les octets avec Bearer puis ouvrir le fichier local. Un navigateur externe ne transmet pas automatiquement le token.

## 4. Codes HTTP

| Code | Signification |
| --- | --- |
| 200 | Consultation, modification, PDF ou approbation réussie |
| 201 | Entrée créée |
| 401 | Token absent, invalide, expiré ou étudiant inactif |
| 404 | Ressource absente, autre propriétaire ou contrat actuel indisponible |
| 409 | Entrée existante, entrée verrouillée ou état des lieux déjà signé |
| 422 | Saisie invalide, image invalide, absence de chambre active, sortie sans signature CROU, consentement absent ou version incorrecte/ancienne |

Les erreurs de validation comportent les messages dans `errors`. Une erreur de photo peut utiliser la clé historique `pieces_json`, même avec un payload `pieces` : afficher ce message au niveau du constat.

## 5. Vérifications réalisées

- Contrat/API et service d’approbation : **12 tests, 48 assertions réussies**.
- Sélection ciblée des états des lieux et photos : **12 tests, 79 assertions réussies**.
- Contrôle complémentaire de la liste personnelle incluant les contrats : **1 test, 10 assertions réussies**.
- Contrôle des réponses d’affichage et de liste avec URL de photos de pièces et d’éléments : **1 test, 8 assertions réussies**, y compris après alignement sur le stockage configuré.

Certains tests ont été relancés : ces résultats ne représentent pas un total de tests distincts. La suite globale n’a pas été exécutée. Les routes ont été vérifiées dans le registre Laravel et les fichiers PHP modifiés ont été contrôlés.

Les éléments utilisent la migration `2026_10_09_000001_add_photo_to_edl_elements.php`. Les contrats utilisent la table existante `student_contract_approvals`, migration `2026_09_23_000002_create_student_contract_approvals.php`. Aucun nouveau mécanisme d’approbation des documents institutionnels n’a été créé.

Sources : `routes/api.php`, les contrôleurs API `InspectionController`, `ContractApprovalController`, `DocumentController`, les services `EdlPhotoService`, `StudentContractApprovalService` et `StudentContractService`, les modèles `EdlElement` et `EdlImage`.
