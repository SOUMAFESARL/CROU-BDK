@extends('admin.layouts.app', ['activeMenu'=>'hebergement','activeSubMenu'=>'resident-contrat'])
@section('title','Notifications, contrat et entrée')
@section('content')
<div class="card"><div class="card-body">
<h1>Notifications, contrat et entrée dans les lieux</h1>
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<p>L’annonce de campagne, la proposition, le contrat et le rendez-vous sont traités automatiquement en arrière-plan. Les rendez-vous sont créés après approbation du contrat par l’étudiant.</p>
@adminroute('admin.housing-entry.update')
<form method="POST" action="{{ route('admin.housing-entry.update') }}">
@csrf
<h2>Notifications et propositions</h2>
<input type="hidden" name="enabled" value="0">
<p><label><input type="checkbox" name="enabled" value="1" @checked(old('enabled',$options['enabled'] ?? false))> Activer le parcours automatique pour les nouvelles attributions et l’annonce des campagnes actives</label></p>
<p><label>Délai de réponse à la proposition (heures)
<input class="form-control" type="number" name="proposal_hours" min="1" max="720" value="{{ old('proposal_hours',$options['proposal_hours'] ?? 72) }}" required></label></p>
<h2>Créneaux d’entrée</h2>
<input type="hidden" name="appointments_enabled" value="0">
<p><label><input type="checkbox" name="appointments_enabled" value="1" @checked(old('appointments_enabled',$options['appointments_enabled'] ?? false))> Programmer automatiquement les rendez-vous</label></p>
<p><label>À partir du <input class="form-control" type="date" name="start_date" value="{{ old('start_date',$options['start_date'] ?? '') }}"></label></p>
<fieldset><legend>Jours d’accueil</legend>
@foreach([1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi',7=>'Dimanche'] as $day=>$label)
<label class="mr-3"><input type="checkbox" name="weekdays[]" value="{{ $day }}" @checked(in_array($day,old('weekdays',$options['weekdays'] ?? [])))> {{ $label }}</label>
@endforeach
</fieldset>
<p><label>Ouverture <input class="form-control" type="time" name="opening_time" value="{{ old('opening_time',$options['opening_time'] ?? '') }}"></label></p>
<p><label>Fermeture <input class="form-control" type="time" name="closing_time" value="{{ old('closing_time',$options['closing_time'] ?? '') }}"></label></p>
<p><label>Durée du créneau (minutes) <input class="form-control" type="number" name="slot_minutes" min="5" max="240" value="{{ old('slot_minutes',$options['slot_minutes'] ?? 30) }}" required></label></p>
<p><label>Étudiants par créneau <input class="form-control" type="number" name="capacity" min="1" max="100" value="{{ old('capacity',$options['capacity'] ?? 1) }}" required></label></p>
<p><label>Délai minimal avant rendez-vous (heures) <input class="form-control" type="number" name="lead_hours" min="0" max="720" value="{{ old('lead_hours',$options['lead_hours'] ?? 24) }}" required></label></p>
<p><label>Lieu d’accueil <input class="form-control" name="location" maxlength="255" value="{{ old('location',$options['location'] ?? '') }}"></label></p>
<p>Fuseau horaire : {{ config('app.timezone') }}. Une modification des créneaux ne déplace pas les rendez-vous déjà annoncés.</p>
<h2>Conditions du contrat</h2>
<p>Les valeurs du modèle actuel sont reprises ci-dessous. Le préavis est celui du paramétrage des départs. Les modifications s’appliquent aux contrats non approuvés ; les contrats approuvés restent conservés dans leur version acceptée.</p>
@foreach($terms as $key=>$value)
<p><label>{{ $key === 'delai_restituion' ? 'Délai de restitution (jours)' : ucfirst(str_replace('_',' ',$key)) }}
<textarea class="form-control" name="contract[{{ $key }}]" rows="2" maxlength="3000" required>{{ old('contract.'.$key,$value) }}</textarea></label></p>
@endforeach
<button type="submit" class="btn btn-primary">Enregistrer les paramètres</button>
</form>
@endadminroute
<h2 class="mt-4">Suivi des envois</h2>
<p>{{ $failed }} envoi(s) en échec. Les notifications restent consultables dans l’espace étudiant.</p>
@if($failed)@adminroute('admin.housing-entry.retry')
<form method="POST" action="{{ route('admin.housing-entry.retry') }}">@csrf<button class="btn btn-secondary">Reprendre les envois en échec</button></form>
@endadminroute
@endif
<h2 class="mt-4">Rendez-vous programmés</h2>
<table class="table"><thead><tr><th>Étudiant</th><th>Date</th><th>Lieu</th><th>Statut</th></tr></thead><tbody>
@forelse($appointments as $appointment)
<tr><td>{{ $appointment->student?->name }}</td><td>{{ $appointment->starts_at->format('d/m/Y H:i') }}</td><td>{{ $appointment->location }}</td><td>{{ $appointment->status === 'scheduled' ? 'Programmé' : 'Annulé' }}</td></tr>
@empty<tr><td colspan="4">Aucun rendez-vous programmé.</td></tr>@endforelse
</tbody></table>
{{ $appointments->links() }}
</div></div>
@endsection
