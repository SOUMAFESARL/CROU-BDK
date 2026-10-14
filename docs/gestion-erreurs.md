# Messages d’erreur pour les utilisateurs

La gestion commune est définie dans `App\Support\UserFacingErrors`, enregistrée dans `bootstrap/app.php` et appliquée aux requêtes par `HandleUserErrors`.

Les exceptions inattendues restent dans les journaux Laravel. Les utilisateurs reçoivent un message simple, même si `APP_DEBUG=true`. Les API renvoient leur code HTTP d’échec et `{success: false, message: ...}`. Les erreurs de validation ajoutent `errors`, avec les champs à corriger. Les réponses JSON 5xx retournées manuellement sont aussi nettoyées.

Sur les pages, les erreurs générales affichent une page indépendante du modèle administrateur et de la base de données, avec un bouton de retour. Les validations conservent la redirection vers le formulaire, les erreurs de champs et les anciennes valeurs ; les mots de passe sont exclus par Laravel. Les erreurs de connexion conservent le circuit d’authentification.

Le middleware possède un `try-catch` couvrant toutes les requêtes ; le rendu de la page d’erreur possède aussi un secours si le modèle ne peut pas être rendu. Il n’est pas nécessaire d’entourer chaque simple lecture d’un catch qui masquerait les pannes.

Dans un catch applicatif, utiliser `UserFacingErrors::caught($exception)` pour journaliser le détail et obtenir un texte destiné au public. Pour les transactions, laisser l’exception sortir de la transaction avant de la convertir en réponse afin que Laravel annule les écritures. Ne pas transformer une panne en réponse de succès ou en liste vide.

Les erreurs courantes de base sont expliquées sans nom de table ni requête : doublon, élément encore utilisé ou sélection devenue indisponible. Les règles de validation métier conservent leurs messages de champ. Les réponses HTTP préservent leurs codes et leurs en-têtes tels que `Retry-After`.

Les requêtes utilisent le français par défaut, avec les traductions de validation dans `lang/fr/validation.php`. `USER_ERROR_LOCALE` permet d’ajuster cette langue. La traduction couvre les règles courantes ; les règles personnalisées doivent fournir leur propre message compréhensible.

Cette gestion couvre les erreurs traitées par Laravel. Une panne du serveur web, un arrêt de PHP avant le démarrage de Laravel ou une interruption de connexion côté navigateur doit être traitée au niveau correspondant.

Les pages HTML reçoivent aussi le script `public/js/user-errors.js`. Une erreur JavaScript non traitée ou une promesse rejetée affiche un message simple, sans détails techniques et sans renouveler automatiquement une opération. Les annulations volontaires (`AbortError`) ne déclenchent pas d’alerte. Les réponses JSON et fichiers ne reçoivent pas ce script.
