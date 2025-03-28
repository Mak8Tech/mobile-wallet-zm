<?php

namespace Mak8Tech\MobileWalletZm\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mak8Tech\MobileWalletZm\Exceptions\AuthenticationException;
use Mak8Tech\MobileWalletZm\Exceptions\InvalidTransactionException;
use Mak8Tech\MobileWalletZm\Exceptions\PaymentRequestException;
use Mak8Tech\MobileWalletZm\Exceptions\PaymentStatusException;
use Mak8Tech\MobileWalletZm\Exceptions\WebhookException;
use Mak8Tech\MobileWalletZm\Models\WalletTransaction as Transaction;

abstract class AbstractPaymentService
{
    /**
     * The API base URL.
     */
    protected string $baseUrl;

    /**
     * The API key.
     */
    protected string $apiKey;

    /**
     * The API secret.
     */
    protected string $apiSecret;

    /**
     * The provider name.
     */
    protected string $provider;

    /**
     * Create a new payment service instance.
     */
    public function __construct(string $baseUrl, string $apiKey, string $apiSecret)
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Authenticate with the API.
     */
    abstract public function authenticate(): string;

    /**
     * Request a payment from a mobile money subscriber.
     */
    abstract public function requestPayment(
        string $phoneNumber,
        float $amount,
        ?string $reference = null,
        ?string $narration = null
    ): array;

    /**
     * Check the status of a transaction.
     */
    abstract public function checkTransactionStatus(string $transactionId): array;

    /**
     * Process a callback/webhook from the provider.
     */
    abstract public function processCallback(array $payload): array;

    /**
     * Create a transaction record.
     */
    public function createTransaction(
        string $phoneNumber,
        float $amount,
        ?string $reference = null,
        ?string $narration = null,
        $transactionable = null
    ): Transaction {
        $transaction = new Transaction([
            'provider' => $this->provider,
            'phone_number' => $phoneNumber,
            'amount' => $amount,
            'currency' => config('mobile_wallet.currency', 'ZMW'),
            'reference' => $reference ?? 'Payment of '.$amount.' '.config('mobile_wallet.currency', 'ZMW'),
            'narration' => $narration ?? 'Mobile wallet payment',
            'status' => 'pending',
        ]);

        if ($transactionable) {
            $transaction->transactionable()->associate($transactionable);
        }

        $transaction->save();

        return $transaction;
    }

    /**
     * Update a transaction with a response.
     */
    protected function updateTransactionWithResponse(Transaction $transaction, array $response, ?string $status = null): Transaction
    {
        $data = [
            'raw_response' => $response,
        ];

        if ($status) {
            $data['status'] = $status;
        }

        $transaction->update($data);

        return $transaction;
    }

    /**
     * Get the HTTP client with appropriate headers.
     */
    protected function getHttpClient(?string $accessToken = null): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::acceptJson()
            ->contentType('application/json')
            ->timeout(30);

        if ($accessToken) {
            $client->withToken($accessToken);
        }

        return $client;
    }

    /**
     * Format the phone number for the provider.
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Remove leading zeros
        $phoneNumber = ltrim($phoneNumber, '0');

        // Add country code if not already present
        if (! str_starts_with($phoneNumber, '260')) {
            $phoneNumber = '260'.$phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Handle authentication exception.
     */
    protected function handleAuthenticationException(string $message, $response): never
    {
        $this->logError('Authentication error', $message, $response);

        throw new AuthenticationException(
            $message,
            $this->provider,
            $response instanceof \Illuminate\Http\Client\Response ? $response->json() : $response
        );
    }

    /**
     * Handle payment request exception.
     */
    protected function handlePaymentRequestException(string $message, $response, ?Transaction $transaction = null): never
    {
        if ($transaction) {
            $transaction->markAsFailed($message);
        }

        $this->logError('Payment request error', $message, $response);

        throw new PaymentRequestException(
            $message,
            $this->provider,
            $response instanceof \Illuminate\Http\Client\Response ? $response->json() : $response
        );
    }

    /**
     * Handle payment status exception.
     */
    protected function handlePaymentStatusException(string $message, $response): never
    {
        $this->logError('Payment status error', $message, $response);

        throw new PaymentStatusException(
            $message,
            $this->provider,
            $response instanceof \Illuminate\Http\Client\Response ? $response->json() : $response
        );
    }

    /**
     * Handle webhook exception.
     */
    protected function handleWebhookException(string $message, $response): never
    {
        $this->logError('Webhook error', $message, $response);

        throw new WebhookException(
            $message,
            $this->provider,
            $response instanceof \Illuminate\Http\Client\Response ? $response->json() : $response
        );
    }

    /**
     * Handle invalid transaction exception.
     */
    protected function handleInvalidTransactionException(string $message, $details = null): never
    {
        $this->logError('Invalid transaction', $message, $details);

        throw new InvalidTransactionException(
            $message,
            $this->provider,
            $details
        );
    }

    /**
     * Log an error with context.
     */
    protected function logError(string $type, string $message, $details = null): void
    {
        Log::error("Mobile Wallet [{$this->provider}] {$type}: {$message}", [
            'provider' => $this->provider,
            'details' => $details instanceof \Illuminate\Http\Client\Response ? [
                'status' => $details->status(),
                'body' => $details->body(),
                'headers' => $details->headers(),
            ] : $details,
        ]);
    }

    /**
     * Log information with context.
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("Mobile Wallet [{$this->provider}]: {$message}", array_merge(['provider' => $this->provider], $context));
    }
}
