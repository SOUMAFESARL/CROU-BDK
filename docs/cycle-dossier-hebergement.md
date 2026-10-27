# Cycle du dossier d’hébergement

Le dossier porte l’état de référence dans `Demande.etat_dossier`. Les anciens statuts de l’étudiant et de la demande sont conservés pour les écrans existants, mais leur mise à jour passe par `DossierWorkflow`.

Parcours : Brouillon → Soumis → Éligible → Confirmé par l’étudiant → Attribué définitif. Il n’y a plus d’étape « En étude » : l’administration statue directement sur un dossier soumis (incomplet, éligible, refusé, annulé). L’état `en_etude` ne subsiste que sur les dossiers de l’ancien parcours, qui peuvent encore avancer (affichés au niveau « Soumis »). Un dossier incomplet doit être complété et soumis à nouveau. Les refus, annulations, expirations et clôtures sont conservés dans l’historique.

- L’administration contrôle l’éligibilité d’un dossier soumis. Seule l’absence de sanction disciplinaire bloquante conditionne le passage en `eligible` ; la validation des documents (« Documents validés », affichée « Non vérifié » tant que le seuil n’est pas atteint et « Rejeté » si une pièce est rejetée) et les autres critères de campagne (âge, moyenne, crédits, distance, boursier…) sont affichés à titre indicatif et ne bloquent pas la décision de l’admin. Une dérogation approuvée reste liée au dossier et à l’évaluation vérifiée.
- **Décision automatique à la soumission** (`App\Http\Services\DecisionAutomatiqueDossier`) : quand l’étudiant dépose son dossier (inscription web ou mobile, ou soumission d’un brouillon / dossier incomplet), le barème de scoring actif de la campagne (*Paramétrage › Scoring*) calcule le score. Score ≥ « Score minimum pour l’attribution automatique (/100) » → `eligible` (liste « En attente ») ; score inférieur → `refuse` (liste « Demandes rejetées ») avec un motif indiquant le score et le minimum. La transition est tracée par `systeme` (« Traitement automatique ») avec `details.decision_automatique = true`, le score et les sous-scores. Le dossier reste « Soumis » pour une décision manuelle si la campagne n’a pas de barème actif, si une sanction disciplinaire bloque l’éligibilité ou si la campagne n’est plus active. Un dossier saisi par un admin (admission directe) n’est pas concerné.
- **Score affiché sur la fiche du dossier** : la carte « Score d’éligibilité (barème) » (`CampaignScoringService::detail()`) détaille le score par volet (poids, situation de l’étudiant, points, apport) et le compare au minimum ; il est recalculé sur les données actuelles et rappelle le score retenu à la décision automatique s’il a changé depuis. Il ne faut pas le confondre avec la carte « Critères d’éligibilité » (nombre de critères de campagne conformes, ex. 7/10), purement indicative et sans effet sur la décision automatique.
- **Critères ajoutés au barème** (*Paramétrage › Scoring*, étape « Pondérations », bouton « Ajouter un critère ») : en plus des quatre volets fixes (bourse, résultats académiques, distance, PMR), l’admin peut ajouter jusqu’à 10 critères sans développement. Chacun s’appuie sur une donnée du dossier (`CampaignScoringService::SOURCES`) : âge (calculé à la date de la demande), moyenne, crédits validés, distance — notés par **tranches** (borne basse incluse, borne haute exclue, sans chevauchement) — ou genre, niveau d’étude actuel, filière, région, nationalité, situation de bourse, PMR confirmé — notés **par valeur** (valeurs issues des référentiels). Des points « Hors tranches » / « Autre valeur » s’appliquent quand rien ne correspond. Leur pondération entre dans le total de 100 %. Ils sont enregistrés dans `campaign_scorings.criteria` avec la version du barème, et figurent dans la carte « Score d’éligibilité » de la fiche du dossier. Comme pour les volets fixes, une donnée non renseignée (ou invalide) pour un volet ou un critère pondéré (ex. distance ou date de naissance absente) vaut **0 point** pour ce volet ; le score reste calculé et la fiche affiche « … non renseigné(e) → 0 point ».
- **Levée d’un refus automatique** : un dossier refusé par le barème peut passer directement de `refuse` à `eligible` (admin, motif par défaut « Refus automatique levé… »), en un clic depuis la fiche du dossier (bouton « Rendre éligible ») ou la liste « Demandes rejetées » (par dossier, ou en cochant les dossiers voulus — seuls les refus automatiques sont cochables, case d’en-tête pour toute la page — puis « Rendre éligible la sélection », route `admin.hebergement.campagnes_admission.lever_refus_automatiques`, permission `hebergement.admissions.validate`). Un refus prononcé par un agent n’est pas concerné : il passe par le réexamen (retour à « Soumis »).
- Éligible : l’étudiant reçoit ses accès (premier passage) et doit confirmer qu’il souhaite toujours être logé, depuis son espace web (tableau de bord ou « Mes demandes », boutons « Confirmer ma demande » / « Refuser », route `etudiants.validation-demande`). Seul l’étudiant peut confirmer (`eligible → confirme`) ; l’admin ne peut pas le faire à sa place. Un refus exige un motif et passe le dossier en `refuse`.
- Correspondance avec les listes admin (`etudiants.statut_inscription`, tenu par `DossierWorkflow`) : `eligible` → « En attente » (`en_attente`, `/admin/gestion-hebergement/campagnes_admissions/liste/en_attente`) ; refus → « Demandes rejetées » (`annuler`) ; `confirme` → « En vérification » (`en_verification`, `/admin/gestion-hebergement/residents_contrat/liste/en_verification`) ; `attribue_definitif` → résidents.
- Dans « En vérification », l’icône lit d’un dossier `confirme` ouvre le formulaire d’attribution de sa campagne (`admin.allocation.allocate-form`, étudiant présélectionné via `etudiant_id`, droit `hebergement.attributions.assign`). Seuls les dossiers `confirme` sont proposés à l’attribution (manuelle ou automatique).
- L’attribution d’un dossier confirmé est **définitive** : l’affectation est créée `confirmee` sans échéance, le dossier passe en `attribue_definitif` (critères et chambre recontrôlés) et l’étudiant devient résident, sans nouvelle acceptation. Elle ne s’annule ni ne se réaffecte plus depuis le suivi des attributions : une erreur se corrige par la procédure de sortie.
- Ancien parcours (dossiers déjà `pre_attribue` / `accepte`) : l’étudiant accepte ou refuse la proposition, puis le service Logement valide définitivement. Une réaffectation d’une telle proposition l’annule, ramène le dossier en `confirme` et attribue définitivement la nouvelle chambre.
- Réexamen : depuis « Éligible » ou « Confirmé », l’admin peut renvoyer le dossier en « Soumis » (action « Réexaminer »). La réouverture d’un refus ramène aussi en « Soumis » et exige un motif. Seul l’étudiant soumet un brouillon ou un dossier incomplet ; l’admin ne peut pas passer en « Soumis » depuis un autre état et l’étudiant ne peut pas resoumettre un dossier refusé. Le délai de dépôt de la campagne ne s’applique pas au réexamen.
- Les dérogations se demandent sur un dossier « Soumis ».
- Refus, dossier incomplet, annulation et réouverture nécessitent un motif.
- L’annulation d’une proposition libère la place. Un séjour confirmé passe par la procédure de sortie.
- Les admissions directes et imports suivent aussi ces étapes : les dossiers non éligibles restent « Soumis ».
- L’expiration d’une campagne ne transforme pas les attributions définitives en refus.

