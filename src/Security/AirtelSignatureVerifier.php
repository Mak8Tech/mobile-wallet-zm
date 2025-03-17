<?php

namespace Mak8Tech\MobileWalletZm\Security;

use Illuminate\Http\Request;
use Mak8Tech\MobileWalletZm\Contracts\SignatureVerifier;
use Illuminate\Support\Facades\Log;

class AirtelSignatureVerifier implements SignatureVerifier
{
    /**
     * The client ID for Airtel API
     * 
     * @var string
     */
    protected string $clientId;
    
    /**
     * The secret key used for signature verification
     * 
     * @var string
     */
    protected string $secretKey;

    /**
     * Create a new Airtel signature verifier instance
     * 
     * @param string $clientId
     * @param string $secretKey
     * @return void
     */
    public function __construct(string $clientId, string $secretKey)
    {
        $this->clientId = $clientId;
        $this->secretKey = $secretKey;
    }

    /**
     * Verify the signature of an incoming webhook request from Airtel
     * 
     * Based on Airtel Money API documentation, the signature is calculated
     * using HMAC-SHA256 algorithm with a specific format that includes
     * timestamp, client ID, and request body.
     * 
     * @param Request $request
     * @return bool
     */
    public function verifySignature(Request $request): bool
    {
        try {
            // Get the signature from the request header
            $signature = $request->header('X-Auth-Signature');
            $timestamp = $request->header('X-Timestamp');
            
            if (empty($signature) || empty($timestamp)) {
                Log::warning('Airtel webhook signature or timestamp missing');
                return false;
            }
            
            // Get the request content
            $content = $request->getContent();
            
            // Format the data to be signed
            $dataToSign = $timestamp . $this->clientId . $content;
            
            // Calculate the expected signature
            $calculatedSignature = base64_encode(
                hash_hmac('sha256', $dataToSign, $this->secretKey, true)
            );
            
            // Verify the signature
            $isValid = hash_equals($calculatedSignature, $signature);
            
            if (!$isValid) {
                Log::warning('Airtel webhook signature verification failed', [
                    'received' => $signature,
                    'calculated' => $calculatedSignature
                ]);
            }
            
            return $isValid;
        } catch (\Exception $e) {
            Log::error('Error verifying Airtel webhook signature: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return false;
        }
    }
} 