@extends('admin.layouts.app', ['activeMenu' => 'hebergement', 'activeSubMenu' => 'equipements'])
@section('title', 'Équipements du parc immobilier')
@section('content')
<div class="p-4">
    <x-breadcrumbs title="Équipements du parc immobilier" :items="['Équipements' => route('admin.hebergement.equipements.index')]" />
    <p>Le stock disponible correspond au matériel en magasin. Chaque affectation retire la quantité du magasin ; un retour la réintègre. Les équipements défectueux doivent être remis en bon état avant retour.</p>
    <section class="card p-4 mb-4">
        <h2 class="h5">Affecter un équipement</h2>
        @adminroute('admin.hebergement.equipements.affectations.store')
        <form method="post" action="{{ route('admin.hebergement.equipements.affectations.store') }}" class="equipment-location-form">
            @csrf
            <div class="row">
                <label class="col-md-4">Équipement<select class="form-control" name="equipement_id" required>
                    <option value="">Choisir</option>@foreach($equipements as $equipment)<option value="{{ $equipment->equipement_id }}" @selected(old('equipement_id') === $equipment->equipement_id)>{{ $equipment->name }} — {{ $equipment->stock_actuel }} disponible(s)</option>@endforeach
                </select></label>
                <label class="col-md-4">Cité<select class="form-control" name="cite_id" required>
                    <option value="">Choisir</option>@foreach($cites as $cite)<option value="{{ $cite->id_cite }}" @selected(old('cite_id') === $cite->id_cite)>{{ $cite->name }}</option>@endforeach
                </select></label>
                <label class="col-md-4">Bâtiment (facultatif)<select class="form-control" name="batiment_id">
                    <option value="">Équipement commun à la cité</option>@foreach($batiments as $building)<option data-cite="{{ $building->cite_id }}" value="{{ $building->id_batiment }}" @selected(old('batiment_id') === $building->id_batiment)>{{ $building->name ?? $building->code ?? $building->id_batiment }}</option>@endforeach
                </select></label>
                <label class="col-md-4">Chambre (facultatif)<select class="form-control" name="chambre_id">
                    <option value="">Équipement commun au bâtiment</option>@foreach($chambres as $room)<option data-building="{{ $room->batiment_id }}" value="{{ $room->id_chambre }}" @selected(old('chambre_id') === $room->id_chambre)>{{ $room->numero_chambre }}</option>@endforeach
                </select></label>
                <label class="col-md-4">Quantité<input class="form-control" type="number" min="1" step="1" max="1000000" name="quantite" value="{{ old('quantite', 1) }}" required></label>
                <label class="col-md-4">État<select class="form-control" name="etat">@foreach(['bon' => 'Bon', 'moyen' => 'Moyen', 'defectueux' => 'Défectueux'] as $key => $label)<option value="{{ $key }}" @selected(old('etat', 'bon') === $key)>{{ $label }}</option>@endforeach</select></label>
            </div>
            <label class="d-block">Observations<textarea name="notes" maxlength="2000" class="form-control">{{ old('notes') }}</textarea></label>
            <button type="submit" class="btn btn-success">Affecter au parc</button>
        </form>
        @endadminroute
    </section>
    <form method="get" class="mb-3 d-flex flex-wrap align-items-end" style="gap:12px">
        <label>Cité<select name="cite_id" class="form-control"><option value="">Toutes les cités</option>@foreach($cites as $cite)<option value="{{ $cite->id_cite }}" @selected(request('cite_id') === $cite->id_cite)>{{ $cite->name }}</option>@endforeach</select></label>
        <button class="btn btn-primary mb-2">Filtrer</button>@adminroute('admin.hebergement.equipements.affectations.index')
        <a class="mb-3" href="{{ route('admin.hebergement.equipements.affectations.index') }}">Réinitialiser</a>
        @endadminroute

    </form>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Matériel</th><th>Emplacement</th><th>Quantité</th><th>État</th><th>Suivi</th></tr></thead><tbody>
        @forelse($affectations as $item)
            <tr><td>{{ $item->equipement?->name ?? 'Équipement indisponible' }}<br><small>{{ $item->notes }}</small></td>
                <td>{{ $item->cite?->name ?? $item->cite_id }}<br>{{ $item->batiment?->name ?? $item->batiment?->code }} @if($item->chambre_id) / Chambre {{ $item->chambre?->numero_chambre ?? $item->chambre_id }} @endif</td>
                <td>{{ $item->quantite }}</td><td>
                @if(!$item->returned_at)
                    @adminroute('admin.hebergement.equipements.affectations.update')
                    <form method="post" action="{{ route('admin.hebergement.equipements.affectations.update', $item) }}">@csrf @method('PATCH')
                        <select name="etat" aria-label="État du matériel" class="form-control">@foreach(['bon'=>'Bon','moyen'=>'Moyen','defectueux'=>'Défectueux'] as $key=>$label)<option value="{{ $key }}" @selected($item->etat === $key)>{{ $label }}</option>@endforeach</select>
                        <button class="btn btn-sm btn-outline-primary mt-1">Enregistrer</button>
                    </form>
                    @endadminroute
                @else {{ ucfirst($item->etat) }} @endif
                </td><td>@if($item->returned_at) Retourné le {{ $item->returned_at->format('d/m/Y H:i') }}
                    @else Affecté le {{ $item->created_at->format('d/m/Y') }}
                    @adminroute('admin.hebergement.equipements.affectations.retour')
                    <form method="post" action="{{ route('admin.hebergement.equipements.affectations.retour', $item) }}">@csrf<button class="btn btn-sm btn-outline-success mt-2" @disabled($item->etat !== 'bon')>Retour au stock ({{ $item->quantite }})</button></form>
@endadminroute
                    @endif</td></tr>
        @empty <tr><td colspan="5">Aucune affectation pour cette sélection.</td></tr> @endforelse
    </tbody></table></div>
    {{ $affectations->links() }}
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('.equipment-location-form').forEach(form => {
    const city = form.elements.cite_id, building = form.elements.batiment_id, room = form.elements.chambre_id;
    function filter(select, key, value) {
        [...select.options].forEach(option => {
            option.hidden = option.value !== '' && (!value || option.dataset[key] !== value);
            option.disabled = option.hidden;
            if (option.selected && option.disabled) select.value = '';
        });
    }
    const update = () => { filter(building, 'cite', city.value); filter(room, 'building', building.value); };
    city.addEventListener('change', update); building.addEventListener('change', update); update();
});
</script>
@endpush
