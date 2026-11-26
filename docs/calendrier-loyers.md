# Calendrier des loyers et absence de pénalités

Écran : **Gestion hébergement › Paiements** (`/admin/gestion-hebergement/paiements`), onglet **Calendrier des loyers** (`?onglet=calendrier`). Le calendrier porte sur la campagne choisie en haut de la page (« Toutes les campagnes » : rien à configurer).

## Règles

- **Aucune pénalité de retard ne s'applique aux loyers.** Le calcul des pénalités a été supprimé (`Loyer::appliquerPenalites`, `LoyerRepository::appliquerPenalites`, pénalité journalière de 2 % à la génération du loyer). Les loyers sont créés avec `penalite_journaliere = 0` et la tâche quotidienne (`LoyerService::verifierEtMettreAJourStatuts`) ne fait plus que mettre à jour le statut des loyers impayés. La migration `2026_11_26_000002_supprimer_penalites_loyers` retire du solde des loyers les pénalités déjà calculées (`solde_restant -= total_penalites`, jamais sous 0) et remet `total_penalites` et `penalite_journaliere` à 0 : opération non réversible.
- L'indicateur « Pénalités » de la page Paiements est remplacé par **Prochaine échéance** (première échéance à venir du calendrier enregistré de la campagne) ; la carte « Pénalités » du rapport financier est retirée. Le type de paiement système `penalite` reste dans le paramétrage (types système toujours actifs).
- Une campagne compte **10 mois de loyer** (`CalendrierLoyer::NOMBRE_MOIS`), chacun avec sa **date d'échéance** (date limite de paiement).

## Configuration (onglet « Calendrier des loyers »)

- Tableau des 10 mois : n°, mois de loyer, échéance de paiement (jj/mm/aaaa).
- Tant qu'il n'est pas enregistré, le tableau montre une proposition : 10 mois à partir de septembre de l'année de début de la campagne (`annee_debut`, sinon l'année de `periode_debut`), échéance le 10 du mois du loyer. Elle n'est pas appliquée : sans calendrier enregistré, les mois se suivent et l'échéance tombe le 10 du mois.
- **Configurer / Modifier le calendrier** (droit `hebergement.paiements.update`, route `admin.hebergement.paiements.calendrier.update`, `PUT /admin/gestion-hebergement/paiements/calendrier/{campagne}` avec `premier_mois`, `jour`, `echeance`) ouvre un formulaire en 3 étapes :
  1. **Premier mois** : seul le premier mois de loyer se choisit ; les 10 mois se suivent à partir de lui.
  2. **Échéance** : un jour (1 à 31) et un choix à cocher, **« le [jour] du mois du loyer »** (`mois_loyer`) ou **« le [jour] du mois suivant »** (`mois_suivant`). Le jour est ramené au dernier jour des mois plus courts (31 → 28/02).
  3. **Vérification** : les 10 mois et leurs échéances, puis enregistrement.
- Contrôles (client zod et serveur `CalendrierLoyer::enregistrer`) : premier mois `aaaa-mm` valide, jour entre 1 et 31, choix de l'échéance ; les mois dont un loyer de la campagne est déjà payé (loyer facturé avec `total_paye > 0`, ou mois couvert par un paiement validé de loyer ou d'avance de la campagne, `Paiement::scopeDeCampagne`) doivent rester dans les 10 mois.
- À l'enregistrement, la date limite (`loyers.date_limit_paiement`) des loyers **non soldés** de la campagne est alignée sur la nouvelle échéance de leur mois.
- Le formulaire est prérempli avec la règle du calendrier enregistré (`CalendrierLoyer::regle` : premier mois, plus grand jour des échéances, mois du loyer ou suivant), sinon avec la proposition.

Stockage : colonne JSON `campagne.calendrier_loyer` (migration `2026_11_26_000001`), les 10 mois calculés par `CalendrierLoyer::construire`, `[{"mois": "2026-09", "echeance": "2026-09-10"}, …]`. Service : `App\Services\CalendrierLoyer`.

## Effets du calendrier enregistré

La campagne d'un étudiant est celle de sa dernière attribution confirmée, sinon celle de sa dernière demande (`CalendrierLoyer::campagneEtudiant`).

