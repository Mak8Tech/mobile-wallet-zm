<?php

namespace Mak8Tech\MobileWalletZm\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mak8Tech\MobileWalletZm\Contracts\SignatureVerifier;

class MTNSignatureVerifier implements SignatureVerifier
{
    /**
     * The secret key used for signature verification
     */
    protected string $secretKey;

    /**
     * Create a new MTN signature verifier instance
     *
     * @return void
     */
    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Verify the signature of an incoming webhook request from MTN
     *
     * Based on MTN Mobile Money API documentation, the signature is calculated
     * using HMAC-SHA256 algorithm with the request body as the message and
     * the API secret key as the secret.
     */
    public function verifySignature(Request $request): bool
    {
        try {
            // Get the signature from the request header
            $signature = $request->header('X-MTN-Signature');

            if (empty($signature)) {
                Log::warning('MTN webhook signature missing');

                return false;
            }

            // Get the request content
            $content = $request->getContent();

            // Calculate the expected signature
            $calculatedSignature = hash_hmac('sha256', $content, $this->secretKey);

            // Verify the signature
            $isValid = hash_equals($calculatedSignature, $signature);

            if (! $isValid) {
                Log::warning('MTN webhook signature verification failed', [
                    'received' => $signature,
                    'calculated' => $calculatedSignature,
                ]);
            }

            return $isValid;
        } catch (\Exception $e) {
            Log::error('Error verifying MTN webhook signature: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return false;
        }
    }
}
