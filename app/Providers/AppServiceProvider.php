<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use App\Repositories\Interfaces\EtudiantRepositoryInterface;
use App\Repositories\EtudiantRepository;
use App\Repositories\Interfaces\DemandeRepositoryInterface;
use App\Repositories\DemandeRepository;
use App\Repositories\Interfaces\DocumentEtudiantRepositoryInterface;
use App\Repositories\DocumentEtudiantRepository;
use App\Http\Services\DemandeService;
use App\Http\Services\DocumentEtudiantService;
use App\Http\Services;
use App\Repositories\Interfaces\CiteRepositoryInterface;
use App\Repositories\CiteRepository;
use App\Http\Services\CiteService;
use App\Http\Services\OccupationService;

use App\Repositories\Interfaces\CampusRepositoryInterface;
use App\Repositories\CampusRepository;
use App\Repositories\BatimentRepository;

use App\Http\Services\BatimentService;
use App\Http\Services\CampusService;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\OccupationServiceInterface;
use App\Repositories\Interfaces\BatimentRepositoryInterface;
use App\Repositories\Interfaces\ChambreRepositoryInterface;

use App\Models\Campus;
use App\Repositories\UserRepository;

use App\Repositories\ConfigAppRepository;
use App\Repositories\Interfaces\ConfigAppRepositoryInterface;

use App\Repositories\EtatDesLieuxRepository;
use App\Repositories\Interfaces\EtatDesLieuxRepositoryInterface;

use App\Repositories\OrdreTravailRepository;
use App\Repositories\Interfaces\OrdreTravailRepositoryInterface;
use App\Repositories\Interfaces\OrdreTravailServiceInterface;
use App\Http\Services\OrdreTravailService;

use App\Repositories\Interfaces\AnnonceRepositoryInterface;
use App\Repositories\AnnonceRepository;
use App\Repositories\Interfaces\MaintenancierRepositoryInterface;
use App\Repositories\MaintenancierRepository;

