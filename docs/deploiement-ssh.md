# Deploiement GitHub Actions par SSH

Le workflow `.github/workflows/deploy.yml` deploie chaque push sur `dev`.
Il peut aussi etre lance dans Actions > Deploy via SSH > Run workflow en
selectionnant `dev` (le lancement manuel exige que le workflow soit aussi
present sur la branche par defaut du depot).

Il deploie le commit du workflow, installe les dependances PHP de production,
compile le frontend et execute `php artisan migrate --force --no-interaction`.
Seules les migrations encore absentes de la table `migrations` sont executees.
Le `.env` et les fichiers persistants restent sur le serveur.

## Preparer le serveur Linux

- Installer Git, Bash, `flock`, PHP 8.2 ou plus avec les extensions requises,
  Composer 2, Node.js 22.14 ou plus et npm.
- Le compte SSH doit pouvoir ecrire dans le projet, `storage` et `bootstrap/cache`.
  Le serveur web doit aussi pouvoir ecrire dans ces deux derniers dossiers.
- Utiliser un checkout Git propre de ce depot, avec `origin` configure pour lire
  le depot sans interaction. Pour un depot prive, configurer une cle de deploiement
  GitHub en lecture seule sur le serveur. Cette cle est distincte de celle utilisee
  par GitHub Actions pour se connecter au serveur.
- Configurer le serveur web avec la racine du site dans `public/`.
- Creer le `.env` de production sur le serveur : `APP_ENV=production`,
  `APP_DEBUG=false`, `APP_URL`, une `APP_KEY` stable et la connexion MySQL correcte.
- Effectuer une premiere installation Composer sur le serveur :
  `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader`.
- Configurer un gestionnaire de processus (par exemple Supervisor) si des workers
  de queue sont utilises : `queue:restart` leur demande de redemarrer, sans les lancer.
  Si OPcache ne verifie pas les dates des fichiers, prevoir le rechargement de PHP-FPM
  avec l'administrateur du serveur apres deploiement.

## Configurer GitHub

Dans Settings > Environments, creer `production`. Dans cet environnement, ajouter
les secrets suivants (ou utiliser les secrets Actions du depot) :

| Secret | Valeur |
| --- | --- |
| `SSH_HOST` | Nom DNS ou adresse IPv4 du serveur |
| `SSH_PORT` | Port SSH, optionnel, `22` par defaut |
| `SSH_USER` | Utilisateur SSH de deploiement |
| `SSH_PRIVATE_KEY` | Cle privee SSH dediee, complete, protegee par une passphrase |
| `SSH_PASSPHRASE` | Passphrase de la cle privee SSH |
| `DEPLOY_PATH` | Chemin absolu du checkout, par exemple `/var/www/crou-bdk` |

### Hebergement LWS / cPanel : `public_html/crou`

Le dossier indique pour ce projet est `public_html/crou`. Le gestionnaire de
fichiers cPanel peut afficher `/public_html/crou` relativement au compte.
Verifier le chemin reel dans une session SSH avec `cd ~/public_html/crou && pwd`,
puis copier le resultat dans `DEPLOY_PATH` (souvent
`/home/<compte>/public_html/crou`). Ne pas utiliser l'URL du gestionnaire cPanel
comme hote SSH : `SSH_HOST` et `SSH_PORT` doivent venir des parametres SSH LWS.

Verifier que `php`, `composer`, `npm` et `flock` sont disponibles dans une session
SSH non interactive. La version PHP du terminal peut differer de celle configuree
pour le site dans cPanel. Configurer le PATH du compte de deploiement si necessaire.
Si l'offre ne propose pas Node.js ou SSH, ce workflow doit etre adapte avant usage.
Configurer la racine du domaine/sous-domaine vers `public_html/crou/public` ; les
fichiers `.env`, `.git` et le dump SQL ne doivent pas etre accessibles sur le Web.

Ajouter la cle publique correspondante dans `~/.ssh/authorized_keys` du compte
de deploiement. Ne jamais enregistrer la cle privee dans le depot.
Le secret `SSH_KNOWN_HOSTS` n'est pas requis. Avec `StrictHostKeyChecking=accept-new`,
SSH accepte automatiquement la cle d'hote lors de la premiere connexion et
l'enregistre dans un fichier temporaire. Ce fichier est supprime en fin d'etape :
chaque execution GitHub Actions accepte donc de nouveau la cle presentee, sans
verification prealable de l'identite du serveur.
La cle est chargee dans un agent SSH temporaire avec `SSH_ASKPASS` pour permettre
le deploiement sans saisie interactive. La passphrase reste dans les secrets GitHub ;
elle n'est pas inscrite dans le script temporaire. L'agent est arrete en fin d'etape.

## Base existante et migrations

Ce projet contient un schema historique dans un dump SQL : les migrations seules
ne reconstruisent pas necessairement toutes les tables metier. Pour la premiere
installation, preparer la base et verifier sa table `migrations` avant d'activer
le workflow. Une base importee contenant deja des tables, mais sans historique
de migrations coherent, peut provoquer des erreurs « table already exists ».
Ne pas marquer arbitrairement toutes les migrations comme executees.

Faire une sauvegarde de la base avant le premier deploiement et avant toute
migration qui supprime ou transforme des donnees. Le workflow n'importe pas
automatiquement le dump SQL et n'execute ni `migrate:fresh` ni les seeders.

## Echec et reprise

Les deploiements sont serialises et un verrou sur le serveur empeche leur execution
simultanee. Les changements locaux du checkout et les historiques Git divergents
bloquent le deploiement sans etre ecrases.

Le site passe en maintenance pendant la mise a jour, y compris la compilation.
Si une etape echoue ensuite, il reste en maintenance pour eviter d'exposer une
application partiellement mise a jour. Examiner le journal Actions, corriger
l'erreur, puis relancer le workflow sur `dev`. Apres verification, `php artisan up`
permet aussi de rouvrir manuellement le site depuis le dossier du projet.

Une migration MySQL peut etre partiellement appliquee avant un echec. Verifier
`php artisan migrate:status` et le schema avant de relancer. Aucun retour arriere
automatique du code ou de la base n'est effectue.

References : [deploiements GitHub Actions](https://docs.github.com/en/actions/how-tos/deploy/configure-and-manage-deployments/control-deployments),
[commandes de deploiement Laravel](https://laravel.com/cloud/deploy-a-laravel-app).
