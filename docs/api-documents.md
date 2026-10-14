# API des documents — CROU de Bondoukou

Référence vérifiée dans `routes/api.php`, les deux contrôleurs DocumentController et le middleware AuthenticateStudentApi le 10 octobre 2026. Les chemins ci-dessous sont relatifs à **`/api/V1`** et sensibles à la casse du préfixe `V1`.

## Authentification des sept endpoints de documents

```http
Authorization: Bearer VOTRE_TOKEN
Accept: application/json
```

Le token vient de `POST /auth/authenticate`, dans `auth.token`. L'étudiant doit être actif et le token valide. Une session web seule ne remplace pas le token.

Toutes les opérations ci-dessous sont des **GET sans corps**. Aucun fichier ni champ `etudiant_id`, `resident_id` ou `campagne_id` n'est à envoyer. L'identité est déterminée par le token. Les identifiants personnels sont des chaînes ; utiliser ceux retournés par la liste, sans les convertir en nombres.

## Variables attendues, endpoint par endpoint

| Méthode et endpoint | Variables obligatoires | Variables facultatives | Réponse |
| --- | --- | --- | --- |
| `GET /documents` | Token dans l'en-tête | Query `per_page` : entier 1–100, défaut 15 ; `page` : page demandée, défaut 1 | JSON paginé des documents communs publiés |
| `GET /documents/{document}/pdf` | Path `document` : identifiant `id` d'un document de `/documents` | Aucune | PDF de sa version publiée |
| `GET /mes-documents` | Token dans l'en-tête | Aucune | JSON de tous les documents personnels disponibles |
| `GET /mes-documents/recu/{id}` | Path `id` : identifiant du **paiement** (`id_paiement`), ligne `type: recu` | Aucune | Reçu PDF téléchargeable |
| `GET /mes-documents/etat-des-lieux/{id}` | Path `id` : identifiant de l'**état des lieux** (`etat_des_lieux_id`), ligne `type: etat-des-lieux` | Aucune | État des lieux PDF affichable |
| `GET /mes-documents/justificatif/{id}` | Path `id` : identifiant du **document étudiant** (`id_document`), ligne `type: justificatif` | Aucune | Fichier local ou redirection vers le stockage configuré |
| `GET /mes-documents/decompte/{id}` | Path `id` : identifiant de la **demande de sortie** (`id_demande_sortie`), ligne `type: decompte` | Aucune | Décompte PDF téléchargeable |

Les cinq téléchargements exigent aussi le token. `page` est traité par le paginateur Laravel ; seul `per_page` dispose actuellement d'une validation explicite dans ce contrôleur. Envoyer un entier positif pour `page`.

### Liste des documents communs

```http
GET /api/V1/documents?per_page=15&page=1
Authorization: Bearer VOTRE_TOKEN
Accept: application/json
```

