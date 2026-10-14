@extends('admin.layouts.app', [
    'activeMenu' => 'hebergement',
    'activeSubMenu' => 'parc-immobilier'
])

@section('title', 'Parc immobilier - ' . ($editMode ? 'Modifier' : 'Ajouter'))

@section('content')
    <div class="page-body">
        <x-breadcrumbs
                title="{{ $editMode ? 'Modifier un élément' : 'Ajouter un élément' }}"
                :items="[
                'Gestion Hébergement' => route('admin.hebergement.index'),
                'Parc Immobilier' => route('admin.hebergement.parc-immobilier.index'),
                ($editMode ? 'Modifier' : 'Ajouter') => null,
            ]"
        />

        <div class="pt-2">


            <!-- Dans la section des cartes, modifiez comme suit : -->
            <div class="cards-container">
                <!-- Card Nouveau Campus -->
{{--                @unless($editMode)--}}
{{--                    <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'campus']) }}">--}}
{{--                        <div class="card-select card-option primary {{ $selectedType === 'campus' ? 'selected' : '' }}"--}}
{{--                             data-target="campus-form"--}}
{{--                             @if($editMode && $selectedType !== 'campus') style="display: none;" @endif>--}}
{{--                            <div class="icone-stats-card">--}}
{{--                                <i class="fas fa-university"></i>--}}
{{--                            </div>--}}
{{--                            <div class="card-select-content">--}}
{{--                                <div class="titre-card-select">--}}
{{--                                    {{ $editMode && $selectedType === 'campus' ? 'Modifier Campus' : 'Nouveau Campus' }}--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </a>--}}
{{--                @endunless--}}

                <!-- Card Nouvelle Cité -->
                @unless($editMode)
                @adminroute('admin.hebergement.parc-immobilier.create')
                <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'cite']) }}">
                <div class="card-select card-option success {{ $selectedType === 'cite' ? 'selected' : '' }}"
                     data-target="cite-form"
                     @if($editMode && $selectedType !== 'cite') style="display: none;" @endif>
                    <div class="icone-stats-card">
                        <i class="fas fa-city"></i>
                    </div>
                    <div class="card-select-content">
                        <div class="titre-card-select">
                            {{ $editMode && $selectedType === 'cite' ? 'Modifier Cité' : 'Nouvelle Cité' }}
                        </div>
                    </div>
                </div>
                </a>
                @endadminroute
                @endunless

                <!-- Card Nouveau Bâtiment -->
                @unless($editMode)
                @adminroute('admin.hebergement.parc-immobilier.create')
                <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'batiment']) }}">
                <div class="card-select card-option warning {{ $selectedType === 'batiment' ? 'selected' : '' }}"
                     data-target="batiment-form"
                     @if($editMode && $selectedType !== 'batiment') style="display: none;" @endif>
                    <div class="icone-stats-card">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="card-select-content">
                        <div class="titre-card-select">
                            {{ $editMode && $selectedType === 'batiment' ? 'Modifier Bâtiment' : 'Nouveau Bâtiment' }}
                        </div>
                    </div>
                </div>
                </a>
                @endadminroute
                @endunless

                <!-- Card Nouvelle Chambre -->
                @unless($editMode)
                @adminroute('admin.hebergement.parc-immobilier.create')
                <a href="{{ route('admin.hebergement.parc-immobilier.create', ['type' => 'chambre']) }}">
                <div class="card-select card-option info {{ $selectedType === 'chambre' ? 'selected' : '' }}"
                     data-target="chambre-form"
                     @if($editMode && $selectedType !== 'chambre') style="display: none;" @endif>
                    <div class="icone-stats-card">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="card-select-content">
                        <div class="titre-card-select">
                            {{ $editMode && $selectedType === 'chambre' ? 'Modifier Chambre' : 'Nouvelle Chambre' }}
                        </div>
                    </div>
                </div>
                </a>
                @endadminroute
                    @endunless
            </div>
        </div>

        @if(request()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="success-alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ request()->get('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="success-alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(request()->has('errors'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul class="mb-0">
{{--                    @foreach($errors->all() as $error)--}}
{{--                        <li>{{ $error }}</li>--}}
{{--                    @endforeach--}}
                    {{ session('errors') }}
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif


        <div class="d-flex flex-column flex-md-row mt-4">
            <div class="card-classic latest-update-card" style="flex: 5;">

                <!-- Formulaire Campus -->
                @if($selectedType==="campus")
                @includeIf('admin.hebergement.parc-immobilier.vue.form-campus')
                @endif

                <!-- Formulaire Cité -->
                @if($selectedType==="cite")
                @includeIf('admin.hebergement.parc-immobilier.vue.form-cite')
                @endif
                <!-- Formulaire Bâtiment -->
                @if($selectedType==="batiment")
                @includeIf('admin.hebergement.parc-immobilier.vue.form-batiment')
                @endif
                <!-- Formulaire Chambre - NOUVELLE VERSION -->
                @if($selectedType==="chambre")
                  @includeIf('admin.hebergement.parc-immobilier.vue.form-chambre')
                @endif





                <!-- Message par défaut (affiché uniquement si aucun type sélectionné) -->
                <div id="default-message" style="{{ $selectedType ? 'display: none;' : 'display: block;' }}">
                    <div class="text-center py-5">
                        <i class="fas fa-hand-pointer fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Sélectionnez une option ci-dessus</h4>
                        <p class="text-muted">Cliquez sur l'une des cartes pour afficher le formulaire correspondant</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('styles')

    <link rel="stylesheet" href="{{asset("files/assets/css/parc-immobilier.css")}}">

@endpush

@push('scripts')
    <script src="{{asset("files/assets/js/parc-immobilier.js")}}"></script>
@endpush