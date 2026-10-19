<?php

namespace App\Http\Controllers\admin\immobilier\depart_etat_des_lieux;

use App\Http\Controllers\Controller;
use App\Http\Services\EdlPhotoService;
use App\Services\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Cite;
use App\Models\AssignationChambre;
use App\Models\DepartureSetting;
use App\Models\EtatDesLieux;
use App\Models\Loyer;
use App\Models\Caution;
use App\Models\DepartureRequest;
use App\Models\DepartureSettlement;
use App\Models\Etudiant;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Repositories\Interfaces\EtatDesLieuxRepositoryInterface;
use Inertia\Inertia;

class DepartEtatDesLieuxController extends Controller
{
    /** Motifs d'une demande de sortie (validation de `storeDepartureRequest`). */
    private const MOTIFS = [
        'fin_etudes' => 'Fin d’études',
        'demenagement' => 'Déménagement',
        'stage' => 'Stage professionnel',
        'raison_personnelle' => 'Raison personnelle',
        'autre' => 'Autre',
    ];

    /** Modes de restitution de la caution (validation de `restituerCaution`). */
    private const MODES_RESTITUTION = [
        'especes' => 'Espèces',
        'virement' => 'Virement',
        'orange_money' => 'Orange Money',
        'mtn_money' => 'MTN Money',
        'moov_money' => 'Moov Money',
        'carte_bancaire' => 'Carte bancaire',
        'autre' => 'Autre',
    ];

    protected $etatDesLieux;
    public function __construct(EtatDesLieuxRepositoryInterface $etatDesLieux)
    {
        $this->etatDesLieux = $etatDesLieux;
    }

    /** URL d'une route admin, ou null si l'administrateur connecté n'y a pas accès (remplace `@adminroute`). */
    private function urlSiAutorise(Request $request): \Closure
    {
        $access = app(AdminAccess::class);
        $admin = $request->user('admin');

        return fn (string $route, mixed $parametres = []) => $access->allows($admin, $route) ? route($route, $parametres) : null;
    }

    private static function options(array $liste): array
    {
        return collect($liste)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all();
    }

    /** Statut affiché d'une demande : en attente, approuvée, clôturée ou refusée / annulée. */
    private static function statutDemande(DepartureRequest $departure): string
    {
        return match (true) {
            $departure->statut === 'cloturee' => 'cloturee',
            $departure->statut === 'annulee' => 'refusee',
            $departure->decision === 'approuvee' => 'approuvee',
            default => 'en_attente',
        };
    }

