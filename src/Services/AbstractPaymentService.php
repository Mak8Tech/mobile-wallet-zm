<?php

namespace Mak8Tech\MobileWalletZm\Services;

use Illuminate\Support\Facades\Http;
use Mak8Tech\MobileWalletZm\Models\Transaction;

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
        string $reference = null,
        string $narration = null,
        $transactionable = null
    ): Transaction {
        $transaction = new Transaction([
            'provider' => $this->provider,
            'phone_number' => $phoneNumber,
            'amount' => $amount,
            'currency' => config('mobile_wallet.currency', 'ZMW'),
            'reference' => $reference ?? 'Payment of ' . $amount . ' ' . config('mobile_wallet.currency', 'ZMW'),
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
    protected function updateTransactionWithResponse(Transaction $transaction, array $response, string $status = null): Transaction
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
    protected function getHttpClient(string $accessToken = null): \Illuminate\Http\Client\PendingRequest
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
        if (!str_starts_with($phoneNumber, '26')) {
            $phoneNumber = '26' . $phoneNumber;
        }

        return $phoneNumber;
    }
}
