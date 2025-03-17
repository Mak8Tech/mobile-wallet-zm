<?php

namespace Mak8Tech\MobileWalletZm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mak8Tech\MobileWalletZm\Facades\MobileWallet;
use Mak8Tech\MobileWalletZm\Models\WalletTransaction;
use Mak8Tech\MobileWalletZm\Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure the providers
        config([
            'mobile_wallet.default' => 'mtn',
            'mobile_wallet.mtn.base_url' => 'https://api.mtn.com',
            'mobile_wallet.mtn.api_key' => 'test-api-key',
            'mobile_wallet.mtn.api_secret' => 'test-api-secret',
            'mobile_wallet.mtn.collection_subscription_key' => 'test-subscription-key',
            'mobile_wallet.mtn.environment' => 'sandbox',
            'mobile_wallet.currency' => 'ZMW',
        ]);
    }

    public function test_complete_mtn_payment_flow(): void
    {
        // 1. Mock HTTP responses
        Http::fake([
            // Authentication response
            'https://api.mtn.com/collection/token/' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            
            // Payment request response
            'https://api.mtn.com/collection/v1_0/requesttopay' => Http::response([], 202),
            
            // Status check response
            'https://api.mtn.com/collection/v1_0/requesttopay/*' => Http::response([
                'status' => 'SUCCESSFUL',
                'amount' => '100',
                'currency' => 'ZMW',
                'payerMessage' => 'Test payment',
                'payeeNote' => 'Test reference',
            ], 200),
        ]);

        // 2. Request a payment using the facade
        $response = MobileWallet::requestPayment(
            '0977123456',
            100.00,
            'Test reference',
            'Test payment'
        );

        // 3. Verify the payment request response
        $this->assertTrue($response['success']);
        $this->assertEquals('pending', $response['status']);
        $this->assertArrayHasKey('transaction_id', $response);
        
        // Get the transaction ID for subsequent checks
        $transactionId = $response['transaction_id'];

        // 4. Verify a transaction record was created in the database
        $transaction = WalletTransaction::where('transaction_id', $transactionId)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('mtn', $transaction->provider);
        $this->assertEquals('0977123456', $transaction->phone_number);
        $this->assertEquals(100.00, $transaction->amount);
        $this->assertEquals('Test reference', $transaction->reference);
        $this->assertEquals('Test payment', $transaction->narration);
        $this->assertEquals('pending', $transaction->status);

        // 5. Check the transaction status
        $statusResponse = MobileWallet::checkTransactionStatus($transactionId);
        $this->assertTrue($statusResponse['success']);
        $this->assertEquals('paid', $statusResponse['status']);
        
        // 6. Verify the transaction was updated
        $transaction->refresh();
        $this->assertEquals('paid', $transaction->status);
        $this->assertNotNull($transaction->paid_at);

        // 7. Simulate a webhook callback
        $callbackPayload = [
            'referenceId' => $transaction->provider_transaction_id,
            'status' => 'SUCCESSFUL',
            'amount' => '100',
            'currency' => 'ZMW',
        ];

        $callbackResponse = MobileWallet::provider('mtn')->processCallback($callbackPayload);
        $this->assertTrue($callbackResponse['success']);
        $this->assertEquals('paid', $callbackResponse['status']);
        
        // 8. Test the webhook endpoint
        $response = $this->postJson(route('mobile-wallet.webhook', ['provider' => 'mtn']), $callbackPayload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_payment_can_be_initiated_via_controller(): void
    {
        // 1. Mock HTTP responses
        Http::fake([
            // Authentication response
            'https://api.mtn.com/collection/token/' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            
            // Payment request response
            'https://api.mtn.com/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        // 2. Make a request to the payment controller
        $response = $this->postJson(route('mobile-wallet.payment'), [
            'provider' => 'mtn',
            'phone_number' => '0977123456',
            'amount' => 100.00,
            'reference' => 'Test reference',
            'narration' => 'Test payment',
        ]);
        
        // 3. Verify the response
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'transaction_id',
            'provider_transaction_id',
            'status',
        ]);
        
        // 4. Verify a transaction was created
        $responseData = $response->json();
        $transaction = WalletTransaction::where('transaction_id', $responseData['transaction_id'])->first();
        $this->assertNotNull($transaction);
    }
} 