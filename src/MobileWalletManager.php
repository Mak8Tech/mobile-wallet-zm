<?php

namespace Mak8Tech\MobileWalletZm;

use Illuminate\Contracts\Foundation\Application;
use Mak8Tech\MobileWalletZm\Services\AirtelService;
use Mak8Tech\MobileWalletZm\Services\MTNService;
use Mak8Tech\MobileWalletZm\Services\ZamtelService;

class MobileWalletManager
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The default provider.
     */
    protected string $default;

    /**
     * The array of resolved providers.
     */
    protected array $providers = [];

    /**
     * Create a new mobile wallet manager instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->default = $this->app['config']['mobile_wallet.default'] ?? 'mtn';
    }

    /**
     * Get a payment provider instance.
     */
    public function provider(?string $name = null): object
    {
        $name = $name ?: $this->getDefaultProvider();

        return $this->providers[$name] = $this->get($name);
    }

    /**
     * Get the default provider name.
     */
    public function getDefaultProvider(): string
    {
        return $this->default;
    }

    /**
     * Set the default provider name.
     */
    public function setDefaultProvider(string $name): void
    {
        $this->default = $name;
    }

    /**
     * Get a provider instance.
     */
    protected function get(string $name): object
    {
        return $this->providers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given provider.
     */
    protected function resolve(string $name): object
    {
        $config = $this->app['config']["mobile_wallet.{$name}"];

        $providerClass = match($name) {
            'mtn' => MTNService::class,
            'airtel' => AirtelService::class,
            'zamtel' => ZamtelService::class,
            default => throw new \InvalidArgumentException("Provider [{$name}] is not supported."),
        };

        return $this->app->make($providerClass);
    }

    /**
     * Dynamically call the default provider instance.
     */
    public function __call(string $method, array $parameters)
    {
        return $this->provider()->$method(...$parameters);
    }
}
