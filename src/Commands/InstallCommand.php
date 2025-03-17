<?php

namespace Mak8Tech\MobileWalletZm\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mobile-wallet:install';

    /**
     * The console command description.
     */
    protected $description = 'Install the Mobile Wallet ZM package';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'mobile-wallet-config',
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--tag' => 'mobile-wallet-migrations',
        ]);

        // Publish assets
        $this->call('vendor:publish', [
            '--tag' => 'mobile-wallet-assets',
        ]);

        // Publish routes
        $this->call('vendor:publish', [
            '--tag' => 'mobile-wallet-routes',
        ]);

        $this->info('Mobile Wallet ZM package installed successfully.');
        $this->info('Please update your .env file with your mobile money provider credentials.');
    }
}
