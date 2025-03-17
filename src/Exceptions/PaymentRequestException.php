<?php

namespace Mak8Tech\MobileWalletZm\Exceptions;

class PaymentRequestException extends MobileWalletException
{
    /**
     * Create a new payment request exception.
     */
    public function __construct(
        string $message = 'Payment request failed',
        ?string $provider = null,
        mixed $rawResponse = null,
        int $code = 400
    ) {
        parent::__construct(
            $message,
            'payment_request_failed',
            $provider,
            $rawResponse,
            $code
        );
    }
}
