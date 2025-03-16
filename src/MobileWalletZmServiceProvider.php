<?php

namespace Mak8Tech\MobileWalletZm;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Mak8Tech\MobileWalletZm\Commands\MobileWalletZmCommand;

class MobileWalletZmServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('mobilewalletzm')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_migration_wallet_transactions_table')
            ->hasCommand(MobileWalletZmCommand::class);
    }
}
