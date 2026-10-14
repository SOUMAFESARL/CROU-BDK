<?php

namespace App\Http\Controllers\admin\parametrage;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\ApplicationModule;
use App\Services\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RolePermissionController extends Controller
{
    /** Libellés des grands modules et de quelques sous-modules (identiques à l'ancienne vue Blade). */
    private const MODULE_LABELS = [
        'dashboard' => 'Tableau de bord',
        'utilisateurs' => 'Utilisateurs',
        'hebergement' => 'Hébergement',
        'parametrage' => 'Paramètres',
        'activity' => 'Journal d’activité',
        'parametrage.legal-documents' => 'Documents de la résidence',
        'parametrage.payment-providers' => 'API de paiement',
    ];

    /** Permission technique masquée dans l'éditeur et le référentiel. */
    private const HIDDEN_PERMISSIONS = ['parametrage.legal-documents.signatures'];

    public function index(Request $request, AdminAccess $access): Response
    {
        $this->synchronizeSuperAdmin();
        $actor = $request->user('admin');
        $moduleTree = ApplicationModule::with(['permissions'=>fn($q)=>$q->orderBy('action'),'children'=>fn($q)=>$q->where('is_active',true)->with(['permissions'=>fn($p)=>$p->orderBy('action'),'activeDescendants'])])
            ->whereNull('parent_id')->where('is_active',true)->orderByRaw("CASE slug WHEN 'dashboard' THEN 1 WHEN 'utilisateurs' THEN 2 WHEN 'hebergement' THEN 3 WHEN 'parametrage' THEN 4 WHEN 'activity' THEN 5 ELSE 6 END")->orderBy('position')->get();

        $can = [
            'create' => $access->allows($actor, 'admin.parametrage.roles-permissions.roles.store'),
            'update' => $access->allows($actor, 'admin.parametrage.roles-permissions.roles.update') && $actor->hasPermission('parametrage.roles.update'),
            'delete' => $access->allows($actor, 'admin.parametrage.roles-permissions.roles.destroy'),
            'assignments' => app(\App\Services\AdminViewAccess::class)->allows($actor, 'roles.assignments'),
            'assign' => $access->allows($actor, 'admin.parametrage.roles-permissions.users.assign-role'),
        ];

        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();
        $search = trim((string) $request->query('search', ''));
        $statut = in_array($request->query('statut'), ['active', 'inactive'], true) ? $request->query('statut') : '';

        // Affectations : 10 utilisateurs par page, recherche sur le nom, l'e-mail et le matricule, filtre par statut du compte.
        $users = $can['assignments']
            ? User::with(['role', 'campus'])
                ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%")))
                ->when($statut !== '', fn ($query) => $query->where('statut', $statut))
                ->orderBy('name')
                ->paginate(10, ['id_user', 'name', 'matricule', 'role_id', 'campus_id', 'statut'])
                ->withQueryString()
                // Les liens de pagination rouvrent l'onglet « Affectations ».
                ->appends('onglet', 'affectations')
                ->through(fn (User $user) => [
                    'id' => (string) $user->id_user,
                    'name' => $user->name,
                    'matricule' => $user->matricule,
                    'campus' => $user->campus?->name,
                    'role_id' => $user->role_id ? (string) $user->role_id : null,
                    'role' => $user->role?->name,
                    'statut' => $user->statut === 'active' ? 'active' : 'inactive',
                    'assignUrl' => $can['assign'] ? route('admin.parametrage.roles-permissions.users.assign-role', $user->id_user) : null,
                ])
            : null;

        return Inertia::render('Admin/Parametrage/RolesPermissions/Index', [
            'roles' => $roles->map(fn (Role $role) => [
                'id' => (string) $role->id,
                'name' => $role->name,
                'usersCount' => (int) $role->users_count,
                'isSuperAdmin' => (int) $role->id === 1,
                // Le rôle super-administrateur n'est modifiable que par un super-administrateur (cf. updateRole).
                'editable' => $can['update'] && ((int) $role->id !== 1 || (int) $actor->role_id === 1),
                'permissionIds' => $role->permissions->pluck('id')->map(fn ($id) => (int) $id)->values(),
                'updateUrl' => route('admin.parametrage.roles-permissions.roles.update', $role),
                'destroyUrl' => $can['delete'] ? route('admin.parametrage.roles-permissions.roles.destroy', $role) : null,
            ])->values(),
            'moduleTree' => $moduleTree->map(fn (ApplicationModule $root) => $this->serializeNode($root, 0))->values(),
            'permissionsCount' => Permission::whereNotNull('module_id')->count(),
            'users' => $users,
            'usersTotal' => $can['assignments'] ? User::count() : 0,
            'filters' => ['search' => $search, 'statut' => $statut],
            'can' => $can,
            'storeUrl' => $can['create'] ? route('admin.parametrage.roles-permissions.roles.store') : null,
            'modulesUrl' => $access->allows($actor, 'admin.parametrage.modules.index') ? route('admin.parametrage.modules.index') : null,
            'parametrageUrl' => route('admin.parametrage.index'),
        ]);
    }

    /** Nœud de l'arbre des modules : au premier niveau les enfants actifs, ensuite les descendants actifs (comme l'ancienne vue). */
    private function serializeNode(ApplicationModule $node, int $depth): array
    {
        $children = $depth === 0 ? $node->children : $node->activeDescendants;

        return [
            'id' => (int) $node->id,
            'slug' => $node->slug,
            'name' => self::MODULE_LABELS[$node->slug] ?? $node->name,
            'permissions' => $node->permissions
                ->reject(fn (Permission $permission) => in_array($permission->slug, self::HIDDEN_PERMISSIONS, true))
                ->map(fn (Permission $permission) => [
                    'id' => (int) $permission->id,
                    'action' => $permission->action,
                    'label' => ModuleController::ACTIONS[$permission->action] ?? $permission->name,
                    'description' => $permission->description,
                ])->values(),
            'children' => $children->map(fn (ApplicationModule $child) => $this->serializeNode($child, $depth + 1))->values(),
        ];
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate(['name'=>['required','string','max:225',Rule::unique('roles','name')], 'permissions'=>'array', 'permissions.*'=>'exists:permissions,id']);
        $data['permissions'] = $this->validDynamicPermissions($data['permissions'] ?? []);
        DB::transaction(function () use ($data) {
            $role = Role::create(['name'=>$data['name']]);
            $role->permissions()->sync($data['permissions'] ?? []);
        });
        return back()->with('success', 'Rôle créé avec ses permissions.');
    }

    public function updateRole(Request $request, Role $role)
    {
        abort_if((int) $role->id === 1 && (int) $request->user('admin')->role_id !== 1, 403);
        $data = $request->validate(['name'=>['required','string','max:225',Rule::unique('roles','name')->ignore($role->id)], 'permissions'=>'array', 'permissions.*'=>'exists:permissions,id']);
        $data['permissions'] = (int) $role->id === 1
            ? Permission::whereNotNull('module_id')->pluck('id')->all()
            : $this->validDynamicPermissions($data['permissions'] ?? []);
        DB::transaction(function () use ($role, $data) {
            $role->update(['name'=>$data['name']]);
            $role->permissions()->sync($data['permissions'] ?? []);
        });
        return back()->with('success', 'Rôle et permissions mis à jour.');
    }

    public function destroyRole(Role $role)
    {
        if ((int) $role->id === 1) return back()->withErrors(['role'=>'Le rôle super-administrateur ne peut pas être supprimé.']);
        if ($role->users()->exists()) return back()->withErrors(['role'=>'Ce rôle est encore affecté à des utilisateurs.']);
        DB::transaction(function () use ($role) { $role->permissions()->detach(); $role->delete(); });
        return back()->with('success', 'Rôle supprimé.');
    }

    public function assignRole(Request $request, User $user)
    {
        $data = $request->validate(['role_id'=>'required|exists:roles,id']);
        abort_if(((int) $data['role_id'] === 1 || (int) $user->role_id === 1)
            && (int) $request->user('admin')->role_id !== 1, 403);
        $user->update(['role_id'=>$data['role_id']]);
        return back()->with('success', 'Rôle affecté à '.$user->name.'.');
    }

    private function validDynamicPermissions(array $ids): array
    {
        return Permission::whereNotNull('module_id')->whereIn('id', $ids)->pluck('id')->all();
    }

    private function synchronizeSuperAdmin(): void
    {
        $superAdmin = Role::find(1);
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching(Permission::whereNotNull('module_id')->pluck('id'));
        }
    }
}
