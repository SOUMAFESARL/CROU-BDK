# Reporting de l’hébergement

Accès administrateur : **Hébergement > Reporting**, ou **Statistiques** sous chaque sous-module du menu latéral.

- Synthèse : `/admin/gestion-hebergement/reporting`.
- Module : `/admin/gestion-hebergement/reporting/{module}`.
- Filtres GET : `periode` (`week`, `month`, `quarter`, `year`, `custom`), `cite` (identifiant facultatif), `debut` et `fin` au format `YYYY-MM-DD` pour une période personnalisée.
- Le changement de module conserve les filtres. Le bouton **Imprimer / PDF** utilise l’impression du navigateur (les valeurs exactes sont imprimées sous chaque graphique).
- Page Inertia + React : `resources/js/pages/Admin/Hebergement/Reporting/Index.tsx`, graphiques dans `resources/js/components/reporting/report-charts.tsx` (shadcn/Recharts). Chaque tableau du service porte un `kind` qui choisit le graphique : `monthly` → aire (≥ 3 mois) ou barres, `distribution` → anneau (≤ 6 parts) ou barres horizontales avec regroupement « Autres », `comparison` → barres groupées capacité / places libres. Chaque graphique a une vue **Tableau**.
- Les métriques dont le libellé se termine par `(F CFA)` ou `(%)` sont affichées avec leur unité ; les pourcentages ont une jauge.
- Les routes restent sous le middleware administrateur du projet. Les réponses sont privées et non mises en cache.

| Module | Indicateurs |
|---|---|
| `parc-immobilier` | Cités, bâtiments, chambres, capacité, occupation, réservations, places libres utilisables, état des chambres et comparaison des cités |
| `equipements` | Références, stock actuel, alertes de seuil, entrées et sorties de stock |
| `campagnes` | Dossiers déposés, parcours, campagnes, niveaux, boursiers et demandes avec attribution confirmée |
| `residents` | Résidents distincts, affectations actives, nouvelles affectations et approbations des contrats |
| `entree-logement` | Notifications, lectures, distribution et rendez-vous d’entrée |
| `vie-cite` | Maintenance, coûts, catégories, incidents disciplinaires, sanctions et annonces |
| `departs` | Demandes de sortie, clôtures, motifs, états des lieux et décomptes |
| `finances` | Facturation, solde restant, encaissements validés, loyers encaissés, remboursements et transactions en ligne |
| `parametrage-herb` | Types de chambres, de cités et d’équipements |

## Règles de calcul

Les périodes prédéfinies vont du début de la semaine, du mois, du trimestre ou de l’année jusqu’à la fin de la journée courante. Les dates personnalisées sont inclusives.

Le parc et les résidents actifs représentent la situation actuelle. Les distributions de statuts et les soldes représentent aussi l’état actuel des dossiers retenus dans la période, et ne constituent pas une photographie comptable historique.

Le module `parc-immobilier` porte sur le même périmètre que la page Parc immobilier : seules les chambres configurées (numéro et type renseignés, scope `Chambre::configurees()`) sont comptées, ainsi que leurs affectations. Les deux pages affichent donc les mêmes chambres, capacité et occupants.

Les places libres sont calculées chambre par chambre après déduction des affectations confirmées et des propositions encore valables. Les chambres en maintenance, hors service ou fermées ne fournissent aucune place libre. Les affectations confirmées futures comptent comme réservations, pas comme occupation actuelle.

Les attributions sont comptées uniquement à travers l’affectation liée aux dossiers de la sélection. Avec un filtre de cité, les dossiers sans affectation sont exclus ; ils restent visibles dans le rapport global. Le taux de satisfaction divise les dossiers avec affectation confirmée par les dossiers de cette même sélection.

Le stock est global : il ne possède pas de rattachement à une cité. La consultation ne modifie jamais le stock. Si le stock courant est absent, le solde des mouvements est utilisé, ou le stock initial en l’absence de mouvement. L’entrée initiale enregistrée comme mouvement n’est pas additionnée une seconde fois.

Les annonces et les référentiels sont également globaux ; cette portée est indiquée dans leurs sections. Les contrats, notifications et rendez-vous sont rattachés à la cité via leur affectation.

Les départs proviennent des demandes de sortie, et non des affectations annulées. Les demandes clôturées sont comptées parmi les demandes déposées dans la période, sans supposer une date de clôture dans cette même période.

Les loyers sont sélectionnés par date limite de paiement, hors annulations et suppressions. Les paiements sont sélectionnés par date de paiement ; seuls les paiements validés entrent dans les encaissements. Les remboursements sont présentés séparément selon la date du paiement d’origine. Les réussites en ligne ne sont pas additionnées aux encaissements, où elles sont déjà comptabilisées.

Une table de module absente produit un message **Statistiques indisponibles**, jamais un total artificiel de zéro. Les autres erreurs SQL ne sont pas masquées. Une sélection sans enregistrements présente des compteurs à zéro et un message de liste vide.

Les tests `tests/Feature/HousingReportingTest.php` couvrent les calculs de disponibilité, l’isolation des cités, la sélection des paiements, les cohortes d’admission, le stock sans écriture et les modules non installés.
