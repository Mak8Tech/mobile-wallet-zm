<?php

namespace Mak8Tech\MobileWalletZm\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Mak8Tech\MobileWalletZm\Commands\InstallCommand;
use Mak8Tech\MobileWalletZm\Contracts\SignatureVerifier;
use Mak8Tech\MobileWalletZm\Http\Middleware\AuthorizeAdminOperations;
use Mak8Tech\MobileWalletZm\Http\Middleware\RateLimitApiRequests;
use Mak8Tech\MobileWalletZm\Http\Middleware\VerifyCsrfToken;
use Mak8Tech\MobileWalletZm\Http\Middleware\VerifyWebhookSignature;
use Mak8Tech\MobileWalletZm\MobileWalletManager;
use Mak8Tech\MobileWalletZm\Security\AirtelSignatureVerifier;
use Mak8Tech\MobileWalletZm\Security\MTNSignatureVerifier;
use Mak8Tech\MobileWalletZm\Security\SignatureVerifierFactory;
use Mak8Tech\MobileWalletZm\Security\ZamtelSignatureVerifier;
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
            __DIR__.'/../../config/mobile_wallet.php',
            'mobile_wallet'
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

        // Register signature verifiers
        $this->app->singleton(MTNSignatureVerifier::class, function ($app) {
            return new MTNSignatureVerifier(
                config('mobile_wallet.mtn.api_secret')
            );
        });

        $this->app->singleton(AirtelSignatureVerifier::class, function ($app) {
            return new AirtelSignatureVerifier(
                config('mobile_wallet.airtel.api_key'),
                config('mobile_wallet.airtel.api_secret')
            );
        });

        $this->app->singleton(ZamtelSignatureVerifier::class, function ($app) {
            return new ZamtelSignatureVerifier(
                config('mobile_wallet.zamtel.api_secret')
            );
        });

        // Register a SignatureVerifier resolver that returns the appropriate verifier based on provider
        $this->app->bind(SignatureVerifier::class, function ($app, $params) {
            $provider = $params['provider'] ?? config('mobile_wallet.default');
            $config = config("mobile_wallet.{$provider}");

            return SignatureVerifierFactory::create($provider, $config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Get the router instance
        $router = $this->app->make(Router::class);

        // Register the middleware with aliases
        $router->aliasMiddleware('verify.webhook.signature', VerifyWebhookSignature::class);
        $router->aliasMiddleware('rate.limit', RateLimitApiRequests::class);
        $router->aliasMiddleware('mobile-wallet.csrf', VerifyCsrfToken::class);
        $router->aliasMiddleware('mobile-wallet.admin', AuthorizeAdminOperations::class);

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

        // Publish routes
        $this->publishes([
            __DIR__.'/../../routes/web.php' => base_path('routes/mobile-wallet.php'),
        ], 'mobile-wallet-routes');

        $this->publishes([
            __DIR__.'/../../routes/admin.php' => base_path('routes/mobile-wallet-admin.php'),
        ], 'mobile-wallet-admin-routes');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');

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
