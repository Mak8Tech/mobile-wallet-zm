<?php

namespace Mak8Tech\MobileWalletZm\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mak8Tech\MobileWalletZm\Models\WalletTransaction as Transaction;

class ZamtelService extends AbstractPaymentService
{
    /**
     * The API environment.
     */
    protected string $environment;

    /**
     * The provider name.
     */
    protected string $provider = 'zamtel';

    /**
     * Create a new Zamtel service instance.
     */
    public function __construct(
        string $baseUrl,
        string $apiKey,
        string $apiSecret
    ) {
        parent::__construct($baseUrl, $apiKey, $apiSecret);

        $this->environment = config('mobile_wallet.zamtel.environment', 'sandbox');
    }

    /**
     * Authenticate with Zamtel Kwacha API.
     */
    public function authenticate(): string
    {
        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->post("{$this->baseUrl}/auth/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Zamtel Kwacha authentication failed: ' . $response->body());
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
            'phone_number' => $phoneNumber,
            'amount' => (string) $amount,
            'currency' => config('mobile_wallet.currency', 'ZMW'),
            'reference' => $transaction->transaction_id,
            'callback_url' => config('mobile_wallet.callback_url'),
            'narration' => $narration ?? "Payment of {$amount} " . config('mobile_wallet.currency', 'ZMW'),
        ];

        // Update transaction with request data
        $transaction->update([
            'raw_request' => $payload,
        ]);

        // Make API request
        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/payments/request", $payload);

        if (!$response->successful()) {
            // Mark transaction as failed
            $transaction->markAsFailed($response->body());

            throw new \Exception('Zamtel Kwacha payment request failed: ' . $response->body());
        }

        $result = $response->json();

        // Update transaction with provider ID
        $transaction->update([
            'provider_transaction_id' => $result['transaction_id'] ?? null,
            'raw_response' => $result,
        ]);

        return [
            'success' => true,
            'transaction_id' => $transaction->transaction_id,
            'provider_transaction_id' => $result['transaction_id'] ?? null,
            'status' => 'pending',
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
            ->get("{$this->baseUrl}/payments/status/{$transaction->provider_transaction_id}");

        if (!$response->successful()) {
            throw new \Exception('Zamtel Kwacha status check failed: ' . $response->body());
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
            'details' => $result
        ];
    }

    /**
     * Process a callback/webhook from Zamtel.
     */
    public function processCallback(array $payload): array
    {
        // Extract the transaction details from the payload
        $transactionId = $payload['transaction_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$transactionId || !$status) {
            return [
                'success' => false,
                'message' => 'Invalid payload',
            ];
        }

        // Find the transaction
        $transaction = Transaction::where('provider_transaction_id', $transactionId)
            ->where('provider', $this->provider)
            ->first();

        if (!$transaction) {
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