use App\Repositories\Interfaces\InterventionRepositoryInterface;
use App\Repositories\InterventionRepository;
use  App\Http\Services\InterventionService;
use App\Repositories\Interfaces\PaiementRepositoryInterface;
use App\Repositories\PaiementRepository;
use App\Repositories\Interfaces\LoyerRepositoryInterface;
use App\Repositories\LoyerRepository;
use App\Http\Services\LoyerService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoyerRepositoryInterface::class, LoyerRepository::class);
        
        // Enregistrement du service
        $this->app->singleton(LoyerService::class, function ($app) {
            return new LoyerService(
                $app->make(LoyerRepositoryInterface::class)
            );
        });

        $this->app->bind(PaiementRepositoryInterface::class,PaiementRepository::class);

        $this->app->bind(InterventionRepositoryInterface::class, InterventionRepository::class);

        // Enregistrement du service
        $this->app->singleton(InterventionService::class, function ($app) {
            return new InterventionService($app->make(InterventionRepositoryInterface::class));
        });

        $this->app->bind( MaintenancierRepositoryInterface::class, MaintenancierRepository::class);
        $this->app->bind(AnnonceRepositoryInterface::class, AnnonceRepository::class);
        $this->app->bind( OrdreTravailRepositoryInterface::class,OrdreTravailRepository::class);

        $this->app->bind(OrdreTravailServiceInterface::class,OrdreTravailService::class);
        $this->app->bind(EtatDesLieuxRepositoryInterface::class,EtatDesLieuxRepository::class);
        $this->app->bind(ConfigAppRepositoryInterface::class, ConfigAppRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        $this->app->bind(CiteRepositoryInterface::class, CiteRepository::class);
        $this->app->bind(BatimentRepositoryInterface::class, BatimentRepository::class);
        $this->app->bind(ChambreRepositoryInterface::class, ChambreRepository::class);

        // --- Services ---
        $this->app->bind(OccupationServiceInterface::class, OccupationService::class);
        //
        $this->app->bind(
            EtudiantRepositoryInterface::class,
            EtudiantRepository::class
        );
        $this->app->bind(
            DemandeRepositoryInterface::class,
            DemandeRepository::class
        );
        $this->app->bind(
            DocumentEtudiantRepositoryInterface::class,
            DocumentEtudiantRepository::class
        );
        $this->app->bind(DemandeService::class, function ($app) {
            return new DemandeService(
                $app->make(DemandeRepositoryInterface::class)
            );
        });

        $this->app->bind(DocumentEtudiantService::class, function ($app) {
            return new DocumentEtudiantService(
                $app->make(DocumentEtudiantRepositoryInterface::class)
            );
        });

        $this->app->bind(EtudiantService::class, function ($app) {
            return new EtudiantService(
                $app->make(EtudiantRepositoryInterface::class)
            );
        });

        // Enregistrer le repository
        $this->app->bind(
            CampusRepositoryInterface::class,
            CampusRepository::class
        );

        // Enregistrer le service
        $this->app->bind(
            CampusService::class,
            function ($app) {
                return new CampusService(
                    $app->make(CampusRepositoryInterface::class)
                );
            }
        );

        // Enregistrer le repository
        $this->app->bind(
            CiteRepositoryInterface::class,
            CiteRepository::class
        );

        // Enregistrer le service
        $this->app->bind(
            CiteService::class,
            function ($app) {
                return new CiteService(
                    $app->make(CiteRepositoryInterface::class),
                    $app->make(OccupationServiceInterface::class)

                );
            }
        );

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Some hosts expose public/ below the domain root and bypass .htaccess
        // for static files. Respect an explicit CDN/ASSET_URL configuration.
        $request = $this->app['request'];
        $documentRoot = $request->server('DOCUMENT_ROOT');
        if (! config('app.asset_url') && $documentRoot
            && realpath($documentRoot) !== realpath(public_path())
            && is_file(rtrim($documentRoot, '/\\').'/public/build/manifest.json')) {
            $this->app['url']->useAssetOrigin($request->getSchemeAndHttpHost().$request->getBasePath().'/public');
        }

        Blade::if('adminroute', fn (string $route) => app(\App\Services\AdminAccess::class)->allows(auth('admin')->user(), $route));
        Blade::if('adminpermission', fn (string ...$permissions) => app(\App\Services\AdminViewAccess::class)->any(auth('admin')->user(), $permissions));
        Blade::if('adminblock', fn (string $block) => app(\App\Services\AdminViewAccess::class)->allows(auth('admin')->user(), $block));
        View::composer('admin.*', function ($view) {
            if ($actor = auth('admin')->user()) {
                $view->with(app(\App\Services\AdminViewData::class)->filter($view->getData(), $actor));
            }
        });
        //
        Blade::component('components.card-statistics', 'card-statistics');
        Blade::component('components.card-analytics', 'card-analytics');
        Blade::component('components.card-table', 'card-table');
        Blade::component('components.card-user-activity', 'card-user-activity');
        if (! $this->app->runningInConsole()) {
            try {
                $systemConfig = allConfig();

                View::share('systemConfig', $systemConfig);

                if ($systemConfig) {
                    config([
                        'app.name' => $systemConfig->app_name ?: config('app.name'),
                        'app.locale' => $systemConfig->locale ?: config('app.locale'),
                        'app.timezone' => $systemConfig->timezone ?: config('app.timezone'),
                        'session.lifetime' => $systemConfig->session_lifetime ?: config('session.lifetime'),
                        'session.secure' => (bool) $systemConfig->force_https,
                        'mail.default' => $systemConfig->mail_driver ?: config('mail.default'),
                        'mail.from.address' => $systemConfig->mail_from_address ?: $systemConfig->email ?: config('mail.from.address'),
                        'mail.from.name' => $systemConfig->mail_from_name ?: $systemConfig->company_name ?: $systemConfig->app_name ?: config('mail.from.name'),
                    ]);

                    app()->setLocale(config('app.locale'));
                    date_default_timezone_set(config('app.timezone'));
                }
            } catch (\Throwable $exception) {
                View::share('systemConfig', null);
            }

            listeCampagnes();
        }
        
    }
}
