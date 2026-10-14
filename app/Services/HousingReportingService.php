<?php

namespace App\Services;

use App\Models\{AssignationChambre, Batiment, Chambre, Cite, DemandeEtudiant, DepartureRequest, DepartureSettlement, DisciplinaryReport, Equipement, EquipementMouvement, EtatDesLieux, HousingEntryAppointment, HousingEntryMessage, Loyer, OnlinePayment, OrdreTravail, Paiement, Sanction, StudentContractApproval, TypeChambre, TypeCite, TypeEquipement, Annonce};
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class HousingReportingService
{
    public const MODULE_PERMISSIONS = [
        'parc-immobilier' => ['hebergement.parc.view'],
        'equipements' => ['hebergement.equipements.view'],
        'campagnes' => ['hebergement.admissions.view'],
        'residents' => ['hebergement.residents.view'],
        'entree-logement' => ['hebergement.residents.view'],
        'vie-cite' => ['hebergement.maintenance.view', 'hebergement.discipline.view', 'hebergement.annonces.view'],
        'departs' => ['hebergement.departs.view'],
        'finances' => ['hebergement.paiements.view'],
        'parametrage-herb' => ['hebergement.parametrage.view'],
    ];

    public function visibleModules(?User $user): array
    {
        if (!$user?->hasPermission('hebergement.reporting.view')) {
            return [];
        }

        return array_filter(self::MODULES, fn ($key) =>
            app(AdminViewAccess::class)->any($user, self::MODULE_PERMISSIONS[$key]), ARRAY_FILTER_USE_KEY);
    }

    public const MODULES = [
        'parc-immobilier' => 'Parc immobilier',
        'equipements' => 'Équipements et stocks',
        'campagnes' => 'Campagnes et admissions',
        'residents' => 'Résidents et contrats',
        'entree-logement' => 'Notifications et entrée en logement',
        'vie-cite' => 'Vie résidentielle',
        'departs' => 'Départs et états des lieux',
        'finances' => 'Loyers et paiements',
        'parametrage-herb' => 'Paramétrage de l’hébergement',
    ];

    public function reports(string $module, Carbon $start, Carbon $end, ?string $cite, ?User $user = null): array
    {
        $user ??= auth('admin')->user();
        $reports = [];
        foreach ($this->visibleModules($user) as $key => $title) {
            if ($module !== 'synthese' && $module !== $key) continue;
            $reports[$key] = ['title' => $title, 'sections' => array_values(array_filter($this->sections($key, $start, $end, $cite, $user)))];
        }
        return $reports;
    }

    private function rooms(?string $cite)
    {
        return Chambre::query()->when($cite, fn ($q) => $q->where('cite_id', $cite));
    }

    private function assignments(?string $cite)
    {
        return AssignationChambre::query()->whereIn('chambre_id', $this->rooms($cite)->select('id_chambre'));
    }

    private function scope($query, ?string $cite, string $column = 'cite_id')
    {
        return $query->when($cite, fn ($q) => $q->where($column, $cite));
    }

    private function byRoom($query, ?string $cite, string $column = 'chambre_id')
    {
        return $query->when($cite, fn ($q) => $q->whereIn($column, $this->rooms($cite)->select('id_chambre')));
    }

    private function section(string $title, string $note, array $models, callable $load): array
    {
        foreach ($models as $model) {
            if (!Schema::hasTable((new $model)->getTable())) {
                return compact('title', 'note') + ['unavailable' => true, 'metrics' => [], 'tables' => []];
            }
        }
        // Unexpected SQL errors must remain errors, never plausible zero statistics.
        return compact('title', 'note') + ['unavailable' => false] + $load();
    }

    private function distribution($query, string $column, string $title): array
    {
        $rows = (clone $query)->select($column)->selectRaw('COUNT(*) as total')
            ->groupBy($column)->orderByDesc('total')->toBase()->get()
            ->map(fn ($row) => [$row->$column ?: 'Non renseigné', (int) $row->total])->all();
        return ['title' => $title, 'headers' => ['Répartition', 'Nombre'], 'rows' => $rows];
    }

    private function monthly($query, string $date, string $title, ?string $amount = null): array
    {
        // SUBSTR works on MySQL and SQLite; field names are internal constants only.
        $aggregate = $amount ? "SUM($amount)" : 'COUNT(*)';
        $rows = (clone $query)->selectRaw("SUBSTR($date, 1, 7) as month, $aggregate as total")
            ->groupBy('month')->orderBy('month')->toBase()->get()
            ->map(fn ($row) => [$row->month, (float) $row->total])->all();
        return ['title' => $title, 'headers' => ['Mois', $amount ? 'Montant (F CFA)' : 'Nombre'], 'rows' => $rows];
    }

    private function sectionFor(User $user, string $permission, string $title, string $note, array $models, callable $load): ?array
    {
        return $user->hasPermission($permission) ? $this->section($title, $note, $models, $load) : null;
    }

    private function sections(string $module, Carbon $start, Carbon $end, ?string $cite, User $user): array
    {
        $period = [$start->toDateString(), $end->toDateTimeString()];
        switch ($module) {
            case 'parc-immobilier':
                return [$this->section('Occupation et capacité', 'Situation actuelle. Les places indisponibles et réservées sont exclues des places libres. La période ne modifie pas cet instantané.', [Chambre::class, AssignationChambre::class, Batiment::class, Cite::class], function () use ($cite) {
                    $rooms = $this->rooms($cite)->get(['id_chambre', 'cite_id', 'capacite_max', 'etat']);
                    $active = $this->assignments($cite)->actives()->count();
                    $reserved = $this->assignments($cite)->mobilisantPlace()->where(fn ($q) => $q->where('statut', 'en_attente')->orWhere('date_assignation', '>', now()))->count();
                    $used = $this->assignments($cite)->mobilisantPlace()->select('chambre_id')->selectRaw('COUNT(*) as total')->groupBy('chambre_id')->pluck('total', 'chambre_id');
                    $available = fn ($room) => in_array($room->etat, ['maintenance', 'hors_service', 'fermee']) ? 0 : max(0, $room->capacite_max - ($used[$room->id_chambre] ?? 0));
                    $capacity = $rooms->sum('capacite_max');
                    $names = Cite::query()->pluck('name', 'id_cite');
                    $rows = $rooms->groupBy('cite_id')->map(fn ($items, $id) => [$names[$id] ?? 'Non renseignée', $items->count(), (int) $items->sum('capacite_max'), (int) $items->sum($available)])->values()->all();
                    return ['metrics' => ['Bâtiments' => $this->scope(Batiment::query(), $cite)->count(), 'Chambres' => $rooms->count(), 'Capacité totale' => (int) $capacity, 'Places occupées' => $active, 'Places réservées' => $reserved, 'Places libres utilisables' => (int) $rooms->sum($available), 'Occupation / capacité totale (%)' => $capacity ? round(100 * $active / $capacity, 1) : 0], 'tables' => [$this->distribution($this->rooms($cite), 'etat', 'État des chambres'), ['title' => 'Comparatif des cités', 'headers' => ['Cité', 'Chambres', 'Capacité', 'Places libres'], 'rows' => $rows]]];
                })];
            case 'equipements':
                return [$this->section('Stock et mouvements', 'Stock central global, sans rattachement à une cité. Stock actuel ; mouvements enregistrés dans la période.', [Equipement::class, EquipementMouvement::class], function () use ($period) {
                    $movements = EquipementMouvement::query();
                    $balances = (clone $movements)->select('equipement_id')->selectRaw("SUM(CASE WHEN type = 'entry' THEN quantity WHEN type = 'exit' THEN -quantity ELSE 0 END) as balance")->groupBy('equipement_id')->pluck('balance', 'equipement_id');
                    // Query builder deliberately avoids the stock accessor which writes to the DB.
                    $stock = Equipement::query()->whereNull('deleted_at')->toBase()->get(['equipement_id', 'stock_initial', 'stock_actuel', 'threshold']);
                    $quantity = fn ($row) => $row->stock_actuel ?? ($balances[$row->equipement_id] ?? $row->stock_initial ?? 0);
                    $movements->whereBetween('created_at', $period);
                    return ['metrics' => ['Références' => $stock->count(), 'Unités en stock' => $stock->sum($quantity), 'Références au seuil d’alerte' => $stock->filter(fn ($row) => $quantity($row) <= $row->threshold)->count(), 'Unités entrées' => (float) (clone $movements)->where('type', 'entry')->sum('quantity'), 'Unités sorties' => (float) (clone $movements)->where('type', 'exit')->sum('quantity')], 'tables' => [$this->distribution($movements, 'type', 'Mouvements de la période')]];
                })];
            case 'campagnes':
                return [$this->section('Dossiers déposés', 'Dépôts de la période, état actuel du dossier. Avec une cité, seuls les dossiers liés à une affectation de cette cité sont retenus ; les dossiers sans affectation restent dans la vue globale.', [DemandeEtudiant::class, AssignationChambre::class, Chambre::class], function () use ($period, $cite) {
                    $q = DemandeEtudiant::query()->whereRaw('COALESCE(date_demande, created_at) BETWEEN ? AND ?', $period)
                        ->when($cite, fn ($q) => $q->whereIn('assignation_id', $this->assignments($cite)->select('id_assignation')));
                    $assigned = (clone $q)->whereIn('assignation_id', $this->assignments($cite)->where('statut', 'confirmee')->select('id_assignation'))->count();
                    $total = (clone $q)->count();
                    return ['metrics' => ['Dossiers déposés' => $total, 'Dossiers avec attribution confirmée' => $assigned, 'Demandes satisfaites (%)' => $total ? round(100 * $assigned / $total, 1) : 0, 'Dossiers boursiers' => (clone $q)->where('boursier', 1)->count()], 'tables' => [$this->distribution($q, 'etat_dossier', 'États du parcours'), $this->distribution($q, 'campagne_id', 'Dossiers par campagne (identifiant)'), $this->distribution($q, 'niveau_etude_actuel_code', 'Niveaux d’étude')]];
                })];
            case 'residents':
                return [
                    $this->section('Résidents et affectations', 'Résidents actuellement logés ; nouvelles affectations confirmées dont la date de début est dans la période.', [AssignationChambre::class, Chambre::class], function () use ($period, $cite) {
                        $q = $this->assignments($cite);
                        $entries = (clone $q)->where('statut', 'confirmee')->whereBetween('date_assignation', $period);
                        return ['metrics' => ['Résidents actifs distincts' => (clone $q)->actives()->distinct()->count('etudiant_id'), 'Affectations actives' => (clone $q)->actives()->count(), 'Nouvelles affectations confirmées' => (clone $entries)->count()], 'tables' => [$this->monthly($entries, 'date_assignation', 'Entrées par mois')]];
                    }),
                    $this->section('Approbations des contrats', 'Approbations enregistrées dans la période. La cité est celle de l’affectation liée au contrat.', [StudentContractApproval::class, AssignationChambre::class, Chambre::class], function () use ($period, $cite) {
                        $q = StudentContractApproval::query()->whereBetween('approved_at', $period)->when($cite, fn ($q) => $q->whereIn('assignment_id', $this->assignments($cite)->select('id_assignation')));
                        return ['metrics' => ['Contrats approuvés' => $q->count()], 'tables' => [$this->monthly($q, 'approved_at', 'Approbations par mois')]];
                    }),
                ];
            case 'entree-logement':
                return [
                    $this->section('Notifications', 'Messages créés dans la période, état actuel de distribution. La cité est celle de l’affectation liée au message.', [HousingEntryMessage::class, AssignationChambre::class, Chambre::class], function () use ($period, $cite) {
                        $q = HousingEntryMessage::query()->whereBetween('created_at', $period)->when($cite, fn ($q) => $q->whereIn('assignment_id', $this->assignments($cite)->select('id_assignation')));
                        return ['metrics' => ['Messages' => (clone $q)->count(), 'Messages lus' => (clone $q)->whereNotNull('read_at')->count()], 'tables' => [$this->distribution($q, 'status', 'Distribution des messages')]];
                    }),
                    $this->section('Rendez-vous d’entrée', 'Rendez-vous dont la date prévue est dans la période.', [HousingEntryAppointment::class, AssignationChambre::class, Chambre::class], function () use ($period, $cite) {
                        $q = HousingEntryAppointment::query()->whereBetween('starts_at', $period)->when($cite, fn ($q) => $q->whereIn('assignment_id', $this->assignments($cite)->select('id_assignation')));
                        return ['metrics' => ['Rendez-vous' => $q->count()], 'tables' => [$this->distribution($q, 'status', 'État des rendez-vous')]];
                    }),
                ];
            case 'vie-cite':
                return [
                    $this->sectionFor($user, 'hebergement.maintenance.view', 'Maintenance', 'Interventions créées dans la période ; leur état et leur coût sont ceux connus aujourd’hui.', [OrdreTravail::class], function () use ($period, $cite) {
                        $q = $this->scope(OrdreTravail::query(), $cite)->whereBetween('created_at', $period);
                        return ['metrics' => ['Interventions' => (clone $q)->count(), 'Interventions ouvertes' => (clone $q)->whereNotIn('statut', ['termine', 'annule'])->count(), 'Coût réel (F CFA)' => (float) (clone $q)->sum('cout_reel')], 'tables' => [$this->distribution($q, 'statut', 'Suivi des interventions'), $this->distribution($q, 'categorie', 'Catégories'), $this->monthly($q, 'created_at', 'Interventions par mois')]];
                    }),
                    $this->sectionFor($user, 'hebergement.discipline.view', 'Discipline', 'Rapports selon la date d’incident ; sanctions selon la date de décision.', [DisciplinaryReport::class, Sanction::class], function () use ($period, $cite) {
                        $q = $this->scope(DisciplinaryReport::query(), $cite)->whereBetween('date_incident', $period);
                        $sanctions = $this->scope(Sanction::query(), $cite)->whereBetween('date_decision', $period);
                        return ['metrics' => ['Incidents signalés' => (clone $q)->count(), 'Sanctions décidées' => $sanctions->count()], 'tables' => [$this->distribution($q, 'statut', 'Suivi des rapports'), $this->distribution($q, 'gravite', 'Gravité des incidents')]];
                    }),
                    $this->sectionFor($user, 'hebergement.annonces.view', 'Annonces', 'Vue globale de toutes les audiences, non filtrée par cité. Annonces créées dans la période ; vues cumulées à ce jour.', [Annonce::class], function () use ($period) {
                        $q = Annonce::query()->whereBetween('created_at', $period);
                        return ['metrics' => ['Annonces' => (clone $q)->count(), 'Vues cumulées' => (int) (clone $q)->sum('vues')], 'tables' => [$this->distribution($q, 'statut', 'Publication')]];
                    }),
                ];
            case 'departs':
                return [
                    $this->section('Demandes de sortie', 'Demandes déposées dans la période. Une demande clôturée représente un départ finalisé ; une affectation annulée n’est pas comptée comme départ.', [DepartureRequest::class, Chambre::class], function () use ($period, $cite) {
                        $q = $this->byRoom(DepartureRequest::query(), $cite)->whereBetween('date_demande', $period);
                        return ['metrics' => ['Demandes de sortie' => (clone $q)->count(), 'Demandes clôturées' => (clone $q)->where('statut', 'cloturee')->count()], 'tables' => [$this->distribution($q, 'statut', 'Suivi des départs'), $this->distribution($q, 'motif', 'Motifs de sortie')]];
                    }),
                    $this->section('États des lieux', 'Constats établis dans la période, selon leur date d’état des lieux.', [EtatDesLieux::class], function () use ($period, $cite) {
                        $q = $this->scope(EtatDesLieux::query(), $cite, 'id_cite')->whereBetween('date_etat', $period);
                        return ['metrics' => ['Entrées' => (clone $q)->where('type_etat', 'entree')->count(), 'Sorties' => (clone $q)->where('type_etat', 'sortie')->count()], 'tables' => [$this->monthly($q, 'date_etat', 'Constats par mois')]];
                    }),
                    $this->sectionFor($user, 'hebergement.paiements.view', 'Décomptes de sortie', 'Décomptes créés dans la période ; montants calculés au départ, distincts des encaissements.', [DepartureSettlement::class, Chambre::class], function () use ($period, $cite) {
                        $q = $this->byRoom(DepartureSettlement::query(), $cite)->whereBetween('created_at', $period);
                        return ['metrics' => ['Décomptes' => (clone $q)->count(), 'Retenues (F CFA)' => (float) (clone $q)->sum('total_retenues'), 'Solde débiteur (F CFA)' => (float) (clone $q)->sum('solde_debiteur')], 'tables' => []];
                    }),
                ];
            case 'finances':
                return [
                    $this->section('Loyers et encaissements', 'Loyers non annulés à échéance dans la période ; solde restant actuel. Paiements selon leur date de paiement. Les deux cohortes sont distinctes : aucun taux de recouvrement ne les mélange.', [Loyer::class, Paiement::class], function () use ($period, $cite) {
                        $rents = $this->scope(Loyer::query(), $cite)->where('statut', '!=', 'annule')->whereBetween('date_limit_paiement', $period);
                        $payments = $this->scope(Paiement::query(), $cite)->whereBetween('date_paiement', $period);
                        $valid = (clone $payments)->where('statut_paiement', 'valide');
                        $billed = (float) (clone $rents)->sum('montant_total');
                        $balance = (float) (clone $rents)->where('solde_restant', '>', 0)->sum('solde_restant');
                        return ['metrics' => ['Loyers facturés (F CFA)' => $billed, 'Reste à recouvrer (F CFA)' => $balance, 'Part restant due (%)' => $billed > 0 ? round(100 * $balance / $billed, 1) : 0, 'Encaissements validés (F CFA)' => (float) (clone $valid)->sum('montant'), 'Dont loyers (F CFA)' => (float) (clone $valid)->where('type_paiement', 'loyer')->sum('montant'), 'Paiements remboursés (F CFA)' => (float) (clone $payments)->where('statut_paiement', 'rembourse')->sum('montant')], 'tables' => [$this->distribution($payments, 'statut_paiement', 'Statuts des paiements'), $this->distribution($valid, 'type_paiement', 'Nature des encaissements'), $this->monthly($valid, 'date_paiement', 'Encaissements mensuels', 'montant')]];
                    }),
                    $this->section('Paiements en ligne', 'Transactions créées dans la période, état actuel. Les réussites sont déjà comptabilisées dans les paiements : ne pas additionner les montants.', [OnlinePayment::class, Loyer::class], function () use ($period, $cite) {
                        $q = OnlinePayment::query()->whereBetween('created_at', $period)->when($cite, fn ($q) => $q->whereIn('loyer_id', $this->scope(Loyer::query(), $cite)->select('id_loyer')));
                        return ['metrics' => ['Transactions' => (clone $q)->count(), 'Transactions réussies' => (clone $q)->where('status', 'succeeded')->count(), 'À vérifier' => (clone $q)->where('status', 'review')->count()], 'tables' => [$this->distribution($q, 'status', 'Statuts en ligne'), $this->distribution($q, 'provider_code', 'Réseaux utilisés')]];
                    }),
                ];
            case 'parametrage-herb':
                return [$this->section('Référentiels de l’hébergement', 'Configuration actuelle globale. Les filtres de période et de cité ne s’appliquent pas aux référentiels.', [TypeChambre::class, TypeCite::class, TypeEquipement::class], fn () => ['metrics' => ['Types de chambre' => TypeChambre::query()->count(), 'Types de cité' => TypeCite::query()->count(), 'Types d’équipement' => TypeEquipement::query()->count()], 'tables' => []])];
        }
        return [];
    }
}