## Web

Le panneau « Suivi du dossier » est présent dans le traitement administratif et dans les demandes de l’étudiant. Tant que le dernier dossier est « Éligible » (confirmation attendue), l’espace étudiant se limite au tableau de bord : le menu n’affiche que « Tableau de bord » (`EtudiantMenu::confirmationAttendue`) et le middleware `ConfirmationDemandeEtudiant` (groupe `etudiants`) redirige les autres pages vers le tableau de bord ; restent accessibles la réponse (`etudiants.validation-demande`…), le profil et la déconnexion.

La « Progression du traitement » du tableau de bord suit l’état du dossier (`EtudiantDashboard::ETAPES_FRANCHIES`) dans l’ordre Soumission → Étude du dossier → Vérification → Attribution → Validation : un dossier éligible est à 40 % (étude terminée), la vérification n’est franchie qu’après la confirmation de l’étudiant. Le pourcentage de documents validés est un indicateur distinct.

La validation définitive depuis la liste des allocations ne concerne plus que les propositions de l’ancien parcours acceptées par l’étudiant.

## API étudiant

Authentification : `Authorization: Bearer <token étudiant>`.

- `GET /api/V1/dossiers/{id_demande}` : dossier appartenant à l’étudiant, proposition et historique.
- `POST /api/V1/dossiers/{id_demande}/transition` : changement d’état autorisé.

