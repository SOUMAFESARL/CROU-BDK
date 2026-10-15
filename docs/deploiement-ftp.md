# Deploiement FTP vers LWS / cPanel

Le workflow `.github/workflows/deploy.yml` deploie les push sur `dev` et peut etre
lance manuellement sur cette branche. GitHub Actions installe les dependances PHP
de production, compile le frontend puis transfere le code, `vendor` et
`public/build`. Aucun acces SSH ni Node.js sur le serveur n'est necessaire.

## Secrets GitHub

Le serveur de `dev` est distinct de celui de `main`. Dans Settings > Environments >
development, ajouter les secrets FTP du serveur de developpement :

| Secret | Valeur |
| --- | --- |
| `FTP_SERVER` | Hote FTP fourni par LWS, sans `ftp://` |
| `FTP_USERNAME` | Utilisateur FTP autorise a acceder au dossier du projet |
| `FTP_PASSWORD` | Mot de passe du compte FTP |
| `FTP_SERVER_DIR` | Chemin FTP du projet, termine par `/` |

Pour un compte FTP dont la racine est `/home/c2858133c`, le chemin peut etre
`/public_html/crou/`. Si le compte est deja limite au projet, utiliser un sous-dossier
dedie ou adapter la validation du chemin avec soin. Verifier ce chemin dans un
client FTP : il n'est pas necessairement le chemin absolu du terminal cPanel.
Le workflow refuse `/` et `./` pour eviter un transfert accidentel a la racine.

Le port utilise est `21`. Le protocole par defaut est FTPS explicite, qui chiffre
le transfert sur ce port. Si LWS ne propose que FTP simple, definir la variable
GitHub d'environnement `FTP_PROTOCOL` a `ftp` ; les identifiants et les fichiers
seront alors transmis sans chiffrement. Ne pas desactiver la verification TLS.
Les anciens secrets `SSH_*` et `DEPLOY_PATH` ne sont plus utilises.

Le transfert utilise `lftp` avec TLS 1.2, verification des certificats,
mode passif, deux transferts paralleles et jusqu'a cinq tentatives de reconnexion.
Les transferts interrompus peuvent etre repris selon les tailles et dates distantes ;
certains fichiers peuvent etre retransmis. Aucun fichier distant n'est supprime.
Le fichier d'etat de l'ancienne action n'est plus utilise. Le correctif doit etre
confirme par un transfert reel ; si TLS echoue encore, consulter les logs FTP LWS.

## Preparer le serveur

PHP 8.2 ou plus et les extensions requises doivent etre disponibles pour le site
et le terminal cPanel. Le workflow construit les dependances pour PHP 8.2.
Configurer le domaine pour servir `public_html/crou/public`.
Conserver le `.env` de production avec une `APP_KEY` stable, les bons identifiants
MySQL, `APP_ENV=production` et `APP_DEBUG=false`.

Ce transfert cible une installation existante : le `.env`, `storage`,
`bootstrap/cache`, `public/storage`, `public/files` et les images de
`public/admin/assets/images` ne sont ni transferes ni supprimes par l'action.
Ces images incluent des fichiers utilisateurs ; les nouvelles images statiques
dans ce dossier doivent etre transferees separement. Les dumps SQL, la base SQLite,
`.git`, les tests et les fichiers de developpement ne font pas partie du paquet.
Pour une premiere installation, creer les dossiers persistants et installer les
images necessaires, configurer leurs droits et executer `php artisan storage:link`.
Retirer les eventuels anciens dumps SQL accessibles dans le dossier Web.

## Maintenance et migrations dans le terminal cPanel

FTP met a jour les fichiers progressivement : ce deploiement n'est pas atomique.
Avant de pousser sur `dev` ou de lancer le workflow, effectuer une sauvegarde de la
base, ouvrir le terminal web cPanel et executer :

```bash
cd /home/c2858133c/public_html/crou
php artisan down --retry=60
```

Attendre le succes du transfert GitHub Actions. Puis, dans le meme dossier :

```bash
php artisan config:clear
php artisan package:discover
php artisan migrate --force --no-interaction
php artisan config:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Executer ces commandes une par une et s'arreter en cas d'erreur. Ne remettre le site
en ligne qu'apres correction. Si le transfert echoue, laisser la maintenance active,
corriger le probleme et relancer le workflow avant de lancer les migrations.
Les workers existants doivent etre geres par un gestionnaire de processus pour
redemarrer apres `queue:restart`. Pour OPcache sans verification des dates, demander
le rechargement PHP au gestionnaire de l'hebergement.

FTP ne permet pas d'executer les migrations : une execution verte du workflow
confirme le transfert, pas la mise a jour de la base. Aucune tache cron ou route
HTTP d'administration n'est installee. Le schema historique du projet repose aussi
sur un dump SQL : verifier la coherence de la base et de la table `migrations`
avant le premier deploiement. Aucun import, seeder, reset ou rollback automatique.

Reference : [manuel lftp](https://lftp.yar.ru/lftp-man.html).
