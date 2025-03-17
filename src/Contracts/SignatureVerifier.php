<?php

namespace Mak8Tech\MobileWalletZm\Contracts;

use Illuminate\Http\Request;

interface SignatureVerifier
{
    /**
     * Verify the signature of an incoming webhook request
     * 
     * @param Request $request
     * @return bool
     */
    public function verifySignature(Request $request): bool;
} 