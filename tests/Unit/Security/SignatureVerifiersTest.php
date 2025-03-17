<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Security;

use Illuminate\Http\Request;
use Mak8Tech\MobileWalletZm\Security\MTNSignatureVerifier;
use Mak8Tech\MobileWalletZm\Security\AirtelSignatureVerifier;
use Mak8Tech\MobileWalletZm\Security\ZamtelSignatureVerifier;
use Mak8Tech\MobileWalletZm\Security\SignatureVerifierFactory;
use Mak8Tech\MobileWalletZm\Tests\TestCase;

class SignatureVerifiersTest extends TestCase
{
    /** @test */
    public function mtn_signature_verifier_validates_correct_signature()
    {
        $secretKey = 'test-secret-key';
        $verifier = new MTNSignatureVerifier($secretKey);
        
        $payload = json_encode(['amount' => 100, 'transaction_id' => 'tx123']);
        $signature = hash_hmac('sha256', $payload, $secretKey);
        
        $request = Request::create(
            '/api/mobile-wallet/webhook/mtn',
            'POST',
            [],
            [],
            [],
            ['HTTP_X_MTN_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );
        
        $this->assertTrue($verifier->verifySignature($request));
    }
    
    /** @test */
    public function mtn_signature_verifier_rejects_incorrect_signature()
    {
        $secretKey = 'test-secret-key';
        $verifier = new MTNSignatureVerifier($secretKey);
        
        $payload = json_encode(['amount' => 100, 'transaction_id' => 'tx123']);
        $signature = 'invalid-signature';
        
        $request = Request::create(
            '/api/mobile-wallet/webhook/mtn',
            'POST',
            [],
            [],
            [],
            ['HTTP_X_MTN_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );
        
        $this->assertFalse($verifier->verifySignature($request));
    }
    
    /** @test */
    public function airtel_signature_verifier_validates_correct_signature()
    {
        $clientId = 'test-client-id';
        $secretKey = 'test-secret-key';
        $verifier = new AirtelSignatureVerifier($clientId, $secretKey);
        
        $payload = json_encode(['amount' => 100, 'transaction_id' => 'tx123']);
        $timestamp = (string) time();
        $dataToSign = $timestamp . $clientId . $payload;
        $signature = base64_encode(hash_hmac('sha256', $dataToSign, $secretKey, true));
        
        $request = Request::create(
            '/api/mobile-wallet/webhook/airtel',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_X_AUTH_SIGNATURE' => $signature, 
                'HTTP_X_TIMESTAMP' => $timestamp,
                'CONTENT_TYPE' => 'application/json'
            ],
            $payload
        );
        
        $this->assertTrue($verifier->verifySignature($request));
    }
    
    /** @test */
    public function zamtel_signature_verifier_validates_correct_signature()
    {
        $secretKey = 'test-secret-key';
        $verifier = new ZamtelSignatureVerifier($secretKey);
        
        $transactionId = 'tx123';
        $amount = '100';
        $payload = json_encode(['transaction_id' => $transactionId, 'amount' => $amount]);
        $dataToSign = $transactionId . $amount . $secretKey;
        $signature = hash('sha256', $dataToSign);
        
        $request = Request::create(
            '/api/mobile-wallet/webhook/zamtel',
            'POST',
            [],
            [],
            [],
            ['HTTP_X_ZAMTEL_SIGNATURE' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );
        
        $this->assertTrue($verifier->verifySignature($request));
    }
    
    /** @test */
    public function signature_verifier_factory_creates_correct_verifier_for_provider()
    {
        $config = [
            'api_key' => 'test-api-key',
            'api_secret' => 'test-api-secret'
        ];
        
        $mtnVerifier = SignatureVerifierFactory::create('mtn', $config);
        $this->assertInstanceOf(MTNSignatureVerifier::class, $mtnVerifier);
        
        $airtelVerifier = SignatureVerifierFactory::create('airtel', $config);
        $this->assertInstanceOf(AirtelSignatureVerifier::class, $airtelVerifier);
        
        $zamtelVerifier = SignatureVerifierFactory::create('zamtel', $config);
        $this->assertInstanceOf(ZamtelSignatureVerifier::class, $zamtelVerifier);
    }
    
    /** @test */
    public function signature_verifier_factory_throws_exception_for_invalid_provider()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        SignatureVerifierFactory::create('invalid', []);
    }
} 