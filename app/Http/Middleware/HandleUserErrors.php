<?php
namespace App\Http\Middleware;
use App\Support\UserFacingErrors;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HandleUserErrors
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(config('user_errors.locale', 'fr'));
        try {
            $response = $next($request);
        } catch (Throwable $error) {
            $handler = app(ExceptionHandler::class);
            $handler->report($error);
            return $handler->render($request, $error);
        }
        if ($response instanceof \Illuminate\Http\JsonResponse && $response->getStatusCode() >= 500) {
            $response->setData(['success'=>false, 'message'=>UserFacingErrors::messageForStatus($response->getStatusCode())]);
            $response->headers->set('Cache-Control','private, no-store');
        }
        if ($response instanceof \Illuminate\Http\Response && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $content = $response->getContent();
            if (is_string($content) && stripos($content, '</body>') !== false && !str_contains($content, 'js/user-errors.js')) {
                $script = '<script src="'.e(asset('js/user-errors.js')).'?v=20261010b" defer></script>';
                // Les scripts d'impression peuvent contenir leur propre </body>.
                // Insérer avant la fermeture de la page, située après ces scripts.
                $bodyEnd = strripos($content, '</body>');
                $response->setContent(substr_replace($content, $script, $bodyEnd, 0));
                $response->headers->remove('Content-Length');
                $response->headers->remove('ETag');
            }
        }
        return $response;
    }
}
