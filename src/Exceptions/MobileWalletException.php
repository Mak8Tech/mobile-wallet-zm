<?php

namespace Mak8Tech\MobileWalletZm\Exceptions;

use Exception;

class MobileWalletException extends Exception
{
    /**
     * Error code.
     */
    protected string $errorCode;

    /**
     * Provider that threw the exception.
     */
    protected ?string $provider = null;

    /**
     * Raw response from the provider API.
     */
    protected mixed $rawResponse = null;

    /**
     * Create a new mobile wallet exception.
     */
    public function __construct(
        string $message = 'Mobile Wallet error occurred',
        string $errorCode = 'mobile_wallet_error',
        ?string $provider = null,
        mixed $rawResponse = null,
        int $code = 0,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->provider = $provider;
        $this->rawResponse = $rawResponse;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the provider name.
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * Get the raw response.
     */
    public function getRawResponse(): mixed
    {
        return $this->rawResponse;
    }

    /**
     * Convert the exception to an array for use in API responses.
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
                'provider' => $this->provider,
                'details' => $this->rawResponse,
            ],
        ];
    }

    /**
     * Convert the exception to a JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
} 