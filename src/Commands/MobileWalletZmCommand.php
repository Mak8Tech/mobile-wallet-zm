<?php

namespace Mak8Tech\MobileWalletZm\Commands;

use Illuminate\Console\Command;

class MobileWalletZmCommand extends Command
{
    public $signature = 'mobilewalletzm';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
