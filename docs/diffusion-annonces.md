# Diffusion des annonces

- Site web (code interne flux) : visible dans le fil étudiant selon son audience, avec un e-mail si une adresse existe.
- SMS : appel à EnvoiSMS($dest_phone, $message), avec copie par e-mail.
- E-mail : e-mail uniquement.
- Le canal mobile push est retiré du formulaire, du filtre et de la validation.

Un seul e-mail est préparé par étudiant et par annonce, même si plusieurs canaux sont cochés. L'adresse principale valide est utilisée, sinon la secondaire. Même principe pour le téléphone. Les numéros ivoiriens sont normalisés en 225 + 10 chiffres.

## Paramétrage nécessaire

Dans Paramétrage > Configuration > Notifications :
- activer les e-mails et renseigner le transport réel (SMTP, hôte, port, identifiants, expéditeur) ;
- activer les SMS, sélectionner « Passerelle EnvoiSMS (JSON / Bearer) », puis renseigner l'URL HTTPS, la clé API et l'expéditeur.

Le protocole SMS est celui du helper fourni : POST JSON contenant recipient, sender_id, type: plain et message, avec l'en-tête Authorization: Bearer.
Les autres noms de fournisseurs déjà présents ne constituent pas des adaptateurs à leurs API propriétaires. Utiliser une passerelle compatible avec ce contrat. Un fournisseur différent est refusé par ce helper, sans envoi.

Les e-mails utilisent le contenu textuel de l'annonce. Les pièces jointes et images intégrées ne sont pas jointes aux messages.

## Exécution

La migration 2026_09_20_000004_create_annonce_deliveries.php crée le journal et ajoute l'URL SMS au paramétrage.

La publication prépare les destinataires dans une transaction. Un premier lot de 30 messages est traité à la fin de la requête web. Le planificateur Laravel traite les annonces programmées et les lots restants chaque minute via annonces:diffuser.

Configurer le serveur pour exécuter php artisan schedule:run chaque minute (cron ou Planificateur de tâches Windows). La commande php artisan annonces:diffuser déclenche réellement les envois : ne pas l'exécuter comme simple vérification.

Les annonces déjà publiées au moment de la migration ne sont pas renvoyées. Les brouillons ne partent pas ; les annonces programmées partent à la date prévue. Les annonces annulées, supprimées ou expirées ne transmettent plus leurs messages en attente.

## Destinataires et suivi

« Tous les étudiants » concerne les étudiants non supprimés. Les audiences cité, bâtiment et chambre utilisent les affectations confirmées en cours (date de début atteinte, date de fin non dépassée) et les chambres non supprimées. L'audience spécifique utilise les étudiants sélectionnés.

Le titre, le texte et les coordonnées sont figés à la première publication. Modifier ou republier la même annonce ne recrée pas la campagne.

Le journal annonce_deliveries enregistre chaque couple étudiant/canal avec une contrainte unique. Les compteurs affichent les envois acceptés, en attente et en échec. « Accepté » désigne l'acceptation par le transport, pas une preuve de lecture ou de réception sur le téléphone.

Les erreurs sont enregistrées sans secrets fournisseurs. Aucun renvoi automatique d'un échec ou d'une transmission interrompue (sending) : vérifier d'abord le journal du fournisseur, car une réponse perdue peut correspondre à un message déjà accepté. Une reprise nécessite une intervention après cette vérification.

## Vérification

php vendor/bin/phpunit tests/Feature/AnnonceDiffusionTest.php

Les tests utilisent SQLite en mémoire, Mail::fake() et Http::fake() ; aucun message réel n'est envoyé.
