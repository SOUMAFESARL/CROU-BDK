<section id="page-incidents" class="content incident-student-page">
    <div class="topbar">
        <div>
            <p class="incident-eyebrow">Sécurité & vie en résidence</p>
            <h1 id="page-title">Déclarer un incident disciplinaire</h1>
        </div>
        <span class="date-badge">Traitement confidentiel</span>
    </div>

    @php $disciplineStatus = auth('etudiant')->user()->statut_disciplinaire; @endphp
    @if($disciplineStatus !== 'aucune')
    <div class="incident-alert error">Votre statut disciplinaire :
        <strong>{{ \App\Http\Services\DisciplinaryImpactService::LABELS[$disciplineStatus] }}</strong>.
        @if($disciplineStatus === 'expulsion') Contactez le service Logement pour organiser l’état des lieux de sortie. @endif
    </div>
    @endif
    <div class="incident-layout">
        <div>
            @if(session('incident_success'))
                <div class="incident-alert success">✓ {{ session('incident_success') }}</div>
            @endif
            @if($errors->incident->any())
                <div class="incident-alert error"><strong>La déclaration n’a pas pu être envoyée.</strong><ul>@foreach($errors->incident->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form class="incident-form" method="POST" action="{{ route('etudiants.incidents.store') }}">
                @csrf
                <div class="incident-form-head">
                    <span class="incident-step">1</span>
                    <div><h2>Informations sur l’incident</h2><p>Décrivez uniquement les faits dont vous avez connaissance.</p></div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="incident-nature">Nature de l’incident *</label>
                        <select class="form-select" id="incident-nature" name="nature" required>
                            <option value="">Sélectionner une catégorie</option>
                            @foreach(['tapage'=>'Tapage / nuisance sonore','violence'=>'Violence ou menace','harcelement'=>'Harcèlement','degradation'=>'Dégradation volontaire','vol'=>'Vol ou tentative de vol','non_respect_reglement'=>'Non-respect du règlement','autre'=>'Autre incident'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('nature') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="incident-date">Date et heure *</label>
                        <input class="form-input" id="incident-date" type="datetime-local" name="date_incident" max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('date_incident', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="incident-lieu">Lieu précis *</label>
                        <input class="form-input" id="incident-lieu" name="lieu" maxlength="255" value="{{ old('lieu') }}" placeholder="Ex. couloir du bâtiment, entrée principale…" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="incident-gravite">Niveau d’urgence *</label>
                        <select class="form-select" id="incident-gravite" name="gravite" required>
                            <option value="faible" @selected(old('gravite') === 'faible')>Faible — gêne ponctuelle</option>
                            <option value="moyenne" @selected(old('gravite', 'moyenne') === 'moyenne')>Moyenne — intervention souhaitée</option>
                            <option value="grave" @selected(old('gravite') === 'grave')>Grave — sécurité menacée</option>
                            <option value="critique" @selected(old('gravite') === 'critique')>Critique — danger immédiat</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="incident-description">Description factuelle *</label>
                    <p class="form-hint">Précisez ce qui s’est passé, dans quel ordre et les personnes impliquées si elles sont connues.</p>
                    <textarea class="form-textarea" id="incident-description" name="description" minlength="20" maxlength="5000" rows="7" required>{{ old('description') }}</textarea>
                    <div class="incident-counter"><span id="incident-count">0</span>/5000</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="incident-temoins">Témoins éventuels</label>
                    <textarea class="form-textarea compact" id="incident-temoins" name="temoins" maxlength="2000" placeholder="Nom, chambre ou moyen de contact, si vous les connaissez">{{ old('temoins') }}</textarea>
                </div>
                <label class="incident-check"><input type="checkbox" name="confidentiel" value="1" @checked(old('confidentiel'))><span><strong>Demander un traitement confidentiel</strong><small>Votre identité restera réservée aux agents habilités chargés du dossier.</small></span></label>

                <div class="incident-submit"><p>En envoyant ce formulaire, vous certifiez que les informations communiquées sont sincères.</p><button class="btn btn-primary" type="submit">Transmettre le signalement →</button></div>
            </form>
        </div>

        <aside class="incident-side">
            <div class="incident-emergency"><strong>Danger immédiat ?</strong><p>Éloignez-vous de la zone et contactez sans délai le poste de sécurité de votre résidence.</p></div>
            <div class="incident-residence"><span>Résidence associée</span><strong>{{ $assignment?->chambre?->cite?->name ?? 'Non disponible' }}</strong><p>{{ $assignment?->chambre?->batiment?->name ?? 'Bâtiment non renseigné' }} · Chambre {{ $assignment?->chambre?->numero_chambre ?? '—' }}</p></div>
            <div class="incident-history"><h3>Mes signalements</h3>@forelse($reports as $report)<article><div><strong>{{ $report->nature }}</strong><span class="badge {{ in_array($report->statut, ['decision_prise','cloture']) ? 'success' : ($report->statut === 'retourne' ? 'danger' : 'info') }}">{{ str($report->statut)->replace('_',' ')->title() }}</span></div><small>{{ $report->numero_rapport }} · {{ $report->date_incident?->format('d/m/Y') }}</small></article>@empty<p class="incident-empty">Aucun signalement transmis.</p>@endforelse</div>
        </aside>
    </div>
</section>

<style>
.incident-student-page{--incident-navy:#172033;--incident-line:#e7e9ee}.incident-student-page>.topbar{margin:-28px -32px 28px}.incident-eyebrow{font-size:var(--app-font-min, 15px);color:var(--teal-600);font-weight:600;text-transform:uppercase;letter-spacing:.08em;margin-bottom:3px}.incident-layout{display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:20px;max-width:1200px}.incident-form,.incident-side>div{background:#fff;border:1px solid var(--incident-line);border-radius:14px}.incident-form{padding:24px}.incident-form-head{display:flex;gap:12px;align-items:center;padding-bottom:20px;margin-bottom:22px;border-bottom:1px solid var(--incident-line)}.incident-step{width:34px;height:34px;border-radius:10px;background:var(--teal-50);color:var(--teal-600);display:grid;place-items:center;font-weight:700}.incident-form h2{font-size:17px}.incident-form-head p,.incident-submit p{font-size:var(--app-font-min, 15px);color:var(--muted);margin-top:3px}.incident-form .form-textarea.compact{min-height:75px}.incident-counter{text-align:right;font:11px 'DM Mono';color:var(--muted);margin-top:4px}.incident-check{display:flex;align-items:flex-start;gap:10px;background:var(--teal-50);border-radius:10px;padding:13px;cursor:pointer}.incident-check input{margin-top:3px;accent-color:var(--teal-600)}.incident-check strong,.incident-check small{display:block;font-size:var(--app-font-min, 15px)}.incident-check small{font-weight:400;color:var(--teal-800);margin-top:2px}.incident-submit{display:flex;align-items:center;justify-content:space-between;gap:20px;border-top:1px solid var(--incident-line);margin-top:22px;padding-top:18px}.incident-alert{padding:13px 15px;border-radius:10px;margin-bottom:14px;font-size:var(--app-font-min, 15px)}.incident-alert.success{background:var(--green-50);color:var(--green-600)}.incident-alert.error{background:var(--red-50);color:var(--red-600)}.incident-alert ul{margin:6px 0 0 18px}.incident-side{position:static;width:auto;min-height:0;background:transparent;border:0;display:block}.incident-side>div{padding:18px;margin-bottom:14px}.incident-emergency{background:#fff8ed!important;border-color:#f3d29c!important}.incident-emergency strong{color:#9b5b0a}.incident-emergency p,.incident-residence p{font-size:var(--app-font-min, 15px);color:var(--muted);line-height:1.5;margin-top:6px}.incident-residence span{display:block;font-size:var(--app-font-min, 15px);text-transform:uppercase;color:var(--muted);margin-bottom:5px}.incident-residence strong{font-size:15px}.incident-history h3{font-size:var(--app-font-min, 15px);margin-bottom:10px}.incident-history article{padding:11px 0;border-top:1px solid var(--incident-line)}.incident-history article>div{display:flex;justify-content:space-between;gap:8px;align-items:center}.incident-history article strong{font-size:var(--app-font-min, 15px)}.incident-history article small{font-size:var(--app-font-min, 15px);color:var(--muted)}.incident-empty{font-size:var(--app-font-min, 15px);color:var(--muted)}
@media(max-width:900px){.incident-layout{grid-template-columns:1fr}.incident-side{display:grid;grid-template-columns:1fr 1fr;gap:14px}.incident-side>div{margin:0}.incident-history{grid-column:1/-1}}@media(max-width:600px){.incident-student-page>.topbar{margin:-16px -16px 20px}.incident-form{padding:17px}.incident-submit{align-items:stretch;flex-direction:column}.incident-side{grid-template-columns:1fr}.incident-history{grid-column:auto}}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const description = document.getElementById('incident-description');
    const count = document.getElementById('incident-count');
    const updateCount = () => count.textContent = description.value.length;
    description.addEventListener('input', updateCount);
    updateCount();
    @if($errors->incident->any() || session('incident_success'))
        document.querySelector('nav a[data-page="incidents"]')?.click();
    @endif
});
</script>
