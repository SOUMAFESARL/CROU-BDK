<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAdmin;
use App\Http\Middleware\RedirectIfEdudiant;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RedirectIfNotAdmin;
use App\Http\Middleware\RedirectIfNotEtudiant;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
       // web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function(){
            Route::namespace('App\Http\Controllers')->group(function(){
                Route::prefix('api')
                    ->middleware(['api'])
                    ->group(base_path('routes/api.php'));

                Route::middleware(['web', \App\Http\Middleware\CheckAdminAccess::class])

                    ->prefix('admin')
                    ->name('admin.')
                    ->group(base_path('routes/admin.php'));

                Route::middleware(['web'])
                   // ->namespace('etudiants')
                    ->prefix('etudiant')
                    ->name('etudiants.')
                    ->group(base_path('routes/etudiant.php'));

                Route::middleware('web')
                    ->group(base_path('routes/web.php'));
            });

        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\App\Http\Middleware\HandleUserErrors::class);
      $middleware->append(\App\Http\Middleware\Cors::class);
        $middleware->group('web',[
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
         //   \App\Http\Middleware\LanguageMiddleware::class,
            \App\Http\Middleware\EnsureTokenIsValid::class,
            \App\Http\Middleware\EnforceSystemSecurity::class,
            \App\Http\Middleware\LogUserActivity::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        $middleware->group('admin',[
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
         //   \App\Http\Middleware\LanguageMiddleware::class,
            \App\Http\Middleware\EnsureTokenIsValid::class,
            \App\Http\Middleware\RedirectIfNotAdmin::class,
            \App\Http\Middleware\LogUserActivity::class,
        ]);
        $middleware->group('etudiants',[
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
         //   \App\Http\Middleware\RedirectIfEdudiant::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
          //  \App\Http\Middleware\LanguageMiddleware::class,
            \App\Http\Middleware\EnsureTokenIsValid::class,
            \App\Http\Middleware\RedirectIfNotEtudiant::class,
            \App\Http\Middleware\LogUserActivity::class,
          //  \App\Http\Middleware\CheckSubscription::class
        ]);
        $middleware->appendToGroup('api', \App\Http\Middleware\LogUserActivity::class);
        $middleware->alias([
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            'admin' => RedirectIfNotAdmin::class,
            'admin.guest' => RedirectIfAdmin::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,

            'etudiants' => RedirectIfNotEtudiant::class,
            'etudiants.guest' => RedirectIfEdudiant::class,
            'student.api' => \App\Http\Middleware\AuthenticateStudentApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $error, \Illuminate\Http\Request $request) {
            return \App\Support\UserFacingErrors::render($error, $request);
        });
    })->create();
