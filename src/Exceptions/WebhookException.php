<?php

namespace Mak8Tech\MobileWalletZm\Exceptions;

class WebhookException extends MobileWalletException
{
    /**
     * Create a new webhook exception.
     */
    public function __construct(
        string $message = 'Webhook processing failed',
        ?string $provider = null,
        mixed $rawResponse = null,
        int $code = 400
    ) {
        parent::__construct(
            $message,
            'webhook_error',
            $provider,
            $rawResponse,
            $code
        );
    }
}
