# Cycle du dossier d’hébergement

Le dossier porte l’état de référence dans `Demande.etat_dossier`. Les anciens statuts de l’étudiant et de la demande sont conservés pour les écrans existants, mais leur mise à jour passe par `DossierWorkflow`.

Parcours : Brouillon → Soumis → En étude → Éligible → Pré-attribué → Accepté par l’étudiant → Attribué définitif. Un dossier incomplet doit être complété et soumis à nouveau. Les refus, annulations, expirations et clôtures sont conservés dans l’historique.

- L’administration ouvre l’étude et contrôle l’éligibilité. Une dérogation approuvée reste liée au dossier et à l’évaluation vérifiée.
- L’allocation réserve une place et crée une proposition. Elle ne rend pas l’étudiant résident.
- L’étudiant accepte la proposition qui lui est présentée. Le service Logement valide ensuite définitivement, après un nouveau contrôle des critères et de la chambre.
- Un changement de proposition annule l’ancien accord et exige une nouvelle acceptation.
- Refus, dossier incomplet, annulation et réouverture nécessitent un motif.
- L’annulation d’une proposition libère la place. Un séjour confirmé passe par la procédure de sortie.
- Les admissions directes et imports suivent aussi ces étapes : les dossiers non éligibles restent en étude.
- L’expiration d’une campagne ne transforme pas les attributions définitives en refus.

## Web

Le panneau « Suivi du dossier » est présent dans le traitement administratif et dans les demandes de l’étudiant. La liste des allocations permet la validation définitive après l’accord étudiant.

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

Les états envoyables par l’étudiant sont `soumis`, `accepte`, `refuse`, `annule`, selon l’étape courante. Pour un refus, envoyer aussi `motif` ; `assignation_id` est obligatoire pour accepter ou refuser une proposition. Une ancienne proposition ne peut pas être acceptée après une réaffectation.

Réponses : 200 avec `data.etat_dossier`, 401 sans authentification valide, 403 pour un autre propriétaire ou un acteur non autorisé, 422 pour une transition interdite ou des éléments manquants. Une répétition de transition renvoie 422 et ne double pas l’historique.

À l’inscription, `save_as_draft: true` conserve un brouillon. L’inscription ne décide plus automatiquement de l’admission sur un pourcentage de score.

## Déploiement

Exécuter la migration `2026_09_23_000006_create_dossier_workflow.php` avant d’utiliser les nouvelles pages. Elle reprend les anciennes réservations en « Pré-attribué » et les séjours confirmés en « Attribué définitif », avec une entrée de reprise, sans inventer les validations historiques. Les anciennes demandes évaluées sans attribution reviennent en étude pour validation explicite.
