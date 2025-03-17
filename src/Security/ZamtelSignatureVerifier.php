<?php

namespace Mak8Tech\MobileWalletZm\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mak8Tech\MobileWalletZm\Contracts\SignatureVerifier;

class ZamtelSignatureVerifier implements SignatureVerifier
{
    /**
     * The secret key used for signature verification
     */
    protected string $secretKey;

    /**
     * Create a new Zamtel signature verifier instance
     *
     * @return void
     */
    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Verify the signature of an incoming webhook request from Zamtel
     *
     * Based on Zamtel Kwacha API documentation, the signature is calculated
     * using SHA256 algorithm with a specific format that includes
     * the transaction ID and amount concatenated with the secret key.
     */
    public function verifySignature(Request $request): bool
    {
        try {
            // Get the signature from the request header
            $signature = $request->header('X-Zamtel-Signature');

            if (empty($signature)) {
                Log::warning('Zamtel webhook signature missing');

                return false;
            }

            // Get the transaction details from the request
            $data = $request->json()->all();
            $transactionId = $data['transaction_id'] ?? '';
            $amount = $data['amount'] ?? '';

            if (empty($transactionId) || empty($amount)) {
                Log::warning('Zamtel webhook missing required fields for signature verification');

                return false;
            }

            // Format the data to be signed
            $dataToSign = $transactionId.$amount.$this->secretKey;

            // Calculate the expected signature
            $calculatedSignature = hash('sha256', $dataToSign);

            // Verify the signature
            $isValid = hash_equals($calculatedSignature, $signature);

            if (! $isValid) {
                Log::warning('Zamtel webhook signature verification failed', [
                    'received' => $signature,
                    'calculated' => $calculatedSignature,
                ]);
            }

            return $isValid;
        } catch (\Exception $e) {
            Log::error('Error verifying Zamtel webhook signature: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return false;
        }
    }
}
