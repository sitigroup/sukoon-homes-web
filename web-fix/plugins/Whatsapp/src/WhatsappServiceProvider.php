<?php

namespace App\Plugins\Whatsapp;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Plugins\Whatsapp\Services\WhatsappService;
use App\Plugins\Whatsapp\Support\Whatsapp as WhatsappSupport;

class WhatsappServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsappService::class, fn () => new WhatsappService());
    }

    public function boot(): void
    {
        $base = app_path('Plugins/Whatsapp');

        $this->loadMigrationsFrom($base . '/database/migrations');
        $this->loadViewsFrom($base . '/resources/views', 'whatsapp');
        $this->loadTranslationsFrom($base . '/resources/lang', 'whatsapp');

        if (file_exists($base . '/config/whatsapp-permissions.php')) {
            $module = require $base . '/config/whatsapp-permissions.php';
            $existing = config('rolepermission.modules', []);
            config(['rolepermission.modules' => array_merge($existing, [$module])]);
        }

        if (! class_exists('Whatsapp')) {
            class_alias(WhatsappSupport::class, 'Whatsapp');
        }

        Route::middleware('web')->group($base . '/routes/web.php');
        Route::prefix('api')->middleware('api')->group($base . '/routes/api.php');
    }
}

