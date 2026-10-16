<?php

namespace App\Http\Middleware;

use App\Services\AdminMenu;
use App\Services\EtudiantMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'admin.inertia';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $espaceEtudiant = $request->routeIs('etudiants.*');

        return [
            ...parent::share($request),
            'auth' => fn () => $espaceEtudiant ? $this->etudiant($request) : $this->admin(),
            'menu' => fn () => $espaceEtudiant ? [] : app(AdminMenu::class)->forUser(Auth::guard('admin')->user(), $request),
            // Espace étudiant : menu en groupes (Principal / Gestion), commun avec <x-etudiant-shell>.
            'menuEtudiant' => fn () => $espaceEtudiant ? app(EtudiantMenu::class)->groupes($request) : [],
            // Jeton à jour pour les formulaires POST classiques (déconnexion) : la session peut être régénérée sans rechargement.
            'csrfToken' => fn () => csrf_token(),
            // Cookie écrit par la barre latérale (React et Blade) : 'false' = repliée.
            'sidebarOpen' => $request->cookie('sidebar_state') !== 'false',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    private function admin(): array
    {
        $user = Auth::guard('admin')->user();

        return [
            'user' => $user ? [
                'name' => $user->nom_affichage,
                'email' => $user->email,
                'role' => $user->role?->name,
                'avatar' => $user->photo ?: asset('files/assets/images/avatar-4.jpg'),
            ] : null,
            'profileUrl' => route('admin.profile.show'),
            'logoutUrl' => route('admin.logout'),
            'links' => [],
        ];
    }

    private function etudiant(Request $request): array
    {
        $student = Auth::guard('etudiant')->user();

        return [
            'user' => $student ? [
                'name' => $student->name ?: 'Étudiant',
                'email' => $student->email1,
                // Sous le nom, à la place du rôle de l'admin.
                'role' => $student->matricule,
                'avatar' => null,
            ] : null,
            'profileUrl' => route('etudiants.profile'),
            'logoutUrl' => route('etudiants.logout'),
            'links' => [
                ['label' => 'Mes documents', 'icon' => 'folder-open', 'href' => route('etudiants.documents.personal')],
            ],
        ];
    }
}
