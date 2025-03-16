<?php

namespace Mak8Tech\MobileWalletZm\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mak8Tech\MobileWalletZm\MobileWalletZm
 */
class MobileWalletZm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Mak8Tech\MobileWalletZm\MobileWalletZm::class;
    }
}