- **Nouveau paiement** (loyer, avance) : `PaiementService::getProchainMoisAPayer` donne le mois du calendrier qui suit le dernier mois payé ; sans mois payé, le premier mois du calendrier à partir du mois d'entrée dans la chambre (ou du mois en cours avant l'attribution). `moisConsecutifs` ne renvoie que les mois restants du calendrier. La recherche renvoie `mois_calendrier` (mois restants `mm-aaaa`) : le formulaire ne propose que ces mois. Au-delà, le paiement est refusé (« Il ne reste que N mois de loyer à payer sur le calendrier de la campagne. »). La répartition d'un paiement sur les mois futurs suit aussi le calendrier.
- **Loyers créés** (`PaiementService::getOrCreerLoyerParMois`, `creerLoyerMoisFutur`, `LoyerService::genererLoyerPourAssignation`) : date limite = échéance du calendrier à 23:59:59 (sinon le 10 du mois).
- **Espace étudiant › Mes loyers** (`EtudiantPaiements::releve`) : les mois listés sont ceux du calendrier (au lieu de 10 mois consécutifs) ; un mois antérieur à l'entrée n'apparaît que s'il a été facturé ou payé ; l'échéance affichée et le retard (rouge) viennent du loyer facturé, sinon du calendrier.
- **Nouveau paiement › fiche de l'étudiant** : à la place du statut « En règle / En retard », la recherche renvoie `mois_payes` et `mois_en_retard` (`mm-aaaa`), calculés comme « Mes loyers » (`EtudiantPaiements::etatDesMois`) ; un résultat en retard porte le badge « N mois en retard ».
- Les API mobiles et le paiement en ligne (`OnlinePaymentService`) ne sont pas modifiés : ils lisent le solde des loyers, désormais sans pénalités.

## Indicateurs financiers

- **Page Paiements › Total encaissé** : somme des paiements **validés** de la campagne choisie, tous types confondus (loyer, avance, caution…, `Paiement::scopeDeCampagne`). Le taux de recouvrement et les impayés restent calculés sur les loyers facturés.
- **Rapport financier** (`App\Services\RapportFinancier`, deux requêtes agrégées en PHP), filtré par **Cité** (`?residence=`), **Campagne** (`?campagne=<id>|toutes`, par défaut la campagne active ; le bouton de la page Paiements ouvre le rapport sur la campagne affichée) et **Mois** de cette campagne (`?mois=aaaa-mm`, parmi les 10 mois du calendrier des loyers, enregistré ou proposé ; sans campagne, pas de mois ; un mois hors de la campagne est ignoré ; changer de campagne remet « Tous les mois ») :
  - loyers facturés non annulés de la campagne (`Loyer::scopeDeCampagne`), mois = mois facturé : **Total attendu** (`montant_total`), **recouvré** (`total_paye`), **Impayés** (`solde_restant`), **Retards** (solde dû dont l'échéance est passée, à partir du lendemain) et **taux de recouvrement** = recouvré / attendu (borné à 100 %) ; la répartition « Payé / À échoir / En retard » totalise l'attendu ;
  - paiements validés de la campagne (`Paiement::scopeDeCampagne`), mois = date du paiement (à défaut de la création), tous types : **Total encaissé**, répartition par mode (même total) et barres « encaissés » de l'évolution. Avec une cité choisie, les paiements sans cité (caution ou avance avant l'attribution) ne sont pas comptés.
  - L'évolution couvre toujours toute la campagne, même avec un mois choisi : ses mois de loyer, plus les mois où des paiements ont été faits en dehors (caution versée avant le premier mois, par exemple) ; avec « Toutes les campagnes », tous les mois qui ont un loyer ou un paiement. Le rapport n'a plus de détail par résidence ni de liste des derniers paiements (la liste complète est sur la page Paiements).

Tests : `PaiementsPageTest` (rapport financier et total encaissé, onglet, enregistrement à partir du premier mois, échéance le mois suivant, contrôles, mois payés conservés, report des échéances, recherche), `PaiementHorsLoyerTest` (mois à payer selon le calendrier, échéances, migration des pénalités), `StudentPaymentsPageTest` (mois et échéances de « Mes loyers »).
