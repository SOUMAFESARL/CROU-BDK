# Deploiement SSH de main

`.github/workflows/deploy-ssh.yml` deploie les push sur `main` et les lancements
manuels depuis `main`. Le workflow FTP sur `dev` utilise un autre serveur et
l'environnement `development`. Le workflow SSH utilise `production` et le groupe
de concurrence `deploy-production` ; les deux serveurs peuvent etre deployes
independamment.

## Configuration GitHub

Dans Settings > Environments > production, configurer :

| Secret | Valeur |
| --- | --- |
| `SSH_HOST` | Hote SSH fourni par LWS |
| `SSH_USER` | `c2858133c` si le compte utilise est celui de cPanel |
| `SSH_PRIVATE_KEY` | Cle privee OpenSSH complete |
| `SSH_PASSPHRASE` | Passphrase de cette cle |
| `DEPLOY_PATH` | Chemin absolu du projet, par exemple `/home/c2858133c/public_html/crou` |

Conformement a la configuration demandee, aucun secret `SSH_PORT` ou
`SSH_KNOWN_HOSTS` n'est utilise. SSH utilise le port 22 et accepte automatiquement
la cle d'hote presentee a chaque execution, sans verification prealable de
l'identite du serveur. L'acces distant depuis GitHub Actions doit etre autorise
dans le pare-feu LWS ; les erreurs reseau observees precedemment doivent etre
resolues pour que ce workflow puisse fonctionner.

## Preparation du serveur

Le dossier doit etre un checkout Git propre de ce depot, dont le commit courant
est un ancetre du commit a deployer sur `origin/main`. Le script refuse les
changements locaux et les historiques divergents, sans les ecraser. Le serveur
doit pouvoir recuperer `origin/main` sans interaction ; pour un depot prive,
configurer une cle GitHub de lecture sur le serveur, distincte de la cle de
connexion de GitHub Actions au serveur.

Installer PHP 8.2 ou plus et ses extensions requises, Composer, Node.js 22.14 ou
plus, npm, Git, Bash et flock. Ils doivent etre accessibles dans la session SSH
non interactive. Le `.env`, `vendor/autoload.php`, `storage` et `bootstrap/cache`
doivent deja exister. Les deux derniers dossiers doivent etre accessibles en
ecriture au compte de deploiement et au serveur web. Configurer le domaine pour
servir uniquement `public/`. Conserver une `APP_KEY` stable et les identifiants
de production dans le `.env` du serveur.

Les secrets SSH de `production` doivent cibler le serveur de `main`, tandis que
les secrets FTP de `development` doivent cibler le serveur de `dev`.

## Migrations et erreurs

Le script active la maintenance, avance au commit du workflow sans reecrire
l'historique, installe les dependances, compile le frontend puis execute :

```bash
php artisan migrate --force --no-interaction
```

Il ne lance que les migrations en attente, sans importer de dump, executer de
seeders ou reinitialiser la base. Verifier le schema historique et la table
`migrations` avant le premier deploiement ; les migrations seules ne reconstruisent
pas toutes les tables historiques du projet. Faire une sauvegarde de la base avant
les migrations de production.

Apres succes, le script regenere les caches de configuration et de vues, demande
le redemarrage des workers existants et quitte la maintenance. Un gestionnaire de
processus doit relancer ces workers. Si OPcache ne verifie pas les dates, prevoir
le rechargement PHP-FPM avec l'hebergeur.

En cas d'erreur apres activation de la maintenance, le site reste en maintenance.
Inspecter les logs Actions et `php artisan migrate:status`, corriger le probleme,
puis relancer le workflow. Une migration MySQL peut etre partiellement appliquee :
aucun rollback automatique n'est fait. Ne lancer `php artisan up` manuellement
qu'apres avoir verifie l'etat de l'application et de la base.

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
