<?php

namespace Mak8Tech\MobileWalletZm\Exceptions;

class InvalidTransactionException extends MobileWalletException
{
    /**
     * Create a new invalid transaction exception.
     */
    public function __construct(
        string $message = 'Invalid transaction data',
        ?string $provider = null,
        mixed $rawResponse = null,
        int $code = 400
    ) {
        parent::__construct(
            $message,
            'invalid_transaction',
            $provider,
            $rawResponse,
            $code
        );
    }
} 