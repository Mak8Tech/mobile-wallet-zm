<?php

namespace Mak8Tech\MobileWalletZm\Providers;

use Illuminate\Support\ServiceProvider;
use Mak8Tech\MobileWalletZm\Console\Commands\InstallCommand;
use Mak8Tech\MobileWalletZm\MobileWalletManager;
use Mak8Tech\MobileWalletZm\Services\AirtelService;
use Mak8Tech\MobileWalletZm\Services\MTNService;
use Mak8Tech\MobileWalletZm\Services\ZamtelService;

class MobileWalletServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/mobile_wallet.php', 'mobile_wallet'
        );

        // Register the main manager
        $this->app->singleton('mobile-wallet', function ($app) {
            return new MobileWalletManager($app);
        });

        // Register individual payment services
        $this->app->singleton(AirtelService::class, function ($app) {
            return new AirtelService(
                config('mobile_wallet.airtel.base_url'),
                config('mobile_wallet.airtel.api_key'),
                config('mobile_wallet.airtel.api_secret')
            );
        });

        $this->app->singleton(MTNService::class, function ($app) {
            return new MTNService(
                config('mobile_wallet.mtn.base_url'),
                config('mobile_wallet.mtn.api_key'),
                config('mobile_wallet.mtn.api_secret')
            );
        });

        $this->app->singleton(ZamtelService::class, function ($app) {
            return new ZamtelService(
                config('mobile_wallet.zamtel.base_url'),
                config('mobile_wallet.zamtel.api_key'),
                config('mobile_wallet.zamtel.api_secret')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/mobile_wallet.php' => config_path('mobile_wallet.php'),
        ], 'mobile-wallet-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
        ], 'mobile-wallet-migrations');

        // Publish assets (React components)
        $this->publishes([
            __DIR__.'/../../resources/js/' => resource_path('js/vendor/mobile-wallet-zm'),
        ], 'mobile-wallet-assets');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
