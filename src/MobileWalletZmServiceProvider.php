<?php

namespace Mak8Tech\MobileWalletZm;

use Mak8Tech\MobileWalletZm\Commands\MobileWalletZmCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
