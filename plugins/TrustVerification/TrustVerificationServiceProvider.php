<?php

namespace App\Plugins\TrustVerification;

use App\Plugins\TrustVerification\Http\Middleware\TrustVerificationThrottle;
use App\Plugins\TrustVerification\Services\TrustVerificationPermissionService;
use App\Plugins\TrustVerification\Services\TrustVerificationRateLimiterRegistrar;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class TrustVerificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        TrustVerificationPermissionService::registerModule();
    }

    public function boot(): void
    {
        TrustVerificationRateLimiterRegistrar::register();

        $router = $this->app['router'];
        $router->aliasMiddleware('tv.throttle', TrustVerificationThrottle::class);

        $base = __DIR__;

        View::composer('trust-verification::admin.*', function ($view) {
            $view->with('tvPermissions', TrustVerificationPermissionService::capabilities());
        });

        $this->commands([
            Console\ReconcileTrustVerificationPaymentsCommand::class,
            Console\CleanupTrustVerificationPiiCommand::class,
            Console\RefreshTrustScoreCommand::class,
            Console\RefreshRiskCommand::class,
            Console\RefreshTenantReliabilityCommand::class,
            Console\IssueMissingVerificationBadgesCommand::class,
            Console\ActivateOwnerSvoCommand::class,
        ]);

        $this->loadMigrationsFrom($base.'/database/migrations');
        $this->loadViewsFrom($base.'/views', 'trust-verification');

        Route::middleware('api')
            ->prefix('api')
            ->group($base.'/routes/api.php');

        Route::middleware(['web', 'auth', 'checkLogin'])
            ->group($base.'/routes/web.php');
    }
}
