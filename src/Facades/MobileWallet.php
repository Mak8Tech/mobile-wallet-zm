<?php

namespace Mak8Tech\MobileWalletZm\Facades;

use Illuminate\Support\Facades\Facade;

class MobileWallet extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'mobile-wallet';
    }
}
