<?php

namespace Mak8Tech\MobileWalletZm\Exceptions;

class AuthenticationException extends MobileWalletException
{
    /**
     * Create a new authentication exception.
     */
    public function __construct(
        string $message = 'Authentication failed',
        ?string $provider = null,
        mixed $rawResponse = null,
        int $code = 401
    ) {
        parent::__construct(
            $message,
            'authentication_failed',
            $provider,
            $rawResponse,
            $code
        );
    }
}