    /** Départs : liste des états des lieux (premier onglet), demandes à traiter, calendrier des sorties et historiques (page React). */
    public function depart(Request $request)
    {
        $url = $this->urlSiAutorise($request);
        $requests = DepartureRequest::with(['etudiant', 'chambre.cite', 'chambre.batiment', 'settlement'])
            ->latest('date_demande')->get();
        // Une sortie issue d'une expulsion active ne peut pas être refusée ici (règle de `decide`).
        $sanctionsActives = \App\Models\Sanction::whereIn('id_sanction', $requests->pluck('sanction_id')->filter()->unique()->values())
            ->where('statut', 'active')->pluck('id_sanction')->all();
        $pending = $requests->where('statut', 'en_attente')->pluck('etudiant_id')->all();

        $demandes = $requests->map(function (DepartureRequest $departure) use ($url, $sanctionsActives) {
            $settlement = $departure->settlement;
            $statut = self::statutDemande($departure);

            return [
                'id' => $departure->id_demande_sortie,
                'etudiant_id' => $departure->etudiant_id,
                'etudiant' => $departure->etudiant?->name ?? 'Étudiant indisponible',
                'matricule' => $departure->etudiant?->matricule,
                'chambre' => $departure->chambre?->numero_chambre,
                'cite' => $departure->chambre?->cite?->name,
                'cite_id' => $departure->chambre?->cite_id,
                'batiment' => $departure->chambre?->batiment?->name,
                'date_demande' => $departure->date_demande?->format('Y-m-d'),
                'date_sortie' => $departure->date_sortie_prevue?->format('Y-m-d'),
                'motif' => self::MOTIFS[$departure->motif] ?? ($departure->motif ? ucfirst(str_replace('_', ' ', $departure->motif)) : null),
                'observations' => $departure->observations,
                'statut' => $statut,
                'decision' => $departure->decision,
                'motif_decision' => $departure->motif_decision,
                'expulsion' => (bool) $departure->sanction_id,
                'expulsion_active' => in_array($departure->sanction_id, $sanctionsActives, true),
                'decompte' => $settlement ? [
                    'preavis_requis' => (int) $settlement->preavis_requis_jours,
                    'preavis_effectif' => (int) $settlement->preavis_effectif_jours,
                    'penalite_preavis' => (float) $settlement->penalite_preavis,
                    'degradations' => collect($settlement->retenues_degradations ?? [])->map(fn ($r) => [
                        'piece' => $r['piece'] ?? '', 'element' => $r['element'] ?? '', 'regle' => $r['rule'] ?? '', 'montant' => (float) ($r['amount'] ?? 0),
                    ])->values()->all(),
                    'total_degradations' => (float) $settlement->total_degradations,
                    'total_retenues' => (float) $settlement->total_retenues,
                    'impute_caution' => (float) $settlement->impute_caution,
                    'solde_debiteur' => (float) $settlement->solde_debiteur,
                    'caution_a_restituer' => (float) $settlement->caution_a_restituer,
                    'caution_restituee' => (float) $settlement->caution_restituee,
                    'date_restitution' => $settlement->date_restitution?->format('Y-m-d'),
                    'mode_restitution' => self::MODES_RESTITUTION[$settlement->mode_restitution] ?? $settlement->mode_restitution,
                ] : null,
                'urls' => [
                    'decision' => $statut === 'en_attente' ? $url('admin.hebergement.depart_etat_des_lieux.departure_request.decision', $departure->id_demande_sortie) : null,
                    'etat_des_lieux' => $statut === 'approuvee'
                        ? $url('admin.hebergement.depart_etat_des_lieux.etatDesLieux', ['etudiant' => $departure->etudiant_id])
                        : null,
                    'pdf' => $settlement ? $url('admin.hebergement.depart_etat_des_lieux.settlement.pdf', $departure->id_demande_sortie) : null,
                    'restitution' => $settlement && (float) $settlement->caution_a_restituer > 0 && ! $settlement->date_restitution
                        ? $url('admin.hebergement.depart_etat_des_lieux.settlement.refund', $settlement->id_decompte)
                        : null,
                ],
            ];
        })->values();

        $inspections = EtatDesLieux::with(['etudiant', 'chambre', 'cite', 'batiment'])
            ->orderByDesc('date_etat')->orderByDesc('created_at')->get()
            ->map(fn (EtatDesLieux $edl) => [
                'id' => $edl->etat_des_lieux_id,
                'date' => $edl->date_etat?->format('Y-m-d'),
                'etudiant' => $edl->etudiant?->name ?? 'Étudiant indisponible',
                'matricule' => $edl->etudiant?->matricule,
                'cite' => $edl->cite?->name,
                'cite_id' => $edl->id_cite,
                'batiment' => $edl->batiment?->name,
                'chambre' => $edl->chambre?->numero_chambre,
                'type' => $edl->type_etat === 'entree' ? 'entree' : 'sortie',
                'observation' => $edl->observation_generale,
                'pdf' => $url('admin.hebergement.depart_etat_des_lieux.edl.pdf', $edl->etat_des_lieux_id),
            ])->values();

        $residents = AssignationChambre::actives()
            ->with(['etudiant:id_etudiant,name,matricule', 'chambre:id_chambre,numero_chambre'])
            ->get(['id_assignation', 'etudiant_id', 'chambre_id'])
            ->filter(fn ($stay) => $stay->etudiant && $stay->chambre)
            ->map(fn ($stay) => [
                'id' => $stay->etudiant_id,
                'name' => $stay->etudiant->name,
                'matricule' => $stay->etudiant->matricule,
                'chambre' => $stay->chambre->numero_chambre,
                'preavis' => in_array($stay->etudiant_id, $pending, true),
            ])->unique('id')->sortBy('name')->values();

        return Inertia::render('Admin/Hebergement/Departs/Index', [
            'demandes' => $demandes,
            'inspections' => $inspections,
            'residents' => $residents,
            'cites' => Cite::orderBy('name')->get(['id_cite', 'name'])->map(fn ($c) => ['id' => $c->id_cite, 'name' => $c->name])->values(),
            'motifs' => self::options(self::MOTIFS),
            'modesRestitution' => self::options(self::MODES_RESTITUTION),
            'etatsDesLieux' => $this->donneesEtatsDesLieux($url),
            'urls' => [
                'store' => $url('admin.hebergement.depart_etat_des_lieux.store_departure_request'),
                'parametres' => $url('admin.hebergement.depart_etat_des_lieux.settings.edit'),
            ],
        ]);
    }

