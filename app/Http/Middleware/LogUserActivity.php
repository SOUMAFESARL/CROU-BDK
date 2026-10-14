<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    private const TRACKED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), self::TRACKED_METHODS, true)) {
            return $next($request);
        }
        $actor = $this->resolveActor();
        $response = $next($request);

        if (!$this->shouldLog($request, $response)) {
            return $response;
        }

        // Une connexion réussie rend l'acteur disponible seulement après le contrôleur.
        $actor ??= $this->resolveActor();

        try {
            ActivityLog::create([
                'actor_type' => $actor['type'] ?? 'anonymous',
                'actor_id' => $actor['id'] ?? null,
                'actor_name' => $actor['name'] ?? null,
                'action' => $this->actionFrom($request),
                'method' => $request->method(),
                'route_name' => $request->route()?->getName(),
                'controller_action' => $request->route()?->getActionName(),
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                'status_code' => $response->getStatusCode(),
                'context' => [
                    'route_parameters' => $this->safeRouteParameters($request),
                ],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        return systemConfig('activity_log', false)
            && in_array($request->method(), self::TRACKED_METHODS, true)
            && $response->getStatusCode() < 400;
    }

    private function resolveActor(): ?array
    {
        foreach (['admin', 'etudiant', 'web'] as $guard) {
            if (!Auth::guard($guard)->check()) {
                continue;
            }

            $user = Auth::guard($guard)->user();

            return [
                'type' => $guard === 'etudiant' ? 'student' : 'admin',
                'id' => (string) $user->getAuthIdentifier(),
                'name' => $user->name ?? $user->email ?? null,
            ];
        }

        return null;
    }

    private function actionFrom(Request $request): string
    {
        $route = strtolower((string) $request->route()?->getName());

        if (str_contains($route, 'login')) return 'login';
        if (str_contains($route, 'logout')) return 'logout';

        return match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'action',
        };
    }

    private function safeRouteParameters(Request $request): array
    {
        return collect($request->route()?->parameters() ?? [])
            ->map(fn ($value) => is_scalar($value) ? (string) $value : null)
            ->filter(fn ($value) => $value !== null)
            ->all();
    }
}
