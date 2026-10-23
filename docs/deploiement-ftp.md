# Deploiement FTP de dev

Procedure complete de mise en production (sauvegarde, envoi manuel sans git,
migrations, taches cron, retour arriere) : voir [`deploiement-production.md`](deploiement-production.md).

Le workflow `.github/workflows/deploy.yml` se lance sur les push vers `dev`
ou manuellement depuis cette branche. Il utilise l'environnement `development`.

Secrets GitHub requis : `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`,
`FTP_SERVER_DIR`. Le dossier distant doit etre dedie au projet et finir par `/`.
Il est relatif a la racine accessible au compte FTP, pas necessairement a `/home`.

Pour le serveur LWS actuel, utiliser `web53.lws-hosting.com` comme `FTP_SERVER` :
le certificat FTPS ne couvre pas `soumafe.com`. FTPS explicite sur le port 21
est utilise par defaut. La variable `FTP_PROTOCOL=ftp` permet de choisir FTP
simple si necessaire ; elle transmet les identifiants sans chiffrement.

Le workflow installe les dependances PHP et compile Vite avant le transfert.
Deux transferts FTPS sont lances simultanement : `vendor` avec quatre fichiers
en parallele, et l'application avec deux fichiers en parallele. Le workflow
attend les deux et echoue si l'un des transferts echoue.
Les CSS, scripts, polices et bibliotheques sous `public/files` sont inclus.
Le logo CROU et les images d'authentification sont ajoutes uniquement si absents.
Le `.env`, les fichiers utilisateurs, les caches et les fichiers distants seuls
ne sont jamais supprimes. Les dumps SQL sont exclus. Aucun seeder n'est execute.

Le domaine doit servir le dossier `public` du projet. Configurer le `.env`
directement sur le serveur ; utiliser `SESSION_DRIVER=file` tant que la table
historique `sessions` n'est pas compatible avec les identifiants Laravel.

FTP ne permet pas de lancer Artisan. Activer la maintenance avant transfert,
puis executer dans le terminal de l'hebergeur, depuis le projet :

```bash
php artisan config:clear
php artisan package:discover
php artisan migrate --force --no-interaction
php artisan config:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Le transfert met les fichiers a jour en place et n'est pas atomique. En cas
d'echec, conserver la maintenance et inspecter le journal avant de reprendre.
