<?php

namespace Mak8Tech\MobileWalletZm\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Mak8Tech\MobileWalletZm\Providers\MobileWalletServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Mak8Tech\\MobileWalletZm\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Don't call setupDatabase() here to avoid duplicate migrations
        // The database setup will be handled by RefreshDatabase trait or getEnvironmentSetUp
    }

    protected function getPackageProviders($app)
    {
        return [
            MobileWalletServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Drop the table if it exists before creating it
        $app['db']->connection()->getSchemaBuilder()->dropIfExists(config('mobile_wallet.database.table', 'mobile_wallet_transactions'));

        $migration = include __DIR__.'/../database/migrations/create_mobile_wallet_transactions_table.php';
        $migration->up();
    }

    // Remove the setupDatabase method as it's causing duplicate migrations
}
