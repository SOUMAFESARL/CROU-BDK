<?php

namespace App\Http\Controllers\admin\immobilier\depart_etat_des_lieux;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Cite;
use App\Models\AssignationChambre;
use App\Models\Chambre;
use App\Models\DepartureSetting;
use App\Models\Loyer;
use App\Models\Caution;
use App\Models\DepartureRequest;
use App\Models\DepartureSettlement;
use App\Models\Etudiant;
use App\Models\Paiement;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\EtatDesLieuxRepositoryInterface;

class DepartEtatDesLieuxController extends Controller
{
    protected $etatDesLieux;
    public function __construct(EtatDesLieuxRepositoryInterface $etatDesLieux)
    {
        $this->etatDesLieux = $etatDesLieux;
    }

    public function depart()
    {
        $cites = Cite::orderBy('name')->get(['id_cite', 'name']);
        $activeStays = AssignationChambre::actives()->with(['etudiant:id_etudiant,name,matricule','chambre:id_chambre,numero_chambre'])
            ->get(['id_assignation','etudiant_id','chambre_id'])->filter(fn($stay)=>$stay->etudiant && $stay->chambre);
        $requests = DepartureRequest::with(['etudiant', 'chambre.cite', 'chambre.batiment', 'settlement'])
            ->latest('date_demande')->get();
        $departureRequests = $requests->where('statut', 'en_attente')->keyBy('etudiant_id');

        $inspectionHistory = \App\Models\EtatDesLieux::with(['etudiant', 'chambre', 'cite', 'batiment'])
            ->orderByDesc('date_etat')->orderByDesc('created_at')->get();
        $calendarEvents = $requests->filter(fn ($departure) => $departure->date_sortie_prevue)
            ->map(fn ($departure) => [
                'date' => $departure->date_sortie_prevue->format('Y-m-d'),
                'name' => $departure->etudiant?->name ?? 'Étudiant indisponible',
                'matricule' => $departure->etudiant?->matricule ?? '',
                'room' => $departure->chambre?->numero_chambre ?? '',
                'cite' => $departure->chambre?->cite_id ?? '',
                'status' => $departure->statut === 'cloturee' ? 'cloturee'
                    : ($departure->statut === 'annulee' ? 'refusee' : ($departure->decision === 'approuvee' ? 'approuvee' : 'en_attente')),
            ])->values();
        return view('admin.hebergement.depart-etat-des-lieux.index', compact('requests', 'cites', 'activeStays', 'departureRequests', 'inspectionHistory', 'calendarEvents'));
    }

