<?php

namespace Mak8Tech\MobileWalletZm\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mak8Tech\MobileWalletZm\Models\WalletTransaction as Transaction;

class MTNService extends AbstractPaymentService
{
    /**
     * The collection subscription key.
     */
    protected string $collectionSubscriptionKey;

    /**
     * The API environment.
     */
    protected string $environment;

    /**
     * The provider name.
     */
    protected string $provider = 'mtn';

    /**
     * Create a new MTN service instance.
     */
    public function __construct(
        string $baseUrl,
        string $apiKey,
        string $apiSecret
    ) {
        parent::__construct($baseUrl, $apiKey, $apiSecret);

        $this->collectionSubscriptionKey = config('mobile_wallet.mtn.collection_subscription_key');
        $this->environment = config('mobile_wallet.mtn.environment', 'sandbox');
    }

    /**
     * Authenticate with MTN MoMo API.
     */
    public function authenticate(): string
    {
        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->collectionSubscriptionKey,
        ])->withBasicAuth($this->apiKey, $this->apiSecret)
            ->post("{$this->baseUrl}/collection/token/", [
                'grant_type' => 'client_credentials'
            ]);

        if (!$response->successful()) {
            throw new \Exception('MTN MoMo authentication failed: ' . $response->body());
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

        // Create a UUID for the transaction
        $externalTransactionId = (string) Str::uuid();

        // Prepare request payload
        $payload = [
            "amount" => (string) $amount,
            "currency" => config('mobile_wallet.currency', 'ZMW'),
            "externalId" => $transaction->transaction_id,
            "payer" => [
                "partyIdType" => "MSISDN",
                "partyId" => $phoneNumber
            ],
            "payerMessage" => $narration ?? "Payment",
            "payeeNote" => $reference ?? "Payment of {$amount} " . config('mobile_wallet.currency', 'ZMW')
        ];

        // Update transaction with request data
        $transaction->update([
            'raw_request' => $payload,
        ]);

        // Make API request
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'X-Reference-Id' => $externalTransactionId,
            'X-Target-Environment' => $this->environment,
            'Ocp-Apim-Subscription-Key' => $this->collectionSubscriptionKey,
        ])->post("{$this->baseUrl}/collection/v1_0/requesttopay", $payload);

        if (!$response->successful()) {
            // Mark transaction as failed
            $transaction->markAsFailed($response->body());

            throw new \Exception('MTN MoMo payment request failed: ' . $response->body());
        }

        // Update transaction with provider ID
        $transaction->update([
            'provider_transaction_id' => $externalTransactionId,
            'raw_response' => [
                'status_code' => $response->status(),
                'headers' => $response->headers(),
            ]
        ]);

        return [
            'success' => true,
            'transaction_id' => $transaction->transaction_id,
            'provider_transaction_id' => $externalTransactionId,
            'status' => 'pending'
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
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'X-Target-Environment' => $this->environment,
            'Ocp-Apim-Subscription-Key' => $this->collectionSubscriptionKey,
        ])->get("{$this->baseUrl}/collection/v1_0/requesttopay/{$transaction->provider_transaction_id}");

        if (!$response->successful()) {
            throw new \Exception('MTN MoMo status check failed: ' . $response->body());
        }

        $result = $response->json();

        // Update transaction status based on response
        if (isset($result['status'])) {
            switch (strtolower($result['status'])) {
                case 'successful':
                    $transaction->markAsPaid($transaction->provider_transaction_id);
                    break;
                case 'failed':
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
     * Process a callback/webhook from MTN.
     */
    public function processCallback(array $payload): array
    {
        // Extract the transaction details from the payload
        $externalTransactionId = $payload['referenceId'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$externalTransactionId || !$status) {
            return [
                'success' => false,
                'message' => 'Invalid payload',
            ];
        }

        // Find the transaction
        $transaction = Transaction::where('provider_transaction_id', $externalTransactionId)
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