    public function decide(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'decision' => 'required|in:approuvee,refusee,annulee',
            'motif_decision' => 'nullable|required_if:decision,refusee|string|max:2000',
        ], ['motif_decision.required_if' => 'Indiquez le motif du refus.']);
        $departure = DepartureRequest::where('statut', 'en_attente')->findOrFail($id);
        if ($departure->sanction_id && $data['decision'] !== 'approuvee'
            && \App\Models\Sanction::whereKey($departure->sanction_id)->where('statut', 'active')->exists()) {
            throw ValidationException::withMessages(['decision' => 'Cette sortie résulte d’une expulsion active. La sanction doit être levée depuis le module Sanctions.']);
        }
        $departure->update([
            'decision' => $data['decision'],
            'motif_decision' => $data['motif_decision'] ?? null,
            'date_decision' => now(),
            'traite_par' => getInfoAdmin()->users->id_user ?? null,
            'statut' => in_array($data['decision'], ['refusee', 'annulee'], true) ? 'annulee' : 'en_attente',
        ]);

        return back()->with('success', $data['decision'] === 'approuvee'
            ? "La demande de sortie a été approuvée. L'état des lieux peut être réalisé."
            : 'La décision a été enregistrée.');
    }

    public function storeDepartureRequest(Request $request)
    {
        $data=$request->validate(['etudiant_id'=>'required|exists:etudiants,id_etudiant','date_sortie_prevue'=>'required|date|after_or_equal:today','motif'=>'required|in:fin_etudes,demenagement,stage,raison_personnelle,autre','observations'=>'nullable|string|max:2000']);
        $assignment=AssignationChambre::actives()->where('etudiant_id',$data['etudiant_id'])->first();
        if (!$assignment) {
            throw ValidationException::withMessages(['etudiant_id' => "Ce résident n'a pas de séjour en cours."]);
        }
        DepartureRequest::updateOrCreate(['etudiant_id'=>$data['etudiant_id'],'statut'=>'en_attente'],['assignation_id'=>$assignment->id_assignation,'chambre_id'=>$assignment->chambre_id,'date_demande'=>today(),'date_sortie_prevue'=>$data['date_sortie_prevue'],'motif'=>$data['motif'],'observations'=>$data['observations']??null,'created_by'=>getInfoAdmin()->users->id_user??null]);
        $message = 'Demande de sortie et préavis enregistrés.';

        return $request->wantsJson() ? response()->json(['success'=>true,'message'=>$message]) : back()->with('success', $message);
    }

    /** Constat d'entrée ou de sortie envoyé à la page React (seuls les champs affichés). */
    private function constat(EtatDesLieux $edl, \Closure $url): array
    {
        $photo = fn (?string $chemin, ?string $lien = null) => $chemin ? ['chemin' => $chemin, 'url' => $lien ?? EdlPhotoService::url($chemin)] : null;

        return [
            'id' => $edl->etat_des_lieux_id,
            'chambre_id' => $edl->id_chambre,
            'date' => $edl->date_etat?->format('Y-m-d'),
            'cles' => $edl->nbre_cle_recus !== null ? (int) $edl->nbre_cle_recus : null,
            'observation' => $edl->observation_generale,
            'signature_crou' => EdlPhotoService::url($edl->signature_crou),
            'signature_etudiant' => EdlPhotoService::url($edl->signature_etudiant),
            'pieces' => $edl->pieces->map(fn ($piece) => [
                'nom' => (string) $piece->name_piece,
                'photos' => $piece->images->map(fn ($img) => $photo($img->chemin_fichier, $img->url))->filter()->values()->all(),
                'elements' => $piece->elements->map(fn ($el) => [
                    'nom' => (string) $el->name_element,
                    'etat' => in_array($el->etat, ['bon', 'moyen', 'mauvais'], true) ? $el->etat : null,
                    'observation' => $el->observation,
                    'photo' => $photo($el->photo, $el->photo_url),
                ])->values()->all(),
            ])->values()->all(),
            'urls' => [
                // L'entrée appartient à l'étudiant : le CROU la signe une seule fois.
                'signer' => $edl->type_etat === 'entree' && ! $edl->signature_crou
                    ? $url('admin.hebergement.depart_etat_des_lieux.update_eld', $edl->etat_des_lieux_id)
                    : null,
                'pdf' => $url('admin.hebergement.depart_etat_des_lieux.edl.pdf', $edl->etat_des_lieux_id),
            ],
        ];
    }

    /** États des lieux par résident : signature des entrées et réalisation des sorties approuvées (page React). */
    public function etatDesLieux(Request $request)
    {
        $url = $this->urlSiAutorise($request);

        return Inertia::render('Admin/Hebergement/Departs/EtatsDesLieux', [
            ...$this->donneesEtatsDesLieux($url),
            'cites' => Cite::orderBy('name')->get(['id_cite', 'name'])->map(fn ($c) => ['id' => $c->id_cite, 'name' => $c->name])->values(),
            'urls' => [
                'store' => $url('admin.hebergement.depart_etat_des_lieux.store_edl'),
                'departs' => $url('admin.hebergement.depart_etat_des_lieux.depart'),
            ],
        ]);
    }

    /** Résidents avec leurs derniers constats d'entrée / de sortie, grille de retenues et préavis (liste des états des lieux). */
    private function donneesEtatsDesLieux(\Closure $url): array
    {
        $etudiants = [];

        // all() est trié du plus récent au plus ancien : on garde la version la plus récente de chaque type.
        foreach ($this->etatDesLieux->all() as $edl) {
            $etudiant = $edl->etudiant;
            if (!$etudiant) {
                continue; // sécurité si la relation manque
            }

            $key = $etudiant->id_etudiant;
            $etudiants[$key] ??= [
                'id' => $etudiant->id_etudiant,
                'name' => $etudiant->name,
                'email' => $etudiant->email1,
                'matricule' => $etudiant->matricule,
                'cite' => $edl->cite->name ?? null,
                'cite_id' => $edl->cite->id_cite ?? $edl->id_cite,
                'batiment' => $edl->batiment->name ?? null,
                'batiment_id' => $edl->batiment->id_batiment ?? $edl->id_batiment,
                'chambre' => $edl->chambre->numero_chambre ?? null,
                'chambre_id' => $edl->chambre->id_chambre ?? $edl->id_chambre,
                'entree' => null,
                'sortie' => null,
                'demande' => null,
            ];

            $type = $edl->type_etat === 'entree' ? 'entree' : ($edl->type_etat === 'sortie' ? 'sortie' : null);
            if ($type && $etudiants[$key][$type] === null) {
                $etudiants[$key][$type] = $this->constat($edl, $url);
            }
        }

        // Une demande approuvée doit être traitable même sans EDL d'entrée.
        $approvedRequests = DepartureRequest::with(['etudiant', 'chambre.cite', 'chambre.batiment'])
            ->where('statut', 'en_attente')->where('decision', 'approuvee')->get();
        foreach ($approvedRequests as $departure) {
            if (!$departure->etudiant || !$departure->chambre) continue;
            $existing = $etudiants[$departure->etudiant_id] ?? [];
            $memeChambre = fn (?array $constat) => ($constat['chambre_id'] ?? null) === $departure->chambre_id ? $constat : null;
            $etudiants[$departure->etudiant_id] = array_merge($existing, [
                'id' => $departure->etudiant->id_etudiant,
                'name' => $departure->etudiant->name,
                'email' => $departure->etudiant->email1,
                'matricule' => $departure->etudiant->matricule,
                'cite' => $departure->chambre->cite->name ?? null,
                'cite_id' => $departure->chambre->cite_id,
                'batiment' => $departure->chambre->batiment->name ?? null,
                'batiment_id' => $departure->chambre->batiment_id,
                'chambre' => $departure->chambre->numero_chambre,
                'chambre_id' => $departure->chambre_id,
                'entree' => $memeChambre($existing['entree'] ?? null),
                'sortie' => $memeChambre($existing['sortie'] ?? null),
                'demande' => [
                    'id' => $departure->id_demande_sortie,
                    'date_demande' => $departure->date_demande?->format('Y-m-d'),
                    'date_sortie' => $departure->date_sortie_prevue?->format('Y-m-d'),
                ],
            ]);
        }

        $settings = DepartureSetting::current();

        return [
            'residents' => array_values($etudiants),
            'grille' => collect($settings->damage_retention_grid ?? [])
                ->filter(fn ($r) => is_array($r) && trim((string) ($r['label'] ?? '')) !== '')
                ->map(fn ($r) => ['label' => (string) $r['label'], 'amount' => (float) ($r['amount'] ?? 0)])->values(),
            'preavisJours' => (int) $settings->notice_days,
            'urls' => ['store' => $url('admin.hebergement.depart_etat_des_lieux.store_edl')],
        ];
    }

    public function storeEdl(Request $request)
    {
        $validated = $this->validateEdl($request);

        if ($validated['type_etat'] !== 'sortie') {
            throw ValidationException::withMessages(['type_etat' => "L'état des lieux d'entrée est créé par l'étudiant."]);
        }

        if (empty($validated['signature_crou']) || !Str::startsWith($validated['signature_crou'], 'data:image')) {
            throw ValidationException::withMessages(['signature_crou' => "La signature du CROU est obligatoire avant d'assigner l'état des lieux de sortie."]);
        }

        $existing = $this->etatDesLieux->findSortie($validated['id_chambre'], $validated['id_etudiant']);
        if ($existing) {
            throw ValidationException::withMessages(['id_etudiant' => 'Un état des lieux de sortie existe déjà pour cet étudiant.']);
        }

        $result = DB::transaction(function () use ($validated, $request) {
            Etudiant::whereKey($validated['id_etudiant'])->lockForUpdate()->firstOrFail();
            $settings = DepartureSetting::current();
            $unpaid = (float) Loyer::where('etudiant_id', $validated['id_etudiant'])->sum('solde_restant');
            $room = app(\App\Http\Services\AttributionRules::class)->verrouillerChambre($validated['id_chambre']);
            $assignment = AssignationChambre::actives()
                ->where('etudiant_id', $validated['id_etudiant'])
                ->where('chambre_id', $validated['id_chambre'])
                ->lockForUpdate()->first();
            if (!$assignment) {
                throw ValidationException::withMessages(['id_etudiant' => 'Aucun séjour actif ne correspond à ce résident et cette chambre.']);
            }

            $departureRequest = DepartureRequest::where('etudiant_id',$validated['id_etudiant'])->where('assignation_id',$assignment->id_assignation)->where('statut','en_attente')->where('decision','approuvee')->lockForUpdate()->latest('date_demande')->first();
            if (!$departureRequest) {
                throw ValidationException::withMessages(['id_etudiant' => "La demande de sortie doit d'abord être approuvée par l'administration."]);
            }
            $rules = app(\App\Http\Services\DepartureClosureRules::class)->evaluate(
                $settings, $departureRequest->date_demande->toDateString(),
                $validated['date_etat'] ?? now()->toDateString(),
                json_decode($validated['pieces_json'] ?? '[]', true) ?? [], $unpaid
            );
            $noticeDays = $rules['preavis_effectif_jours'];
            $noticePenalty = $rules['penalite_preavis'];
            $damageRetentions = $rules['retenues_degradations'];
            $edl = $this->etatDesLieux->create($this->buildData($validated, $request));
            $damageTotal = collect($damageRetentions)->sum('amount');
            $totalRetention = $noticePenalty + $damageTotal;
            $caution = Caution::where('resident_id',$validated['id_etudiant'])->where('chambre_id',$validated['id_chambre'])->lockForUpdate()->latest('date_encaissement')->first();
            $availableDeposit = $caution ? max(0,(float)$caution->montant_initial-(float)$caution->montant_retenu-(float)$caution->montant_restitue) : 0;
            $fromDeposit = min($totalRetention,$availableDeposit); $debitBalance=max(0,$totalRetention-$fromDeposit);
            $refundableDeposit = max(0, $availableDeposit - $fromDeposit);
            if($caution){$details=$caution->details_retenues??[];if($totalRetention>0)$details[]=['date'=>now()->toDateString(),'type'=>'cloture_sejour','penalite_preavis'=>$noticePenalty,'degradations'=>$damageRetentions,'total'=>$totalRetention];$caution->update(['montant_retenu'=>(float)$caution->montant_retenu+$fromDeposit,'solde'=>$refundableDeposit,'details_retenues'=>$details,'statut'=>$debitBalance>0?'solde_debiteur':'retenue_partielle']);}
            $settlement = DepartureSettlement::create(['demande_sortie_id'=>$departureRequest->id_demande_sortie,'etat_des_lieux_id'=>$edl->etat_des_lieux_id,'etudiant_id'=>$validated['id_etudiant'],'chambre_id'=>$validated['id_chambre'],'preavis_requis_jours'=>$settings->notice_days,'preavis_effectif_jours'=>max(0,$noticeDays),'penalite_preavis'=>$noticePenalty,'retenues_degradations'=>$damageRetentions,'total_degradations'=>$damageTotal,'total_retenues'=>$totalRetention,'impute_caution'=>$fromDeposit,'caution_disponible'=>$availableDeposit,'caution_a_restituer'=>$refundableDeposit,'solde_debiteur'=>$debitBalance,'created_by'=>getInfoAdmin()->users->id_user??null]);
            $departureRequest->update(['statut'=>'cloturee']);
            $assignment->update(['statut'=>$departureRequest->sanction_id ? 'expulsion' : 'annulee', 'date_fin'=>$validated['date_etat'] ?? now()]);
            $dossier = \App\Models\DemandeEtudiant::where('assignation_id',$assignment->getKey())->first();
            if ($dossier && $dossier->etat_dossier === 'attribue_definitif') {
                app(\App\Http\Services\DossierWorkflow::class)->transition($dossier->getKey(),'cloture','systeme',auth('admin')->id(),'État des lieux de sortie enregistré.', [
                    'regles_cloture'=>$rules,
                    'decompte_id'=>$settlement->getKey(),
                    'etat_des_lieux_id'=>$edl->etat_des_lieux_id,
                    'total_retenues'=>$totalRetention,
                    'impute_caution'=>$fromDeposit,
                    'caution_a_restituer'=>$refundableDeposit,
                    'solde_debiteur'=>$debitBalance,
                ]);
            }
            $room->appliquerEtatApresDepart($validated['etat_chambre_sortie']);
            Etudiant::where('id_etudiant', $validated['id_etudiant'])->update(['statut_inscription'=>'archivee']);
            return ['edl'=>$edl,'settlement'=>$settlement,'room_status'=>$room->etat];
        });

        $message = "Séjour clôturé. La chambre est maintenant « ".str_replace('_', ' ', ucfirst($result['room_status']))." ». Retenues calculées : ".number_format((float)$result['settlement']->total_retenues,0,',',' ')." FCFA.";

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $message, 'data' => $result], 201)
            : back()->with('success', $message);
    }

    public function restituerCaution(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate(['methode_paiement'=>'required|in:orange_money,mtn_money,moov_money,carte_bancaire,virement,especes,autre']);
        DB::transaction(function () use ($id, $data) {
            $settlement = DepartureSettlement::whereNull('date_restitution')->lockForUpdate()->findOrFail($id);
            $amount = (float) $settlement->caution_a_restituer;
            if ($amount <= 0) throw ValidationException::withMessages(['caution'=>'Aucun montant de caution ne reste à restituer.']);
            $departure = DepartureRequest::with('assignation')->where('id_demande_sortie', $settlement->demande_sortie_id)->first();
            $payment = Paiement::create([
                'resident_id'=>$settlement->etudiant_id,'contrat_id'=>$departure?->assignation_id,'chambre_id'=>$settlement->chambre_id,
                'type_paiement'=>'caution','montant'=>$amount,'montant_du'=>$amount,'devise'=>'XOF','methode_paiement'=>$data['methode_paiement'],
                'statut_paiement'=>'rembourse','date_paiement'=>now(),'notes'=>'Restitution de caution après clôture du séjour',
            ]);
            Caution::where('resident_id',$settlement->etudiant_id)->where('chambre_id',$settlement->chambre_id)->latest('date_encaissement')->first()?->update([
                'montant_restitue'=>DB::raw('montant_restitue + '.$amount),'solde'=>0,'statut'=>'restitue','date_restitution'=>today(),'paiement_restitution_id'=>$payment->id_paiement,
            ]);
            $settlement->update(['caution_restituee'=>$amount,'mode_restitution'=>$data['methode_paiement'],'paiement_restitution_id'=>$payment->id_paiement,'date_restitution'=>now()]);
        });
        return back()->with('success', 'La restitution de caution a été enregistrée.');
    }

    public function settlementPdf(string $id)
    {
        $departure = DepartureRequest::with(['etudiant','chambre.cite','chambre.batiment','settlement'])->findOrFail($id);
        abort_unless($departure->settlement, 404);
        return Pdf::loadView('admin.hebergement.depart-etat-des-lieux.settlement-pdf', compact('departure'))
            ->download('decompte-sortie-'.$departure->id_demande_sortie.'.pdf');
    }

    /** PDF d'un état des lieux (même document que celui de l'espace étudiant), remplace l'impression de l'ancienne page. */
    public function edlPdf(string $id)
    {
        $inventory = EtatDesLieux::with(['pieces.elements', 'pieces.images', 'etudiant', 'cite', 'batiment', 'chambre'])->findOrFail($id);

        return app(\App\Http\Services\StudentInventoryPdfService::class)->document($inventory)
            ->stream('etat-des-lieux-'.$inventory->type_etat.'-'.$inventory->getKey().'.pdf')
            ->header('Cache-Control', 'private, no-store');
    }

    public function updateEdl(Request $request, string $id)
    {
        $data = $this->buildData($this->validateEdl($request), $request);
        $this->etatDesLieux->update($id, $data);
        $message = 'État des lieux mis à jour avec succès.';

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $message, 'data' => $data])
            : back()->with('success', $message);
    }

    private function validateEdl(Request $request): array
    {
        $data = $request->validate([
            'type_etat'            => 'required|in:entree,sortie',
            'id_cite'              => 'required|string',
            'id_batiment'          => 'required|string',
            'id_chambre'           => 'required|string',
            'id_etudiant'          => 'required|string',
            'date_etat'            => 'nullable|date',
            'nbre_cle_recus'       => 'nullable|integer|min:0',
            'observation_generale' => 'nullable|string',
            'signature_crou'       => 'nullable|string',
            'signature_etudiant'   => 'nullable|string',
            'pieces_json'          => 'nullable|json',
            'etat_chambre_sortie'  => 'required_if:type_etat,sortie|in:disponible,maintenance,hors_service',
        ], ['etat_chambre_sortie.required_if' => 'Choisissez le statut de la chambre après la sortie.']);
        if (!empty($data['pieces_json'])) {
            \Illuminate\Support\Facades\Validator::make(
                ['pieces'=>json_decode($data['pieces_json'],true)],
                ['pieces'=>'array', 'pieces.*'=>'array',
                 'pieces.*.elements'=>'sometimes|array', 'pieces.*.elements.*'=>'array',
                 'pieces.*.elements.*.etat'=>'required|in:bon,moyen,mauvais']
            )->validate();
        }
        return $data;
    }


    private function buildData(array $validated, Request $request): array
    {
        // Signatures base64 → fichiers disque
        foreach (['signature_crou', 'signature_etudiant'] as $field) {
            $val = $validated[$field] ?? null;
            if ($val && Str::startsWith($val, 'data:image')) {
                $bin      = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $val));
                $filename = 'signatures/' . Str::uuid() . '.png';
                Storage::disk('public')->put($filename, $bin);
                $validated[$field] = $filename;
            } else {
                unset($validated[$field]);
            }
        }

        // Pièces JSON → tableau PHP avec clés normalisées et valeurs sécurisées
        $pieces = [];
        if (!empty($validated['pieces_json'])) {
            $decoded = json_decode($validated['pieces_json'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $roomIndex => $room) {
                    $elements = [];
                    foreach ($room['elements'] ?? [] as $elIndex => $el) {
                        $etat = in_array($el['etat'] ?? '', ['bon', 'moyen', 'mauvais'])
                            ? $el['etat']
                            : 'bon';                         // valeur par défaut sûre

                        $elements[] = [
                            'name_element' => $el['name_element'] ?? $el['name'] ?? '',
                            'etat'         => $etat,
                            'observation'  => $el['observation'] ?? $el['obs'] ?? null,
                            'photo'        => $el['photo'] ?? null,
                            'ordre'        => $el['ordre'] ?? ($elIndex + 1),
                        ];
                    }

                    $pieces[] = [
                        'name_piece' => $room['name_piece'] ?? $room['name'] ?? '',
                        'ordre'      => $room['ordre'] ?? ($roomIndex + 1),
                        'elements'   => $elements,
                        'photos'     => $room['photos'] ?? [],
                    ];
                }
            }
        }

        $validated['pieces'] = $pieces;
        unset($validated['pieces_json']);

        return $validated;
    }


}
