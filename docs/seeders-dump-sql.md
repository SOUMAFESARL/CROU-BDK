# Seeders du dump SQL

Source : `kkfvkcpk_croudedaloa_db (2).sql`.

Les 84 seeders de `database/seeders/SqlDump` correspondent aux 84 tables du
dump. Ils conservent exactement les 59 instructions INSERT : identifiants,
dates, textes et mots de passe deja haches. Une table sans INSERT produit un
seeder sans donnees. Les CREATE TABLE et ALTER TABLE ne sont jamais executes.

Ces seeders sont explicites : `DatabaseSeeder` et les workflows de deploiement
ne les appellent pas. Ils ne dependent pas de la presence du fichier SQL sur le
serveur, les instructions etant incluses dans les classes PHP generees.

## Utilisation

Preparer une base MySQL/MariaDB avec le schema compatible du dump avant de lancer :

```bash
php artisan db:seed --class=SqlDumpSeeder
```

Pour une seule table, par exemple `roles` :

```bash
php artisan db:seed --class='Database\Seeders\SqlDump\RolesSeeder'
```

Une table deja remplie est ignoree integralement : aucune ligne n'est effacee,
mise a jour ou fusionnee. Les tables vides recoivent les donnees historiques,
y compris les comptes, configurations, journaux et donnees metier du dump.
Pour une restauration complete, utiliser une base isolee avec toutes les tables
vides : une base deja peuplee peut contenir des references incompatibles.

Les controles de cles et le mode SQL sont retablis apres chaque table, meme en
cas d'erreur. Certaines tables historiques utilisent MyISAM : une erreur peut
laisser un import partiel, sans rollback transactionnel. Une relance ignorera
alors les tables devenues non vides ; inspecter leur contenu avant de reprendre.

Ne pas publier ces seeders comme donnees de demonstration : ils contiennent les
donnees reelles du dump. Leur creation ne les execute pas.

## Regeneration

```bash
python scripts/generate-dump-seeders.py "kkfvkcpk_croudedaloa_db (2).sql"
```

Le generateur analyse les INSERT en respectant les textes multilignes, les
points-virgules et les apostrophes echappees. Il ne se connecte pas a la base.
