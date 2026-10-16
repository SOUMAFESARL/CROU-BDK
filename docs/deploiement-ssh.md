# Deploiement SSH de main : premiere installation et mises a jour

Le deploiement configure `SESSION_DRIVER=file` dans le `.env` du serveur :
la table historique `sessions` utilise des identifiants numeriques incompatibles
avec Laravel et les UUID des utilisateurs. Les sessions sont conservees dans
`storage/framework/sessions`, qui doit rester accessible en ecriture et persistant.
Aucune modification du schema de la base n'est necessaire. Le `.env` reste prive.

Le workflow `deploy-ssh.yml` compile le frontend dans GitHub Actions, transfere
une archive par SSH sur le port 5022 et installe les dependances PHP sur le serveur.
Le serveur ne requiert ni npm, ni Node.js, ni checkout Git. Le workflow FTP de dev
reste independant, avec ses secrets dans `development`.

Si l'hebergement expose les fichiers sous `/public/build` plutot que `/build`,
Laravel detecte le manifeste sous la racine Web et prefixe les URL des assets
avec `/public`. Une configuration explicite `ASSET_URL` reste prioritaire.
Vite compile les imports et les polices avec des chemins relatifs pour supporter
ces deux dispositions. Le domaine peut aussi servir directement le dossier `public`.

## Secrets de production

Configurer dans GitHub > Settings > Environments > production :

| Secret | Valeur |
| --- | --- |
| SSH_HOST | node263-eu.n0c.com |
| SSH_USER | kkfvkcpk |
| SSH_PRIVATE_KEY | Cle privee crou complete |
| SSH_PASSPHRASE | Passphrase de cette cle |
| DEPLOY_PATH | /home/kkfvkcpk/crou-bdk |
| ENV_PRODUCTION | Contenu complet du .env de production pour la premiere installation |

`ENV_PRODUCTION` doit contenir une APP_KEY base64 stable, APP_ENV=production,
APP_DEBUG=false, APP_URL et les identifiants MySQL reels de ce nouveau serveur.
Ne pas copier aveuglement les valeurs locales. Creer la base et autoriser son
utilisateur dans le panneau de gestion. Les variables de messagerie et services
externes doivent aussi etre adaptees. Une fois le .env du serveur cree, il est
conserve : le secret ne l'ecrase pas aux deploiements suivants.

## Base vide : initialisation sans donnees historiques

Le workflow initialise automatiquement une base MySQL/MariaDB strictement vide
avec `database/schema/initial-mysql.json`, puis execute les migrations restantes.
Ce fichier ne contient que le schema et les lignes techniques de `migrations`.
Les comptes, etudiants, paiements, configurations et autres donnees historiques
ne sont pas importes. Aucun import phpMyAdmin du dump complet n'est necessaire.

Le schema conserve les tables historiques que les migrations ne decrivent pas.
Les tables Laravel manquantes de jetons et verrous de cache sont ajoutees. Le champ
legacy `departure_requests.decision` est laisse a la migration en attente qui
cree l'ensemble du workflow de sortie. Les migrations users/cache deja representees
par le schema sont enregistrees dans l'historique ; la migration jobs reste executee.

Si des tables existent deja, le schema initial n'est pas applique. Une initialisation
partiellement echouee doit etre inspectee avant toute reprise : MySQL ne garantit
pas de rollback des changements DDL. Aucun reset automatique n'est effectue.

Cette initialisation prepare la structure, pas les donnees de fonctionnement :
un compte administrateur, les roles/permissions et la configuration metier devront
etre initialises explicitement. Le seeder de demonstration n'est pas execute.

## Serveur et fonctionnement

PHP 8.2 ou plus, les extensions Composer requises, tar et flock doivent etre
disponibles en SSH non interactif. La commande composer est utilisee si disponible,
sinon `php ~/composer.phar`. `~/.local/bin` est ajoute au PATH.

Le script cree le dossier du projet et les dossiers Laravel persistants si besoin.
Il active la maintenance avant de remplacer les fichiers ; .env, storage et les
fichiers utilisateurs sont preserves. Les images statiques sous
public/admin/assets/images sont ajoutees seulement si absentes, pour ne pas ecraser
les images existantes. Les fichiers devenus obsoletes ne sont pas supprimes par
ce transfert en place. Les dumps SQL sont exclus de l'archive.

Apres Composer, le script decouvre les packages, applique les migrations, regenere
les caches et quitte la maintenance. Une erreur laisse le site en maintenance :
corriger puis relancer, ne pas executer `php artisan up` avant verification.
Les workers doivent etre supervises pour redemarrer apres queue:restart.
Configurer le domaine pour servir `/home/kkfvkcpk/crou-bdk/public` et permettre au
serveur web d'ecrire dans storage et bootstrap/cache. Si OPcache ne verifie pas les
dates, prevoir un rechargement PHP aupres de l'hebergeur.

La cle d'hote SSH est acceptee automatiquement a chaque execution comme demande ;
aucun secret SSH_KNOWN_HOSTS ou SSH_PORT n'est requis. L'identite du serveur n'est
pas verifiee a l'avance. Sauvegarder la base avant les mises a jour de schema.

## Page blanche : fichiers Vite en erreur 404

Si `/` repond en HTTP 200 mais `/build/assets/...` en 404, alors que
`/public/build/assets/...` repond en 200, le domaine pointe vers la racine du
projet au lieu de `public/`. Dans cPanel, definir la racine du domaine sur
`DEPLOY_PATH/public` (le chemin reel du projet suivi de `/public`).

Si cette configuration ne peut pas etre modifiee, le `.htaccess` a la racine
achemine les requetes vers `public/`, y compris les fichiers JS et CSS. Deployer
ce fichier avec le reste du projet. Ne pas copier les fichiers compiles a la
racine et ne pas ajouter `/public` a `APP_URL`.

Apres correction, verifier que les URL JS et CSS presentes dans le HTML de `/`
repondent en HTTP 200 avec les types `application/javascript` et `text/css`,
puis recharger la page avec Ctrl+F5.