Exemple d’acceptation :
```json
{
  "etat": "accepte",
  "assignation_id": "identifiant-de-la-proposition-affichee"
}
```

Les états envoyables par l’étudiant sont `soumis`, `confirme` (dossier éligible), `accepte`, `refuse`, `annule`, selon l’étape courante. Pour un refus, envoyer aussi `motif` ; `assignation_id` est obligatoire pour accepter une proposition (ancien parcours) et pour la refuser. L’application mobile n’expose pas encore la confirmation : elle se fait depuis l’espace web. Une ancienne proposition ne peut pas être acceptée après une réaffectation.

Réponses : 200 avec `data.etat_dossier`, 401 sans authentification valide, 403 pour un autre propriétaire ou un acteur non autorisé, 422 pour une transition interdite ou des éléments manquants. Une répétition de transition renvoie 422 et ne double pas l’historique.

À l’inscription, `save_as_draft: true` conserve un brouillon. L’inscription ne décide plus automatiquement de l’admission sur un pourcentage de score.

Identifiants de l’espace étudiant : l’adresse e-mail saisie à l’étape « Identité » (`email1`) sert d’identifiant. Le mot de passe est généré et envoyé (`IdentifiantsEtudiant` → `EtudiantIdentifiantsNotification`, qui invite l’étudiant à confirmer ou refuser sa demande) **au premier passage du dossier en « Éligible »**, par `DossierWorkflow`, après validation de la transaction. Il n’est enregistré que si l’e-mail est parti, et un compte qui a déjà un mot de passe n’est jamais modifié (réexamen, réadmission). Exception : un dossier enregistré en brouillon reçoit ses identifiants dès l’inscription, l’étudiant devant se connecter pour le soumettre. L’écran de confirmation de `/register` annonce l’envoi à venir et ne propose ni redirection vers la connexion ni création de mot de passe.

Pièces jointes de l’inscription : chaque fichier est envoyé sous le slug du document de la campagne. `EtudiantRequest` valide les documents de la campagne (`file`, 2 Mo maximum, formats déclarés limités à PDF/JPG/PNG ; les pièces marquées obligatoires ne sont exigées que sur le formulaire web `/register`, pas sur l’API mobile). `DocumentEtudiantRepository::stockerPieces()` copie les fichiers dans `admin/assets/images/etudiant-document` (racine web, ou `STUDENT_DOCUMENTS_UPLOAD_ROOT`) **avant** la création du compte, en vérifiant le type réel du contenu ; une pièce refusée ou non copiée bloque la demande avec l’erreur sous le document, sans créer de compte. Une ligne `document_etudiant` est ensuite créée par fichier (taille et extension réelles) ; en cas d’échec ultérieur, les fichiers copiés sont supprimés.

## Déploiement

Exécuter la migration `2026_09_23_000006_create_dossier_workflow.php` avant d’utiliser les nouvelles pages. Elle reprend les anciennes réservations en « Pré-attribué » et les séjours confirmés en « Attribué définitif », avec une entrée de reprise, sans inventer les validations historiques. Les anciennes demandes évaluées sans attribution reviennent en étude pour validation explicite.
