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
            fn(string $modelName) => 'Mak8Tech\\MobileWalletZm\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        // Set up the database migration for testing
        $this->setupDatabase();
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

        $migration = include __DIR__ . '/../database/migrations/create_mobile_wallet_transactions_table.php';
        $migration->up();
    }

    protected function setupDatabase()
    {
        // Create the transactions table if it doesn't exist
        if (! $this->app['db']->connection()->getSchemaBuilder()->hasTable(config('mobile_wallet.database.table', 'mobile_wallet_transactions'))) {
            $migration = include __DIR__ . '/../database/migrations/create_mobile_wallet_transactions_table.php';
            $migration->up();
        }
    }
}
