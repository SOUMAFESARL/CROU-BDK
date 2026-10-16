# Encaissement étudiant — configuration et exploitation

Le parcours est raccordé pour **Wave Checkout, MTN Collection Côte d’Ivoire et CinetPay Checkout v2** : création de transaction, page de paiement, notification, vérification serveur et comptabilisation du loyer.

## Parcours

Depuis l’espace étudiant, ouvrir **Paiements** (`/etudiant/dashboard?section=paiements`, page React `Etudiant/MesPaiements`) puis **Payer mon loyer** : assistant en 3 étapes (moyen de paiement, montant et numéro, vérification) qui appelle `POST /etudiant/paiements-en-ligne` en JSON. La page autonome `/etudiant/paiements-en-ligne` reste disponible pour le suivi. Seuls les réseaux actifs, raccordés et munis des identifiants requis sont proposés. La chambre occupée et son plus ancien loyer restant à régler sont présentés automatiquement, sans liste de loyers à choisir ; le montant proposé est le solde restant, que l’étudiant peut réduire (paiement partiel) sans jamais le dépasser. Le montant vient du solde facturé (paiements déjà enregistrés et pénalités compris) ; les montants et identifiants étudiant envoyés par le navigateur ne sont jamais utilisés. Au lancement, le serveur recontrôle l’affectation confirmée en cours, la chambre et le propriétaire du loyer. Un loyer d’un autre étudiant, d’une autre chambre ou d’une ancienne affectation est refusé. Sans affectation active ou sans loyer établi, aucun encaissement n’est lancé.

Wave et CinetPay ouvrent leur page de paiement. MTN demande une validation sur le téléphone. La page de suivi vérifie périodiquement l’état et permet une vérification manuelle. Un retour navigateur ou une notification ne constitue jamais une preuve de règlement : l’application interroge l’API authentifiée.

Une confirmation avec référence, montant et devise XOF concordants crée atomiquement un paiement validé, sa répartition et met à jour le solde du loyer concerné. Les doubles clics et confirmations répétées ne créent pas un second règlement. Les frais opérateur ne diminuent pas le montant brut crédité au loyer. Un éventuel trop-perçu lié à un autre règlement intervenu entre-temps reste visible dans le solde négatif.

## Mise en service

1. Appliquer les migrations de paiement `2026_09_19_000001` à `000004` dans l’ordre, si elles ne sont pas déjà appliquées. La dernière ajoute `online_payments` et le secret des notifications Wave.
2. Configurer `APP_URL` avec l’adresse publique **HTTPS** réelle de l’application. Les URL de retour/notification sont construites à partir de cette configuration. Mettre à jour le cache de configuration si utilisé.
3. Dans **Paramétrage → API de paiement**, bouton **Configurer** du réseau : assistant en 3 étapes (Connexion : environnement MTN et lien API, avec l’adresse attendue proposée ; Identifiants : clés du contrat marchand, laisser vide pour conserver une clé enregistrée ; Activation : activer le réseau et relire). Les valeurs des clés ne sont jamais renvoyées au navigateur : la page indique seulement si chaque clé est enregistrée.
4. Configurer les notifications chez les opérateurs et l’ordonnanceur ci-dessous.
5. Effectuer une recette avec les comptes marchands, vérifier réception des notifications et rapprochement du règlement dans l’application. Les tests automatisés utilisent des réponses simulées et ne prouvent pas l’activation d’un contrat marchand réel.

### Wave

Lien : `https://api.wave.com`. Clé API Wave Business autorisée pour Checkout. Renseigner le secret de signature des requêtes uniquement si cette option est activée sur la clé.

Enregistrer un webhook pour `checkout.session.completed`, avec **signature HMAC**, à l’URL :
`{APP_URL}/api/payments/notifications/wave`.

Renseigner son secret de signature des notifications dans le paramétrage (distinct de la clé API et du secret des requêtes). Il est obligatoire pour proposer Wave aux étudiants. Les signatures sont vérifiées sur le corps brut avec contrôle de fraîcheur, puis l’état est relu par l’API.

### MTN

Choisir **Côte d’Ivoire (XOF)**, lien `https://proxy.momoapi.mtn.com`, API user, API key et clé de souscription **Collection** de production. Le domaine de notification doit correspondre à celui autorisé dans le contrat MTN. La requête fournit automatiquement son URL `X-Callback-Url`.

