<?php

namespace Mak8Tech\MobileWalletZm\Exceptions;

class PaymentStatusException extends MobileWalletException
{
    /**
     * Create a new payment status exception.
     */
    public function __construct(
        string $message = 'Payment status check failed',
        ?string $provider = null,
        mixed $rawResponse = null,
        int $code = 400
    ) {
        parent::__construct(
            $message,
            'payment_status_failed',
            $provider,
            $rawResponse,
            $code
        );
    }
} 