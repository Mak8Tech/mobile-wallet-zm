<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Services;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mak8Tech\MobileWalletZm\Models\WalletTransaction;
use Mak8Tech\MobileWalletZm\Services\ZamtelService;
use Mak8Tech\MobileWalletZm\Tests\TestCase;

class ZamtelServiceTest extends TestCase
{
    protected ZamtelService $zamtelService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure the Zamtel settings
        config([
            'mobile_wallet.zamtel.base_url' => 'https://api.zamtel.com',
            'mobile_wallet.zamtel.api_key' => 'test-api-key',
            'mobile_wallet.zamtel.api_secret' => 'test-api-secret',
            'mobile_wallet.zamtel.merchant_id' => 'test-merchant-id',
            'mobile_wallet.zamtel.environment' => 'sandbox',
            'mobile_wallet.currency' => 'ZMW',
        ]);
        
        $this->zamtelService = new ZamtelService(
            config('mobile_wallet.zamtel.base_url'),
            config('mobile_wallet.zamtel.api_key'),
            config('mobile_wallet.zamtel.api_secret')
        );
    }
    
    public function test_it_authenticates_successfully(): void
    {
        // Mock the HTTP response for authentication
        Http::fake([
            'https://api.zamtel.com/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);
        
        $accessToken = $this->zamtelService->authenticate();
        
        // Verify the request was sent with the correct headers and data
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.zamtel.com/oauth/token' &&
                   $request->hasHeader('Authorization') &&
                   $request['grant_type'] === 'client_credentials';
        });
        
        $this->assertEquals('test-access-token', $accessToken);
    }
    
    public function test_it_requests_payment_successfully(): void
    {
        // Mock the HTTP responses for authentication and payment request
        Http::fake([
            'https://api.zamtel.com/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://api.zamtel.com/api/payment/request' => Http::response([
                'status' => 'SUCCESS',
                'transactionId' => 'test-provider-transaction-id',
                'message' => 'Payment request initiated',
            ], 200),
        ]);
        
        $response = $this->zamtelService->requestPayment(
            '0977123456',
            100.00,
            'REFERENCE123',
            'Test payment'
        );
        
        // Verify the authentication request
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.zamtel.com/oauth/token';
        });
        
        // Verify the payment request was sent with the correct data
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.zamtel.com/api/payment/request' &&
                   $request->header('Authorization')[0] === 'Bearer test-access-token' &&
                   $request['merchantId'] === 'test-merchant-id' &&
                   $request['reference'] === 'REFERENCE123' &&
                   $request['phoneNumber'] === '260977123456' &&
                   $request['amount'] === 100.00 &&
                   $request['currency'] === 'ZMW';
        });
        
        $this->assertTrue($response['success']);
        $this->assertEquals('pending', $response['status']);
        $this->assertArrayHasKey('transaction_id', $response);
        $this->assertArrayHasKey('provider_transaction_id', $response);
        
        // Verify a transaction was created in the database
        $transaction = WalletTransaction::where('transaction_id', $response['transaction_id'])->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('zamtel', $transaction->provider);
        $this->assertEquals('0977123456', $transaction->phone_number);
        $this->assertEquals(100.00, $transaction->amount);
        $this->assertEquals('REFERENCE123', $transaction->reference);
        $this->assertEquals('Test payment', $transaction->narration);
    }
    
    public function test_it_checks_transaction_status_successfully(): void
    {
        // Create a test transaction
        $transaction = WalletTransaction::create([
            'provider' => 'zamtel',
            'phone_number' => '0977123456',
            'amount' => 100.00,
            'currency' => 'ZMW',
            'reference' => 'REFERENCE123',
            'narration' => 'Test payment',
            'status' => 'pending',
            'transaction_id' => 'test-transaction-id',
            'provider_transaction_id' => 'test-provider-transaction-id',
        ]);
        
        // Mock the HTTP responses
        Http::fake([
            'https://api.zamtel.com/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://api.zamtel.com/api/payment/status/test-provider-transaction-id' => Http::response([
                'status' => 'SUCCESS',
                'transactionId' => 'test-provider-transaction-id',
                'transactionStatus' => 'COMPLETED',
                'amount' => 100.00,
                'currency' => 'ZMW',
            ], 200),
        ]);
        
        $response = $this->zamtelService->checkTransactionStatus('test-transaction-id');
        
        // Verify the authentication request
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.zamtel.com/oauth/token';
        });
        
        // Verify the status check request
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.zamtel.com/api/payment/status/test-provider-transaction-id' &&
                   $request->header('Authorization')[0] === 'Bearer test-access-token';
        });
        
        $this->assertTrue($response['success']);
        $this->assertEquals('paid', $response['status']);
        $this->assertEquals('test-transaction-id', $response['transaction_id']);
        
        // Verify the transaction was updated
        $transaction->refresh();
        $this->assertEquals('paid', $transaction->status);
    }
    
    public function test_it_processes_callback_successfully(): void
    {
        // Create a test transaction
        $transaction = WalletTransaction::create([
            'provider' => 'zamtel',
            'phone_number' => '0977123456',
            'amount' => 100.00,
            'currency' => 'ZMW',
            'reference' => 'REFERENCE123',
            'narration' => 'Test payment',
            'status' => 'pending',
            'transaction_id' => 'test-transaction-id',
            'provider_transaction_id' => 'test-provider-transaction-id',
        ]);
        
        // Prepare callback payload
        $payload = [
            'transactionId' => 'test-provider-transaction-id',
            'status' => 'COMPLETED',
            'amount' => 100.00,
            'currency' => 'ZMW',
            'reference' => 'REFERENCE123',
        ];
        
        $response = $this->zamtelService->processCallback($payload);
        
        $this->assertTrue($response['success']);
        $this->assertEquals('test-transaction-id', $response['transaction_id']);
        $this->assertEquals('paid', $response['status']);
        
        // Verify the transaction was updated
        $transaction->refresh();
        $this->assertEquals('paid', $transaction->status);
        $this->assertNotNull($transaction->paid_at);
    }
    
    public function test_it_handles_failed_payment_in_callback(): void
    {
        // Create a test transaction
        $transaction = WalletTransaction::create([
            'provider' => 'zamtel',
            'phone_number' => '0977123456',
            'amount' => 100.00,
            'currency' => 'ZMW',
            'reference' => 'REFERENCE123',
            'narration' => 'Test payment',
            'status' => 'pending',
            'transaction_id' => 'test-transaction-id',
            'provider_transaction_id' => 'test-provider-transaction-id',
        ]);
        
        // Prepare callback payload
        $payload = [
            'transactionId' => 'test-provider-transaction-id',
            'status' => 'FAILED',
            'errorMessage' => 'Insufficient funds',
            'amount' => 100.00,
            'currency' => 'ZMW',
            'reference' => 'REFERENCE123',
        ];
        
        $response = $this->zamtelService->processCallback($payload);
        
        $this->assertTrue($response['success']);
        $this->assertEquals('failed', $response['status']);
        
        // Verify the transaction was updated
        $transaction->refresh();
        $this->assertEquals('failed', $transaction->status);
        $this->assertNotNull($transaction->failed_at);
        $this->assertEquals('Insufficient funds', $transaction->message);
    }
} 