<div class="module-form-grid">
    <label><span>Module parent</span><select name="parent_id"><option value="">Module principal</option>@foreach($allModules as $candidate)@if(!isset($module) || $candidate->id !== $module->id)<option value="{{ $candidate->id }}" @selected(old('parent_id',$module->parent_id ?? '')==$candidate->id)>{{ str_repeat('— ', $candidate->level) }}{{ $candidate->name }}</option>@endif @endforeach</select></label>
    <label><span>Nom *</span><input name="name" value="{{ old('name',$module->name ?? '') }}" required></label>
    <label><span>Identifiant technique *</span><input name="slug" value="{{ old('slug',$module->slug ?? '') }}" placeholder="hebergement.sanctions" required></label>
    <label><span>Icône Font Awesome</span><input name="icon" value="{{ old('icon',$module->icon ?? '') }}" placeholder="fa-solid fa-shield"></label>
    <label><span>Nom de la route</span><input name="route_name" value="{{ old('route_name',$module->route_name ?? '') }}" placeholder="admin.hebergement.index"></label>
    <label><span>Position *</span><input type="number" name="position" min="0" value="{{ old('position',$module->position ?? 0) }}" required></label>
    <label><span>Clé du menu</span><input name="menu_key" value="{{ old('menu_key',$module->menu_key ?? '') }}" placeholder="hebergement"></label>
    <label><span>Clé du sous-menu</span><input name="submenu_key" value="{{ old('submenu_key',$module->submenu_key ?? '') }}" placeholder="vie-cite"></label>
    <label class="module-full"><span>Description</span><textarea name="description" rows="2">{{ old('description',$module->description ?? '') }}</textarea></label>
</div>
<fieldset class="action-fieldset"><legend>Actions et permissions à générer</legend><div class="action-grid">@foreach($availableActions as $key=>$label)<label><input type="checkbox" name="actions[]" value="{{ $key }}" @checked(in_array($key,old('actions',$module->actions ?? ['view'])))><span>{{ $label }}</span><small>.{{ $key }}</small></label>@endforeach</div></fieldset>
<div class="switch-row"><label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$module->is_active ?? true))><span>Module actif</span></label><label><input type="hidden" name="show_in_menu" value="0"><input type="checkbox" name="show_in_menu" value="1" @checked(old('show_in_menu',$module->show_in_menu ?? true))><span>Afficher dans le menu</span></label></div>