Le numéro étudiant est saisi sous la forme 225 suivi des 10 chiffres. Une réponse 202 laisse le paiement en attente ; seul un état SUCCESSFUL vérifié avec le bon montant/référence/devise permet la comptabilisation.

Le sandbox MTN en EUR reste utilisable par les tests du client mais ne peut régler un loyer réel en XOF.

### CinetPay

Le connecteur utilise **Checkout v2 historique** : `https://api-checkout.cinetpay.com/v2/payment`, clé API et Site ID dans le champ marchand. L’URL de notification propre à la transaction est transmise automatiquement. Les notifications déclenchent la vérification serveur `/check`. Les montants doivent être multiples de 5 FCFA.

Le nouveau SDK Seamless utilisant `api_key + api_password` n’est pas interchangeable avec ce connecteur. Vérifier que le compte marchand donne bien accès à Checkout v2 avant activation.

### Moov, Orange, TrésorPay

Les paramètres sont conservables mais ces réseaux ne sont pas proposés pour initier un paiement. Le protocole, les identifiants et les notifications du contrat Côte d’Ivoire restent à confirmer. Le SDK communautaire Moov fourni vise un service SOAP au Bénin et ne suffit pas à établir une intégration CI.

## Reprise automatique

Exécuter `php artisan schedule:run` **chaque minute**, depuis le dossier du projet, avec cron ou le Planificateur de tâches Windows. Le planning appelle `payments:reconcile` sans chevauchement. Exemple de programme Windows : `C:\xampp\php\php.exe`, arguments `artisan schedule:run`, dossier initial `C:\xampp\htdocs\croudedaloa`.

Pour une reprise ponctuelle : `php artisan payments:reconcile --limit=50`.

Les notifications enregistrent d’abord la demande de vérification en base et déclenchent le traitement après réponse HTTP, sans worker obligatoire. Le planning reprend les interruptions et les notifications manquantes. Garder l’horloge serveur synchronisée pour Wave.

Une réponse de création incertaine conserve la transaction en attente, sans renvoyer une demande susceptible de doubler l’encaissement. Wave peut retrouver sa session par référence ; MTN et CinetPay sont interrogés par leur référence persistée. Une transaction introuvable durablement exige un rapprochement opérateur avant de décider d’une nouvelle tentative ; elle n’est pas automatiquement déclarée impayée.

## Administration et comptabilité

Le bas de la page **API de paiement** affiche les transactions (10 par page), références, loyers, étudiants, états et codes de diagnostic, avec quatre indicateurs qui servent de filtres (`?statut=confirmes|en_attente|a_verifier|echoues`). Les transactions en revue (`payment_mismatch`, `loyer_unavailable`, `amount_out_of_range`) nécessitent un rapprochement par le service financier ; elles ne sont pas validées automatiquement.

La désactivation empêche les nouvelles demandes mais permet de terminer les transactions déjà engagées. Chaque transaction conserve ses identifiants chiffrés pour continuer la vérification même après modification du paramétrage. Conserver l’APP_KEY Laravel et les anciennes clés opérateur valides jusqu’au rapprochement des transactions en cours. Ne jamais journaliser les identifiants ni les URL de notification individuelles contenant un jeton.

Les écritures alimentent les tables existantes `paiements`, `paiement_repartitions` et `loyers`. MTN utilise la méthode existante `mtn_money` ; Wave et CinetPay utilisent `autre`, avec le réseau précisé dans les notes et dans le suivi en ligne. Aucun remboursement ou traitement des litiges opérateur n’est automatisé.

## Vérifications

Commande : `C:\xampp\php\php.exe vendor\phpunit\phpunit\phpunit --filter Payment tests\Feature --no-progress`.

Résultat local : **36 tests réussis, 203 assertions**. Migration 000004 appliquée ; vues Blade compilées et tables comptables vérifiées en InnoDB.

Les tests utilisent SQLite en mémoire et HTTP simulé : aucune somme n’est débitée. Ils couvrent les clients, le paramétrage, le montant serveur, la propriété du loyer, les réseaux interdits, les notifications, les réponses incertaines, les écarts de montant/devise et la comptabilisation unique.

Références : [Wave Checkout](https://docs.wave.com/checkout), [Wave notifications](https://docs.wave.com/webhook), [MTN MoMo](https://momodeveloper.mtn.com/api-documentation/use-cases), [CinetPay Checkout v2](https://docs.cinetpay.com/api/1.0-fr/checkout/initialisation), [SDK Seamless](https://github.com/cinetpay/cinetpay-seamless).
