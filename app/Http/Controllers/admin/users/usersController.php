<?php

namespace App\Http\Controllers\admin\users;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Genre;
use App\Models\Role;
use App\Http\Services\UserService;
use App\Models\User;
use App\Services\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class usersController extends Controller
{
    protected  $userService;
    public function __construct(
         UserService $userService
    ) {
        $this->userService = $userService;

    }

    // ─────────────────────────────────────────────
    //  Liste
    // ─────────────────────────────────────────────
    public function index(Request $request, AdminAccess $access): Response
    {
        $actor = $request->user('admin');
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'statut' => in_array($request->query('statut'), ['active', 'inactive'], true) ? $request->query('statut') : '',
        ];
        // Sans « utilisateurs.personal », e-mail et téléphone ne sont pas envoyés au navigateur.
        $personal = $actor->hasPermission('utilisateurs.personal');
        $can = [
            'create' => $access->allows($actor, 'admin.users.create'),
            'view' => $access->allows($actor, 'admin.users.show'),
            'edit' => $access->allows($actor, 'admin.users.edit'),
            'status' => $access->allows($actor, 'admin.users.status') && $actor->hasPermission('utilisateurs.update'),
        ];
        $actorIsSuperAdmin = (int) $actor->role_id === 1;

        $users = $this->userService
            ->paginateUsers($filters['search'], $filters['statut'], 10)
            ->through(fn (User $user) => [
                'id' => $user->id_user,
                'name' => $user->name,
                'civilite' => $user->genre?->name,
                'matricule' => $user->matricule,
                'role' => $user->role?->name,
                'email' => $personal ? $user->email : null,
                'telephone' => $personal ? $user->telephone_principal : null,
                'statut' => $user->statut === 'active' ? 'active' : 'inactive',
                'urls' => [
                    'show' => $can['view'] ? route('admin.users.show', $user->id_user) : null,
                    'edit' => $can['edit'] ? route('admin.users.edit', $user->id_user) : null,
                    // Pas de bascule sur son propre compte ni (hors super admin) sur un super admin.
                    'status' => $can['status']
                        && (string) $user->id_user !== (string) $actor->id_user
                        && ((int) $user->role_id !== 1 || $actorIsSuperAdmin)
                        ? route('admin.users.status', $user->id_user) : null,
                ],
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $filters,
            'showPersonal' => $personal,
            'createUrl' => $can['create'] ? route('admin.users.create') : null,
        ]);
    }

    // ─────────────────────────────────────────────
    //  Formulaire création
    // ─────────────────────────────────────────────
    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Users/Create', array_merge($this->formData($request->user('admin')), [
            'storeUrl' => route('admin.users.store'),
            'indexUrl' => route('admin.users.index'),
        ]));
    }

    // ─────────────────────────────────────────────
    //  Enregistrement
    // ─────────────────────────────────────────────
    public function store(UserRequest $request): RedirectResponse
    {
        $this->userService->createUser(
            $request->validated()
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès !');
    }

    // ─────────────────────────────────────────────
    //  Détail
    // ─────────────────────────────────────────────
    public function show(Request $request, AdminAccess $access, string $id_user): Response
    {
        $actor = $request->user('admin');
        $user = $this->userService->getUserById($id_user);
        // Sans « utilisateurs.personal », civilité, e-mail et téléphone ne sont pas envoyés au navigateur.
        $personal = $actor->hasPermission('utilisateurs.personal');
        $canManage = (int) $user->role_id !== 1 || (int) $actor->role_id === 1;

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'name' => $user->name,
                'civilite' => $personal ? $user->genre?->name : null,
                'matricule' => $user->matricule,
                'role' => $user->role?->name,
                'email' => $personal ? $user->email : null,
                'telephone' => $personal ? $user->telephone_principal : null,
                'statut' => $user->statut === 'active' ? 'active' : 'inactive',
                'createdAt' => $user->created_at?->toIso8601String(),
                'updatedAt' => $user->updated_at?->toIso8601String(),
            ],
            'showPersonal' => $personal,
            'urls' => [
                'index' => $access->allows($actor, 'admin.users.index') ? route('admin.users.index') : null,
                'edit' => $canManage && $access->allows($actor, 'admin.users.edit') ? route('admin.users.edit', $user->id_user) : null,
                // Pas de bascule sur son propre compte ni (hors super admin) sur un super admin.
                'status' => $canManage
                    && (string) $user->id_user !== (string) $actor->id_user
                    && $access->allows($actor, 'admin.users.status')
                    && $actor->hasPermission('utilisateurs.update')
                    ? route('admin.users.status', $user->id_user) : null,
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    //  Formulaire édition
    // ─────────────────────────────────────────────
    public function edit(Request $request, AdminAccess $access, string $id_user): Response
    {
        $actor = $request->user('admin');
        $user = $this->userService->getUserById($id_user);
        // Seul un super administrateur peut modifier un compte super administrateur (cf. UserRequest).
        abort_if((int) $user->role_id === 1 && (int) $actor->role_id !== 1, 403);

        return Inertia::render('Admin/Users/Edit', array_merge($this->formData($actor), [
            'user' => [
                'id' => (string) $user->id_user,
                'genre_id' => $user->genre_id !== null ? (string) $user->genre_id : '',
                'name' => (string) $user->name,
                'telephone_principal' => (string) $user->telephone_principal,
                'email' => (string) $user->email,
                'role_id' => $user->role_id !== null ? (string) $user->role_id : '',
            ],
            'updateUrl' => route('admin.users.update', $user->id_user),
            'cancelUrl' => $access->allows($actor, 'admin.users.show')
                ? route('admin.users.show', $user->id_user)
                : route('admin.users.index'),
        ]));
    }

    // ─────────────────────────────────────────────
    //  Mise à jour
    // ─────────────────────────────────────────────
    public function update(UserRequest $request, string $id_user): RedirectResponse
    {
        $this->userService->updateUser(
            $id_user,
            $request->validated()
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur modifié avec succès !');
    }

    // ─────────────────────────────────────────────
    //  Suppression
    // ─────────────────────────────────────────────
    public function destroy(string $id_user): RedirectResponse
    {
        $target = \App\Models\User::findOrFail($id_user);
        abort_if((int) $target->role_id === 1 && (int) auth('admin')->user()->role_id !== 1, 403);
        $this->userService->deleteUser($id_user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé avec succès !');
    }

    // ─────────────────────────────────────────────
    //  Données communes aux formulaires
    // ─────────────────────────────────────────────
    public function status(\Illuminate\Http\Request $request, string $id_user): RedirectResponse
    {
        $actor = $request->user('admin');
        abort_unless($actor && $actor->hasPermission('utilisateurs.update'), 403);
        $target = \App\Models\User::findOrFail($id_user);
        abort_if((int) $target->role_id === 1 && (int) $actor->role_id !== 1, 403);
        $data = $request->validate(['statut' => ['required', 'in:active,inactive']]);
        if ($data['statut'] === 'inactive' && (string) $actor->id_user === (string) $target->id_user) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }
        $target->update(['statut' => $data['statut'], 'updated_by' => $actor->id_user]);
        return back()->with('success', $data['statut'] === 'active' ? 'Utilisateur activé.' : 'Utilisateur désactivé.');
    }
    /** Données communes aux formulaires de création et d'édition (Inertia). */
    private function formData(User $actor): array
    {
        $actorIsSuperAdmin = (int) $actor->role_id === 1;

        return [
            'genres' => Genre::orderBy('name')->get(['id', 'name'])
                ->map(fn (Genre $genre) => ['id' => (string) $genre->id, 'name' => $genre->name]),
            // Le rôle super administrateur (id 1) n'est proposé qu'à un super administrateur.
            'roles' => Role::orderBy('name')->get(['id', 'name'])
                ->reject(fn (Role $role) => (int) $role->id === 1 && !$actorIsSuperAdmin)
                ->map(fn (Role $role) => ['id' => (string) $role->id, 'name' => $role->name])
                ->values(),
            // Sans ce droit, UserRequest refuse tout changement de rôle (et donc toute création).
            'canAssignRole' => $actor->hasPermission('parametrage.roles.assign'),
            'telephonePrefixes' => UserRequest::PREFIXES_TELEPHONE_CI,
            'passwordPolicy' => [
                'min' => (int) systemConfig('password_min_length', 8),
                'mixedCase' => (bool) systemConfig('password_require_uppercase', true),
                'numbers' => (bool) systemConfig('password_require_number', true),
                'symbols' => (bool) systemConfig('password_require_special', false),
            ],
        ];
    }
}









//namespace App\Http\Controllers\admin\users;
//
//use App\Http\Controllers\Controller;
//use Illuminate\Http\Request;
//use App\Models\User;
//use App\Models\Genre;
//use App\Models\Region;
//use App\Models\TypeUtilisateur;
//use App\Models\Role;
//use App\Models\Ville;
//use Illuminate\Support\Facades\Hash;
//use Illuminate\Support\Str; // Ajouter cette ligne
//use Illuminate\Support\Facades\Storage; // Ajouter cette ligne
//
//class usersController extends Controller
//{
//    public function index()
//    {
//        $user = User::with('type_utilisateur')->get();
//        //dd($user);
//        return view('admin.users.index',compact('user'));
//    }
//
//    public function create(){
//
//        $genre = Genre::all();
//        $region = Region::all();
//        $typeUtilisateur = TypeUtilisateur::all();
//        $role =  Role::all();
//
//        return view('admin.users.create',compact('genre','region','typeUtilisateur','role'));
//    }
//
//    public function edit($id_user){
//        $genre = Genre::all();
//        $region = Region::all();
//        $typeUtilisateur = TypeUtilisateur::all();
//        $user = User::where('id_user', $id_user)->firstOrFail();
//        $ville = Ville::where('region_id', $user->region_id)->get();
//        $role =  Role::all();
//
//        return view('admin.users.edit',compact('genre','region','typeUtilisateur','role','user','ville'));
//    }
//
//    public function store(Request $request)
//    {
//      //  dd('$validated');
//
//        try {
//            $validated = $request->validate([
//                'name' => 'required',
//                'genre_id' => 'required',
//                'matricule' => 'sometimes',
//                'telephone_principal' => 'required',
//                'telephone_secondaire' => 'sometimes',
//                'email' => 'required|email|unique:users,email',
//                'email_secondaire' => 'sometimes',
//                'region_id' => 'sometimes',
//                'ville_id' => 'sometimes',
//                'adresse' => 'required',
//                'nationalite' => 'required',
//                'dateNaiss' => 'sometimes',
//                'lieuNaiss' => 'sometimes',
//                'numcni' => 'required',
//                'datecni' => 'sometimes',
//                'type_utilisateur_id' => 'required',
//                'role_id' => 'required',
//                'password' => 'required|min:6|confirmed',
//                'photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
//            ]);
//
//        //    dd($validated);
//
//            // Gestion photo
//            $photoPath = null;
//            if ($request->hasFile('photo')) {
//                $photoPath = $request->file('photo')->store('users', 'public');
//            }
//
//            User::create([
//                'name' => $validated['name'],
//                'genre_id' => $validated['genre_id'],
//                'matricule' => $validated['matricule'] ?? null,
//                'telephone_principal' => $validated['telephone_principal'],
//                'telephone_secondaire' => $validated['telephone_secondaire'] ?? null,
//                'email' => $validated['email'],
//                'email_secondaire' => $validated['email_secondaire'] ?? null,
//                'region_id' => $validated['region_id'] ?? null,
//                'ville_id' => $validated['ville_id'] ?? null,
//                'adresse' => $validated['adresse'],
//                'nationalite' => $validated['nationalite'],
//                'date_naissance' => $validated['dateNaiss'] ?? null,
//                'lieu_naissance' => $validated['lieuNaiss'] ?? null,
//                'cin' => $validated['numcni'],
//                'date_delivrance_cin' => $validated['datecni'] ?? null,
//                'type_utilisateur_id' => $validated['type_utilisateur_id'],
//                'role_id' => $validated['role_id'],
//                'password' => Hash::make($validated['password']),
//                'photo' => $photoPath,
//                'date_inscription' => now(),
//                'statut' => 'active',
//                'created_by' => getInfoAdmin()->users->code,
//                'remember_token' => Str::random(60),
//            ]);
//
//            return redirect()->route('admin.users.index')->with('success', 'Utilisateur créé avec succès !');
//        }catch (\Exception $exception){
//            dd($exception->getMessage());
//        }
//
//
//    }
//
//    // 🔹 Mise à jour utilisateur
//    public function update(Request $request, $id_user)
//    {
//        $user = User::where('id_user', $id_user)->firstOrFail();
//        try {
//        $validated = $request->validate([
//            'name' => 'required',
//            'genre_id' => 'required',
//            'matricule' => 'sometimes',
//            'telephone_principal' => 'required',
//            'telephone_secondaire' => 'nullable',
//            'email' => 'required|email|unique:users,email,' . $user->id_user . ',id_user',
//            'email_secondaire' => 'nullable|email',
//            'region_id' => 'sometimes',
//            'ville_id' => 'sometimes',
//            'adresse' => 'required',
//            'nationalite' => 'required',
//            'dateNaiss' => 'nullable|date',
//            'lieuNaiss' => 'sometimes',
//            'numcni' => 'required',
//            'datecni' => 'nullable|date',
//            'type_utilisateur_id' => 'required',
//            'role_id' => 'required',
//            'photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
//        ]);
//
//
//
//        // Gestion photo
//        if ($request->hasFile('photo')) {
//            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
//                Storage::disk('public')->delete($user->photo);
//            }
//            $user->photo = $request->file('photo')->store('users', 'public');
//        }
//
//        $user->update([
//            'name' => $validated['name'],
//            'genre_id' => $validated['genre_id'],
//            'matricule' => $validated['matricule'] ?? null,
//            'telephone_principal' => $validated['telephone_principal'],
//            'telephone_secondaire' => $validated['telephone_secondaire'] ?? null,
//            'email' => $validated['email'],
//            'email_secondaire' => $validated['email_secondaire'] ?? null,
//            'region_id' => $validated['region_id'] ?? null,
//            'ville_id' => $validated['ville_id'] ?? null,
//            'adresse' => $validated['adresse'],
//            'nationalite' => $validated['nationalite'],
//            'date_naissance' => $validated['dateNaiss'] ?? null,
//            'lieu_naissance' => $validated['lieuNaiss'] ?? null,
//            'cin' => $validated['numcni'],
//            'date_delivrance_cin' => $validated['datecni'] ?? null,
//            'type_utilisateur_id' => $validated['type_utilisateur_id'],
//            'role_id' => $validated['role_id'],
//            'updated_by' => auth()->id(),
//            'photo' => $user->photo,
//        ]);
//
//        return redirect()->route('admin.users.index')->with('success', 'Utilisateur modifié avec succès !');
//        }catch (\Exception $exception){
//            dd($exception->getMessage());
//        }
//    }
//
//    // 🔹 Suppression utilisateur
//    public function destroy($id_user)
//    {
//        $user = User::where('id_user', $id_user)->firstOrFail();
//
//        // Supprimer la photo si elle existe
//        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
//            Storage::disk('public')->delete($user->photo);
//        }
//
//        $user->delete();
//
//        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé avec succès !');
//    }
//}
