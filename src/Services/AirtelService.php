<?php

namespace Mak8Tech\MobileWalletZm\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mak8Tech\MobileWalletZm\Models\WalletTransaction as Transaction;

class AirtelService extends AbstractPaymentService
{
    /**
     * The API environment.
     */
    protected string $environment;

    /**
     * The provider name.
     */
    protected string $provider = 'airtel';

    /**
     * Create a new Airtel service instance.
     */
    public function __construct(
        string $baseUrl,
        string $apiKey,
        string $apiSecret
    ) {
        parent::__construct($baseUrl, $apiKey, $apiSecret);

        $this->environment = config('mobile_wallet.airtel.environment', 'sandbox');
    }

    /**
     * Authenticate with Airtel Money API.
     */
    public function authenticate(): string
    {
        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->post("{$this->baseUrl}/auth/oauth2/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->apiKey,
                'client_secret' => $this->apiSecret,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Airtel Money authentication failed: '.$response->body());
        }

        $data = $response->json();

        return $data['access_token'];
    }

    /**
     * Request a payment from a mobile money subscriber.
     */
    public function requestPayment(
        string $phoneNumber,
        float $amount,
        ?string $reference = null,
        ?string $narration = null
    ): array {
        // Create a transaction record
        $transaction = $this->createTransaction(
            $phoneNumber,
            $amount,
            $reference,
            $narration
        );

        // Format phone number
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        // Get authentication token
        $accessToken = $this->authenticate();

        // Prepare request payload
        $payload = [
            'reference' => $transaction->transaction_id,
            'subscriber' => [
                'country' => config('mobile_wallet.country_code', 'ZM'),
                'currency' => config('mobile_wallet.currency', 'ZMW'),
                'msisdn' => $phoneNumber,
            ],
            'transaction' => [
                'amount' => $amount,
                'currency' => config('mobile_wallet.currency', 'ZMW'),
                'id' => (string) Str::uuid(),
                'country' => config('mobile_wallet.country_code', 'ZM'),
            ],
        ];

        // Update transaction with request data
        $transaction->update([
            'raw_request' => $payload,
        ]);

        // Make API request
        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/merchant/v1/payments/", $payload);

        if (! $response->successful()) {
            // Mark transaction as failed
            $transaction->markAsFailed($response->body());

            throw new \Exception('Airtel Money payment request failed: '.$response->body());
        }

        $result = $response->json();

        // Update transaction with provider ID
        $transaction->update([
            'provider_transaction_id' => $result['transaction']['id'] ?? null,
            'raw_response' => $result,
        ]);

        // Check if we need to automatically update the status
        if (isset($result['status']) && strtolower($result['status']) === 'success') {
            $transaction->markAsPaid($result['transaction']['id'] ?? null);
        }

        return [
            'success' => true,
            'transaction_id' => $transaction->transaction_id,
            'provider_transaction_id' => $result['transaction']['id'] ?? null,
            'status' => $transaction->status,
        ];
    }

    /**
     * Check the status of a transaction.
     */
    public function checkTransactionStatus(string $transactionId): array
    {
        // Find the transaction
        $transaction = Transaction::where('transaction_id', $transactionId)
            ->where('provider', $this->provider)
            ->firstOrFail();

        // Get authentication token
        $accessToken = $this->authenticate();

        // Make API request
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/standard/v1/payments/{$transaction->provider_transaction_id}");

        if (! $response->successful()) {
            throw new \Exception('Airtel Money status check failed: '.$response->body());
        }

        $result = $response->json();

        // Update transaction status based on response
        if (isset($result['status'])) {
            switch (strtolower($result['status'])) {
                case 'success':
                case 'successful':
                case 'completed':
                    $transaction->markAsPaid($transaction->provider_transaction_id);
                    break;
                case 'failed':
                case 'cancelled':
                case 'rejected':
                    $transaction->markAsFailed($result['reason'] ?? 'Payment failed');
                    break;
            }
        }

        // Update transaction with response
        $this->updateTransactionWithResponse($transaction, $result);

        return [
            'success' => true,
            'transaction_id' => $transaction->transaction_id,
            'status' => $transaction->status,
            'details' => $result,
        ];
    }

    /**
     * Process a callback/webhook from Airtel.
     */
    public function processCallback(array $payload): array
    {
        // Extract the transaction details from the payload
        $transactionId = $payload['transaction']['id'] ?? null;
        $status = $payload['status'] ?? null;

        if (! $transactionId || ! $status) {
            return [
                'success' => false,
                'message' => 'Invalid payload',
            ];
        }

        // Find the transaction
        $transaction = Transaction::where('provider_transaction_id', $transactionId)
            ->where('provider', $this->provider)
            ->first();

        if (! $transaction) {
            return [
                'success' => false,
                'message' => 'Transaction not found',
            ];
        }

        // Update transaction status based on the callback
        switch (strtolower($status)) {
            case 'success':
            case 'successful':
            case 'completed':
                $transaction->markAsPaid();
                break;
            case 'failed':
            case 'cancelled':
            case 'rejected':
                $transaction->markAsFailed($payload['reason'] ?? 'Payment failed');
                break;
        }

        // Update transaction with callback data
        $this->updateTransactionWithResponse($transaction, $payload);

        return [
            'success' => true,
            'transaction_id' => $transaction->transaction_id,
            'status' => $transaction->status,
        ];
    }
}
