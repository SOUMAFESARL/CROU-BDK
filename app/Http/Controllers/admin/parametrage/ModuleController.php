<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Models\ApplicationModule;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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

    /** Page React `Admin/Parametrage/Modules` : liste à plat, l'arborescence est reconstruite côté client. */
    public function index(Request $request, AdminAccess $access): Response
    {
        $actor = $request->user('admin');
        $can = [
            'update' => $access->allows($actor, 'admin.parametrage.modules.update'),
            'toggle' => $access->allows($actor, 'admin.parametrage.modules.toggle'),
            'destroy' => $access->allows($actor, 'admin.parametrage.modules.destroy'),
        ];

        $modules = ApplicationModule::withCount(['children', 'permissions', 'permissions as permissions_attribuees_count' => fn ($q) => $q->whereHas('roles', self::horsSuperAdmin(...))])
            ->orderBy('level')->orderBy('position')->orderBy('name')->get()
            ->map(fn (ApplicationModule $module) => [
                'id' => $module->id,
                'parent_id' => $module->parent_id,
                'name' => $module->name,
                'slug' => $module->slug,
                'description' => $module->description,
                'icon' => $module->icon,
                'route_name' => $module->route_name,
                'menu_key' => $module->menu_key,
                'submenu_key' => $module->submenu_key,
                'position' => (int) $module->position,
                'actions' => array_values($module->actions ?? []),
                'is_active' => (bool) $module->is_active,
                'show_in_menu' => (bool) $module->show_in_menu,
                'is_system' => (bool) $module->is_system,
                'enfants' => (int) $module->children_count,
                'permissions' => (int) $module->permissions_count,
                'permissions_attribuees' => (int) $module->permissions_attribuees_count,
                'urls' => [
                    'update' => $can['update'] ? route('admin.parametrage.modules.update', $module) : null,
                    'toggle' => $can['toggle'] ? route('admin.parametrage.modules.toggle', $module) : null,
                    'destroy' => $can['destroy'] && !$module->is_system ? route('admin.parametrage.modules.destroy', $module) : null,
                ],
            ]);

        return Inertia::render('Admin/Parametrage/Modules', [
            'modules' => $modules,
            'actions' => self::ACTIONS,
            'stats' => [
                'total' => $modules->count(),
                'actifs' => $modules->where('is_active', true)->count(),
                'inactifs' => $modules->where('is_active', false)->count(),
                'permissions' => $modules->sum('permissions'),
            ],
            'urls' => [
                'store' => $access->allows($actor, 'admin.parametrage.modules.store') ? route('admin.parametrage.modules.store') : null,
                'roles' => $access->allows($actor, 'admin.parametrage.roles-permissions.index') ? route('admin.parametrage.roles-permissions.index') : null,
            ],
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
        // Les permissions des modules système sont référencées par config/admin_permissions.php.
        if ($module->is_system && $data['slug'] !== $module->slug) {
            return back()->withErrors(['slug'=>'L’identifiant technique d’un module système ne peut pas être modifié.']);
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
        if ($module->permissions()->whereHas('roles', self::horsSuperAdmin(...))->exists()) return back()->withErrors(['module'=>'Ce module possède des permissions attribuées à des rôles.']);
        DB::transaction(function () use ($module) {
            $module->permissions->each(fn ($permission) => $permission->roles()->detach());
            $module->permissions()->delete();
            $module->delete();
        });
        return back()->with('success', 'Module supprimé.');
    }

    /** Le super-administrateur reçoit d'office toutes les permissions (syncPermissions) : il ne bloque pas la suppression. */
    private static function horsSuperAdmin($query): void
    {
        $query->where('roles.id', '!=', 1);
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