La liste ne contient que les documents au statut `published` dont la version publiée existe et dont `snapshot.draft` vaut `false`. Les valeurs sont issues de cette version, et non du brouillon de travail.

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [{
      "id": 12,
      "type": "reglement_interieur",
      "title": "Règlement intérieur",
      "reference": "RI-2026",
      "version_label": "1.0",
      "pdf_url": "https://votre-domaine/api/V1/documents/12/pdf"
    }],
    "per_page": 15,
    "total": 1
  }
}
```

Le paginateur contient aussi ses liens et autres métadonnées. Les types définis par le projet sont `reglement_interieur`, `guide_resident`, `procedures_securite` et `calendrier_visites`. Aucun filtre `type`, `search` ou `cite_id` n'est actuellement pris en charge par cette API.

### Liste des documents personnels

```http
GET /api/V1/mes-documents
Authorization: Bearer VOTRE_TOKEN
Accept: application/json
```

```json
{
  "success": true,
  "data": [{
    "id": "IDENTIFIANT_PAIEMENT",
    "type": "recu",
    "title": "NUMERO_PAIEMENT",
    "date": "2026-10-06T10:00:00.000000Z",
    "url": "https://votre-domaine/api/V1/mes-documents/recu/IDENTIFIANT_PAIEMENT"
  }]
}
```

`data` est un tableau, sans pagination, trié par date décroissante. `date` peut être `null`. Les justificatifs fournissent aussi `statut` (`en_verification`, `valide`, `rejete` ou `obsolete`).

- Reçus : paiements `valide` ou `rembourse` uniquement.
- États des lieux : constats enregistrés pour l'étudiant, entrée ou sortie.
- Justificatifs : documents du dossier de l'étudiant ; la liste ne filtre pas leur statut.
- Décomptes : demandes de sortie disposant d'un décompte. L'identifiant à utiliser est celui de la demande, pas celui du décompte.

Les contrats déjà enregistrés sont maintenant inclus avec `type: contrat`, `approved`, `document_hash` et `approval_url`. Consulter `/contrats/actuel` pour préparer ou consulter le contrat actuel. Les API de documents ne comportent pas de route POST/PUT/DELETE pour téléverser, modifier ou supprimer les justificatifs.

### Ouverture et erreurs

Utiliser `pdf_url` ou `url` tel que renvoyé, avec l'en-tête Bearer. Télécharger les octets puis ouvrir le fichier local : un navigateur externe n'ajoute pas automatiquement le token. Les réponses PDF sont des fichiers binaires, sans enveloppe JSON.

Un justificatif local PDF/JPEG/PNG/GIF/WebP s'ouvre directement. Les autres formats sont téléchargés. Un ancien fichier distant autorisé peut renvoyer `302` ; le client ne doit pas transmettre son token étudiant à un autre domaine en suivant la redirection.

| Code | Signification |
| --- | --- |
| `200` | Liste ou fichier disponible |
| `302` | Justificatif redirigé vers le stockage distant configuré |
| `401` | Token absent, invalide, expiré ou étudiant inactif |
| `404` | Identifiant inconnu, document d'un autre étudiant, fichier indisponible, décompte absent ou document commun non publié |
| `422` | `per_page` non valide sur la liste commune |

Les réponses de documents portent `Cache-Control: private, no-store`.

## Contrats et approbations : mise à jour du 10 octobre 2026

La référence détaillée des routes, des photos et des approbations est disponible dans [API des états des lieux et approbation des documents](api-etats-des-lieux-approbations.md).

| Méthode et endpoint | Variables attendues | Accès |
| --- | --- | --- |
| GET /contrats/actuel | Aucune | Bearer étudiant : contenu, empreinte, état et URL du contrat actuel |
| GET /contrats/{contract}/pdf | UUID du contrat personnel | Bearer et contrôle du propriétaire ; PDF de la version enregistrée |
| POST ou PATCH /contrats/{contract}/approuver | document_hash (64 caractères hexadécimaux minuscules), consent: true | Bearer ; propriétaire, version et assignation actuelle contrôlés |
| POST ou PATCH /etat-des-lieux/{inspection}/approuver | signature_etudiant PNG/JPEG en data URL | Bearer ; signature distincte d’entrée ou de sortie selon les règles de verrouillage |
| GET /contrat/{id?} | Identifiant étudiant facultatif | Bearer ; l’identifiant doit correspondre au propriétaire du token |
| GET /campagne/contrat/{id} | Identifiant étudiant | Bearer ; même service PDF et même contrôle du propriétaire |
| GET /parametrage/type-document | Aucune | Public ; référentiel des types de pièces justificatives |

Les anciennes routes de contrat sont désormais protégées et leurs noms internes distincts. Les contrats figurent dans la liste personnelle, sans préparation automatique. Les documents institutionnels restent consultables dans leur version publiée ; aucune approbation étudiante de ces documents communs n’est exposée.

Les constats renvoient les photos des éléments dans pieces[].elements[].photo_url et les photos générales dans pieces[].images[].url. Les URL utilisent le stockage public configuré. Le JSON ne contient pas les fichiers binaires.
