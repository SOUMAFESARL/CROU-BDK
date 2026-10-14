# Signatures des responsables et documents de la résidence

La configuration est **commune à l’application**, conformément au choix retenu : une signature pour chacune des quatre fonctions, sans déclinaison par cité.

## Accès depuis le paramétrage

- **Signatures des responsables** : nom du titulaire, image PNG/JPEG, activation, remplacement ou retrait.
- **Documents de la résidence** : rédaction, signataires choisis, aperçu, export PDF et historique.

Les fonctions sont Sous-DAL, Chef de service logement, Directeur de cité et Concierge principal. Elles définissent les blocs de signature ; leur configuration ne crée ni ne modifie les rôles de connexion des utilisateurs.

## Correspondance proposée

| Document | Signataires proposés à la création |
| --- | --- |
| Règlement intérieur | Sous-DAL, Chef de service logement, Directeur de cité |
| Guide du résident | Chef de service logement, Directeur de cité |
| Procédures de sécurité | Directeur de cité, Concierge principal |
| Calendrier des visites ou interventions | Directeur de cité, Concierge principal |

Ces propositions sont modifiables dans chaque document selon le circuit interne retenu par l’établissement. Elles ne représentent pas une obligation légale déterminée par l’application.

## Configurer une signature

1. Saisir le nom et les prénoms du titulaire de la fonction.
2. Charger son image de signature : PNG ou JPEG, au plus 2 Mo et 2 000 × 1 000 pixels. Un PNG transparent convient à une signature détourée.
3. Cocher « Autoriser l’insertion dans les documents », puis enregistrer.
4. Pour remplacer l’image, charger un nouveau fichier. Sans fichier, l’image actuelle est conservée. Pour la retirer, cocher le retrait et désactiver la signature.

L’enregistrement passe par le helper commun `upload('private:legal-signatures', 'png', $request, 'signature')`. Le préfixe `private:` utilise le fichier Laravel validé et retourne un chemin relatif privé ; les appels historiques sans ce préfixe restent inchangés.

Les images sont stockées sur le disque privé Laravel, dans `storage/app/private/legal-signatures`. Leur consultation est protégée par la session administrateur et la permission de configuration des signatures.

## Rédiger un document

Choisir l’un des quatre types. Le formulaire propose une trame de rubriques à compléter ; il ne prétend pas rédiger les règles de l’établissement à sa place.

Informations configurables :
- titre, établissement, résidence ou périmètre ;
- référence, version, année universitaire, date d’application ;
- lieu et contact utile ;
- texte mis en forme dans Summernote, comme pour les annonces : titres, gras, soulignement, couleurs, listes, alignements, tableaux et liens ;
- fonctions dont la signature doit être insérée.

Le calendrier dispose aussi de lignes date, heure, visite/intervention, lieu et responsable. Les lignes sont classées par date et heure à l’enregistrement.

Les boutons au-dessus du texte insèrent des variables :
`{{etablissement}}`, `{{residence}}`, `{{annee_universitaire}}`, `{{date_application}}`, `{{lieu}}`, `{{contact}}`.
Elles sont remplacées par les informations du formulaire à l’aperçu et à l’export. La mise en forme autorisée est conservée dans les aperçus et PDF. Le serveur retire les scripts, événements, cadres, images et ressources distantes du contenu rédigé. Les signatures restent gérées séparément par le paramétrage. Les versions historiques en texte brut conservent leur rendu.

## Brouillon, validation et historique

L’enregistrement crée ou met à jour un **brouillon**. Son PDF porte une mention de brouillon et peut signaler des signatures manquantes.

Après vérification du contenu, « Valider la version enregistrée » conserve le texte, les informations, les noms et les images des signatures dans une nouvelle révision. La référence, la date d’application et au moins un signataire configuré et actif sont requis. Le calendrier doit avoir au moins une ligne. Les variables inconnues ou non renseignées empêchent la validation.

Une modification ultérieure remet le document en brouillon ; les anciennes révisions restent consultables dans l’historique. Un remplacement ou une désactivation de signature n’altère jamais une révision déjà validée. Les modifications concurrentes depuis plusieurs onglets sont détectées.

La validation enregistre le document dans l’application ; elle ne l’envoie ni aux étudiants ni à un service externe. Les PDF contiennent les images des signatures configurées, sans certificat de signature électronique.

## Permissions

Deux permissions distinctes sont disponibles dans la gestion des rôles :
- `parametrage.legal-documents.manage` : rédiger, consulter, valider et exporter les documents ;
- `parametrage.legal-documents.signatures` : modifier les titulaires et les images de signature.

L’administrateur possède ces permissions. Autoriser la rédaction ne donne pas automatiquement le droit de modifier les signatures. Un rédacteur autorisé peut sélectionner les signatures déjà configurées pour ses documents.

## Installation et exploitation

Migrations appliquées localement :
- `2026_09_20_000001_create_legal_documents_tables.php`
- `2026_09_20_000002_add_legal_documents_permissions.php`
- `2026_09_20_000003_add_legal_document_body_format.php` (distinction entre texte historique et contenu mis en forme)

Elles créent les tables `legal_signatories`, `legal_documents`, `legal_document_versions` et les permissions. Aucun document validé ni aucune signature de démonstration n’a été ajouté à la base réelle.

L’extension **PHP GD** est nécessaire aux signatures PNG dans Dompdf. Elle a été activée localement dans `C:\xampp\php\php.ini`. La configuration antérieure est sauvegardée dans `storage/app/private/php.ini.before-signatures-gd`. PHP en ligne de commande charge GD. Apache XAMPP doit être redémarré depuis le panneau XAMPP pour recharger cette configuration : il tourne en console et la commande de rechargement en service n’a pas abouti. La configuration Apache a passé sa vérification de syntaxe.

Sauvegarder la base de données et le stockage privé : les versions validées conservent leurs signatures dans leurs instantanés en base ; les configurations courantes utilisent le stockage privé.

## Vérifications

Résultat local : **14 tests réussis, 148 assertions**, avec génération réelle d’un PDF contenant une signature. Les trois tables et les deux permissions ont été vérifiées après migration.

Tests ciblés :
`C:\xampp\php\php.exe vendor\phpunit\phpunit\phpunit tests\Feature\LegalDocumentsTest.php --no-progress`

Ils vérifient les interfaces des quatre types, les droits d’accès, les fichiers privés, le remplacement/retrait d’une signature, les brouillons, l’historique immuable, la génération réelle d’un PDF avec signature, le contenu échappé, les variables, le calendrier, les conflits de modification et CSRF.
