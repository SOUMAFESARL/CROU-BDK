<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Models\ApplicationModule;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ModuleController extends Controller
{
    public const ACTIONS = [
        'view'=>'Consulter','create'=>'Créer','update'=>'Modifier','delete'=>'Supprimer',
        'validate'=>'Valider','assign'=>'Affecter','export'=>'Exporter','configure'=>'Configurer',
        'transmit'=>'Transmettre','close'=>'Clôturer',
        'personal'=>'Consulter les informations personnelles',
        'documents'=>'Consulter les pièces justificatives',
        'history'=>'Consulter l’historique',
    ];

    public function index()
    {
        return view('admin.parametrage.modules.index', [
            'modules' => ApplicationModule::with(['children.children','permissions'])->whereNull('parent_id')->orderBy('position')->get(),
            'allModules' => ApplicationModule::with('parent')->orderBy('level')->orderBy('position')->get(),
            'availableActions' => self::ACTIONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $module = ApplicationModule::create($this->payload($data));
            $this->syncPermissions($module);
        });
        return back()->with('success', 'Module créé et permissions générées.');
    }

    public function update(Request $request, ApplicationModule $module)
    {
        $data = $this->validated($request, $module);
        if (($data['parent_id'] ?? null) == $module->id || $this->descendantIds($module)->contains((int) ($data['parent_id'] ?? 0))) {
            return back()->withErrors(['parent_id'=>'Un module ne peut pas dépendre de lui-même ou de l’un de ses enfants.']);
        }
        DB::transaction(function () use ($module, $data) {
            $module->update($this->payload($data));
            $this->syncPermissions($module->fresh());
        });
        return back()->with('success', 'Module mis à jour.');
    }

    public function toggle(ApplicationModule $module)
    {
        $module->update(['is_active'=>!$module->is_active]);
        return back()->with('success', $module->is_active ? 'Module activé.' : 'Module désactivé.');
    }

    public function destroy(ApplicationModule $module)
    {
        if ($module->is_system) return back()->withErrors(['module'=>'Un module système ne peut pas être supprimé.']);
        if ($module->children()->exists()) return back()->withErrors(['module'=>'Déplacez ou supprimez d’abord les sous-modules.']);
        if ($module->permissions()->whereHas('roles')->exists()) return back()->withErrors(['module'=>'Ce module possède des permissions attribuées à des rôles.']);
        DB::transaction(function () use ($module) { $module->permissions()->delete(); $module->delete(); });
        return back()->with('success', 'Module supprimé.');
    }

    private function validated(Request $request, ?ApplicationModule $module = null): array
    {
        return $request->validate([
            'parent_id'=>'nullable|exists:modules,id','name'=>'required|string|max:120',
            'slug'=>['required','string','max:160','regex:/^[a-z0-9.-]+$/',Rule::unique('modules','slug')->ignore($module?->id)],
            'description'=>'nullable|string|max:1000','icon'=>'nullable|string|max:100','route_name'=>'nullable|string|max:180',
            'menu_key'=>'nullable|string|max:80','submenu_key'=>'nullable|string|max:80','position'=>'required|integer|min:0|max:9999',
            'actions'=>'array','actions.*'=>Rule::in(array_keys(self::ACTIONS)),
            'is_active'=>'nullable|boolean','show_in_menu'=>'nullable|boolean',
        ]);
    }

    private function payload(array $data): array
    {
        $parent = !empty($data['parent_id']) ? ApplicationModule::find($data['parent_id']) : null;
        return collect($data)->except(['is_active','show_in_menu'])->merge([
            'parent_id'=>$parent?->id,'level'=>$parent ? $parent->level + 1 : 0,'actions'=>$data['actions'] ?? [],
            'is_active'=>(bool)($data['is_active'] ?? false),'show_in_menu'=>(bool)($data['show_in_menu'] ?? false),
        ])->all();
    }

    private function syncPermissions(ApplicationModule $module): void
    {
        $desiredSlugs = collect($module->actions ?? [])->map(fn ($action) => $module->slug.'.'.$action);
        foreach ($module->actions ?? [] as $action) {
            $permission = Permission::updateOrCreate(['slug'=>$module->slug.'.'.$action], [
                'module_id'=>$module->id,'module'=>$module->name,'action'=>$action,
                'name'=>(self::ACTIONS[$action] ?? ucfirst($action)).' — '.$module->name,
                'description'=>'Autorise l’action « '.(self::ACTIONS[$action] ?? $action).' » dans le module '.$module->name.'.',
            ]);
            Role::find(1)?->permissions()->syncWithoutDetaching([$permission->id]);
        }
        $obsolete = $module->permissions()->whereNotIn('slug', $desiredSlugs)->get();
        foreach ($obsolete as $permission) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }

    private function descendantIds(ApplicationModule $module)
    {
        $ids = collect();
        $walk = function ($node) use (&$walk, $ids) { foreach ($node->children as $child) { $ids->push($child->id); $walk($child); } };
        $walk($module->load('children.children'));
        return $ids;
    }
}