    public function decide(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'decision' => 'required|in:approuvee,refusee,annulee',
            'motif_decision' => 'nullable|required_if:decision,refusee|string|max:2000',
        ]);
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
        $assignment=AssignationChambre::actives()->where('etudiant_id',$data['etudiant_id'])->firstOrFail();
        DepartureRequest::updateOrCreate(['etudiant_id'=>$data['etudiant_id'],'statut'=>'en_attente'],['assignation_id'=>$assignment->id_assignation,'chambre_id'=>$assignment->chambre_id,'date_demande'=>today(),'date_sortie_prevue'=>$data['date_sortie_prevue'],'motif'=>$data['motif'],'observations'=>$data['observations']??null,'created_by'=>getInfoAdmin()->users->id_user??null]);
        return response()->json(['success'=>true,'message'=>'Demande de sortie et préavis enregistrés.']);
    }

    public function etatDesLieux()
    {
      
        $etatdeslieux = $this->etatDesLieux->all();

        $etudiants = [];

        foreach ($etatdeslieux as $edl) { 
            $etudiant = $edl->etudiant;
            $chambre  = $edl->chambre;
            $batiment = $edl->batiment;
            $cite     = $edl->cite;

            if (!$etudiant) {
                continue; // sécurité si la relation manque
            }

            $key = $etudiant->id_etudiant;

            if (!isset($etudiants[$key])) {
                $etudiants[$key] = [
                    'id'         => $etudiant->id_etudiant,
                    'name'       => $etudiant->name,
                    'email'      => $etudiant->email1,
                    'matricule'  => $etudiant->matricule,
                    'cite'       => $cite->name ?? 'N/A',
                    'cite_id'    => $cite->id_cite ?? $edl->id_cite,
                    'batiment'   => $batiment->name ?? 'N/A',
                    'batiment_id'=> $batiment->id_batiment ?? $edl->id_batiment,
                    'chambre'    => $chambre->numero_chambre ?? 'N/A',
                    'chambre_id' => $chambre->id_chambre ?? $edl->id_chambre,
                    'edl_entree' => null,
                    'edl_sortie' => null,
                ];
            }

            $edlData = [
                'id'                   => $edl->etat_des_lieux_id,
                'chambre_id'           => $edl->id_chambre,
                'date'                 => optional($edl->date_etat)->format('Y-m-d'),
                'nbre_cle_recus'       => $edl->nbre_cle_recus,
                'observation_generale' => $edl->observation_generale,
                'signature_crou'       => $edl->signature_crou
                    ? config('app.storage_public') . $edl->signature_crou
                    : null,
                'signature_etudiant'   => $edl->signature_etudiant
                    ? config('app.storage_public'). $edl->signature_etudiant
                    : null,
                'pieces' => $edl->pieces->map(function ($piece) {
                    return [
                        'name'       => $piece->name_piece,
                        'name_piece' => $piece->name_piece,
                        'ordre'    => $piece->ordre,
                        'elements' => $piece->elements->map(function ($el) {
                            return [
                                'name'         => $el->name_element,
                                'name_element' => $el->name_element,
                                'etat'        => $el->etat,
                                'observation' => $el->observation,
                                'photo'       => $el->photo,
                                'photo_url'   => $el->photo_url,
                                'ordre'       => $el->ordre,
                            ];
                        })->values(),
                        // Adapte "chemin_image" / "path" / "photo" au vrai nom de colonne
                        // de ton modèle EdlImage — vérifie via `php artisan tinker` :
                        // App\Models\EdlImage::first()
                        'images' => $piece->images->map(function ($img) {
                            return [
                                'id'  => $img->edl_image_id ?? $img->id ?? null,
                                'path' => $img->chemin_fichier,
                                'url' => $img->url,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];

            // all() est trié du plus récent au plus ancien : ne pas remplacer
            // la version courante par un ancien état éventuellement incomplet.
            if ($edl->type_etat === 'entree' && $etudiants[$key]['edl_entree'] === null) {
                $etudiants[$key]['edl_entree'] = $edlData;
            } elseif ($edl->type_etat === 'sortie' && $etudiants[$key]['edl_sortie'] === null) {
                $etudiants[$key]['edl_sortie'] = $edlData;
            }
        }

        // Une demande approuvée doit être traitable même sans EDL d'entrée.
        $approvedRequests = DepartureRequest::with(['etudiant', 'chambre.cite', 'chambre.batiment'])
            ->where('statut', 'en_attente')->where('decision', 'approuvee')->get();
        foreach ($approvedRequests as $departure) {
            if (!$departure->etudiant || !$departure->chambre) continue;
            $existingData = $etudiants[$departure->etudiant_id] ?? [];
            $etudiants[$departure->etudiant_id] = array_merge($existingData, [
                'id' => $departure->etudiant->id_etudiant,
                'name' => $departure->etudiant->name,
                'email' => $departure->etudiant->email1,
                'matricule' => $departure->etudiant->matricule,
                'cite' => $departure->chambre->cite->name ?? 'N/A',
                'cite_id' => $departure->chambre->cite_id,
                'batiment' => $departure->chambre->batiment->name ?? 'N/A',
                'batiment_id' => $departure->chambre->batiment_id,
                'chambre' => $departure->chambre->numero_chambre ?? 'N/A',
                'chambre_id' => $departure->chambre_id,
                'edl_entree' => (($existingData['edl_entree']['chambre_id'] ?? null) === $departure->chambre_id) ? $existingData['edl_entree'] : null,
                'edl_sortie' => (($existingData['edl_sortie']['chambre_id'] ?? null) === $departure->chambre_id) ? $existingData['edl_sortie'] : null,
            ]);
        }

        $etudiantsData = array_values($etudiants);
        $cites = Cite::orderBy('name')->get(['id_cite', 'name']);

        return view(
            'admin.hebergement.depart-etat-des-lieux.etat-des-lieux.index',
            compact('etudiantsData', 'cites')
        );
    }

    public function storeEdl(Request $request)
    {
        $validated = $this->validateEdl($request);

        if ($validated['type_etat'] !== 'sortie') {
            return response()->json([
                'success' => false,
                'message' => "L'état des lieux d'entrée est créé par l'étudiant.",
            ], 422);
        }

        if (empty($validated['signature_crou']) || !Str::startsWith($validated['signature_crou'], 'data:image')) {
            return response()->json([
                'success' => false,
                'message' => "La signature du CROU est obligatoire avant d'assigner l'état des lieux de sortie.",
            ], 422);
        }

        $existing = $this->etatDesLieux->findSortie($validated['id_chambre'], $validated['id_etudiant']);
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Un état des lieux de sortie existe déjà pour cet étudiant.",
            ], 422);
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

        return response()->json([
            'success' => true,
            'message' => "Séjour clôturé. La chambre est maintenant « ".str_replace('_', ' ', ucfirst($result['room_status']))." ». Retenues calculées : ".number_format((float)$result['settlement']->total_retenues,0,',',' ')." FCFA.",
            'data' => $result,
        ], 201);
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

      public function updateEdl(Request $request, string $id)
    {

        $data = $this->buildData($this->validateEdl($request), $request);
        $this->etatDesLieux->update($id, $data);

        
            return response()->json([
                'success' => true,
                'message' => 'État des lieux mis à jour avec succès.',
                'data' => $data,
            ]);
        

   //     return redirect()->back()->with('success', 'État des lieux mis à jour avec succès.');
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
        ]);
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
