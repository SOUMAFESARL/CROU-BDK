<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Construit le menu admin (config/admin_menu.php) filtré selon les droits
 * de l'utilisateur, prêt à être partagé avec Inertia.
 */
class AdminMenu
{
    public function __construct(private AdminAccess $access) {}

    /**
     * @return list<array{label: string, icon: string, href: string, active: bool, children: list<array{label: string, icon: string, href: string, active: bool}>}>
     */
    public function forUser(?User $user, Request $request): array
    {
        if (! $user) {
            return [];
        }

        $items = [];

        foreach (config('admin_menu', []) as $item) {
            if (! $this->allows($user, $item)) {
                continue;
            }

            $items[] = $this->entry($item, $request) + [
                'children' => $this->children($user, $item, $request),
            ];
        }

        return $items;
    }

    /**
     * Sous-menu autorisé d'une entrée du menu (identifiée par sa route),
     * avec les champs propres aux cartes de module (`color`, `description`).
     *
     * @return list<array{label: string, icon: string, href: string, active: bool, color: ?string, description: ?string}>
     */
    public function childrenOf(?User $user, string $route, Request $request): array
    {
        $item = collect(config('admin_menu', []))->firstWhere('route', $route);

        if (! $user || ! $item) {
            return [];
        }

        return array_map(
            fn (array $child) => $this->entry($child, $request) + [
                'color' => $child['color'] ?? null,
                'description' => $child['description'] ?? null,
            ],
            $this->allowedChildren($user, $item)
        );
    }

    /**
     * @return list<array{label: string, icon: string, href: string, active: bool}>
     */
    private function children(User $user, array $item, Request $request): array
    {
        return array_map(fn (array $child) => $this->entry($child, $request), $this->allowedChildren($user, $item));
    }

    private function allowedChildren(User $user, array $item): array
    {
        return array_values(array_filter($item['children'] ?? [], fn (array $child) => $this->allows($user, $child)));
    }

    private function allows(User $user, array $item): bool
    {
        return Route::has($item['route']) && $this->access->allows($user, $item['route']);
    }

    /**
     * @return array{label: string, icon: string, href: string, active: bool}
     */
    private function entry(array $item, Request $request): array
    {
        return [
            'label' => $item['label'],
            'icon' => $item['icon'],
            'href' => route($item['route'], $item['params'] ?? []),
            'active' => $request->routeIs(...($item['active'] ?? [$item['route']])),
        ];
    }
}
