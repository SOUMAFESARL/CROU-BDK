@php
    $statutsDerogation = [
        'pending' => ['En attente', 'tone-amber', 'clock'],
        'approved' => ['Approuvée', 'tone-green', 'circle-check'],
        'rejected' => ['Refusée', 'tone-red', 'circle-x'],
    ];
    $peutDemander = $demande->derniereDemande?->etat_dossier === 'en_etude'
        && !$derogations->where('demande_id', $demande->derniereDemande->getKey())->contains('status', 'pending');
@endphp
<section class="ca-card">
    <div class="ca-card-header">
        <h3 class="ca-card-title"><span class="ca-kpi-icon tone-amber"><x-admin-icon name="scale" /></span> Dérogation à l’éligibilité</h3>
        <span class="ca-card-meta">{{ $derogations->count() }} enregistrée(s)</span>
    </div>
    <div class="ca-card-body">
        <p class="ca-muted ca-small" style="margin: 0 0 14px">
            Une dérogation permet de poursuivre l’admission malgré des critères non respectés. Elle doit être motivée et validée par un autre Sous-DAL ou Chef du service logement habilité. Elle ne réserve aucune chambre et ne supprime aucun contrôle de disponibilité.
        </p>

        @if($peutDemander)
            @adminroute('admin.hebergement.campagnes_admission.derogations.store')
            <form method="POST" action="{{ route('admin.hebergement.campagnes_admission.derogations.store', $demande->getKey()) }}" class="ca-field mb-4">
                @csrf
                <input type="hidden" name="demande_id" value="{{ $demande->derniereDemande->getKey() }}">
                <label for="derogation-reason" class="ca-label">Motif de la dérogation <span class="req">*</span></label>
                <textarea id="derogation-reason" name="reason" required maxlength="2000" rows="3" class="ca-input">{{ old('reason') }}</textarea>
                <span class="ca-hint">2 000 caractères maximum.</span>
                <div><button type="submit" class="ca-btn ca-btn-warning">Demander une dérogation</button></div>
            </form>
            @endadminroute
        @endif

        @forelse($derogations as $exception)
            @php [$libelle, $tone, $icone] = $statutsDerogation[$exception->status] ?? [$exception->status, 'tone-gray', 'info']; @endphp
            <article class="ca-derogation">
                <div class="ca-derogation-head">
                    <span class="ca-badge {{ $tone }}"><x-admin-icon :name="$icone" size="14" /> {{ $libelle }}</span>
                    <span class="ca-small ca-muted">{{ $exception->requester_name }} — {{ $exception->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <p>{{ $exception->reason }}</p>
                @php $nonRespectes = collect($exception->eligibility['regles'] ?? [])->reject(fn ($rule) => $rule['ok'] ?? false); @endphp
                @if($nonRespectes->isNotEmpty())
                    <ul>
                        @foreach($nonRespectes as $rule)<li>{{ $rule['label'] ?? 'Critère non respecté' }}</li>@endforeach
                    </ul>
                @endif

                @if($exception->decided_at)
                    <div class="ca-derogation-decision">
                        Décision de {{ $exception->decider_name }} — {{ $exception->decided_at->format('d/m/Y H:i') }} : {{ $exception->decision_reason }}
                    </div>
                @elseif($canApproveDerogation && (string) $exception->requested_by !== (string) auth('admin')->id())
                    @adminroute('admin.hebergement.campagnes_admission.derogations.decide')
                    <form method="POST" action="{{ route('admin.hebergement.campagnes_admission.derogations.decide', $exception->id) }}" class="ca-field ca-derogation-decision">
                        @csrf
                        <label for="decision-{{ $exception->id }}" class="ca-label">Motif de la décision <span class="req">*</span></label>
                        <textarea id="decision-{{ $exception->id }}" name="reason" required maxlength="2000" rows="2" class="ca-input"></textarea>
                        <div class="ca-actions">
                            <button name="decision" value="approve" class="ca-btn ca-btn-primary ca-btn-sm"><x-admin-icon name="check" size="16" /> Approuver la dérogation</button>
                            <button name="decision" value="reject" class="ca-btn ca-btn-danger ca-btn-sm"><x-admin-icon name="x" size="16" /> Refuser</button>
                        </div>
                    </form>
                    @endadminroute
                @else
                    <div class="ca-derogation-decision">Validation attendue d’un autre responsable habilité.</div>
                @endif
            </article>
        @empty
            <p class="ca-muted" style="margin: 0">Aucune dérogation enregistrée.</p>
        @endforelse
    </div>
</section>
