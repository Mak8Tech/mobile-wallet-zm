<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Services;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mak8Tech\MobileWalletZm\Models\WalletTransaction;
use Mak8Tech\MobileWalletZm\Services\MTNService;
use Mak8Tech\MobileWalletZm\Tests\TestCase;

class MTNServiceTest extends TestCase
{
    protected MTNService $mtnService;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure the MTN settings
        config([
            'mobile_wallet.mtn.base_url' => 'https://api.mtn.com',
            'mobile_wallet.mtn.api_key' => 'test-api-key',
            'mobile_wallet.mtn.api_secret' => 'test-api-secret',
            'mobile_wallet.mtn.collection_subscription_key' => 'test-subscription-key',
            'mobile_wallet.mtn.environment' => 'sandbox',
            'mobile_wallet.currency' => 'ZMW',
        ]);

        $this->mtnService = new MTNService(
            config('mobile_wallet.mtn.base_url'),
            config('mobile_wallet.mtn.api_key'),
            config('mobile_wallet.mtn.api_secret')
        );
    }

    public function test_it_authenticates_successfully(): void
    {
        // Mock the HTTP response for authentication
        Http::fake([
            'https://api.mtn.com/collection/token/' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $accessToken = $this->mtnService->authenticate();

        // Verify the request was sent with the correct headers and data
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.mtn.com/collection/token/' &&
                   $request->header('Ocp-Apim-Subscription-Key')[0] === 'test-subscription-key' &&
                   $request->hasHeader('Authorization') &&
                   $request['grant_type'] === 'client_credentials';
        });

        $this->assertEquals('test-access-token', $accessToken);
    }

    public function test_it_requests_payment_successfully(): void
    {
        // Mock the HTTP responses for authentication and payment request
        Http::fake([
            'https://api.mtn.com/collection/token/' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://api.mtn.com/collection/v1_0/requesttopay' => Http::response([], 202),
        ]);

        $response = $this->mtnService->requestPayment(
            '0977123456',
            100.00,
            'REFERENCE123',
            'Test payment'
        );

        // Verify the authentication request
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.mtn.com/collection/token/';
        });

        // Verify the payment request was sent with the correct data
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.mtn.com/collection/v1_0/requesttopay' &&
                   $request->header('Authorization')[0] === 'Bearer test-access-token' &&
                   $request->header('X-Target-Environment')[0] === 'sandbox' &&
                   $request->header('Ocp-Apim-Subscription-Key')[0] === 'test-subscription-key' &&
                   $request->hasHeader('X-Reference-Id') &&
                   $request['amount'] === '100' &&
                   $request['currency'] === 'ZMW' &&
                   $request['payer']['partyId'] === '260977123456';
        });

        $this->assertTrue($response['success']);
        $this->assertEquals('pending', $response['status']);
        $this->assertArrayHasKey('transaction_id', $response);
        $this->assertArrayHasKey('provider_transaction_id', $response);

        // Verify a transaction was created in the database
        $transaction = WalletTransaction::where('transaction_id', $response['transaction_id'])->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('mtn', $transaction->provider);
        $this->assertEquals('0977123456', $transaction->phone_number);
        $this->assertEquals(100.00, $transaction->amount);
        $this->assertEquals('REFERENCE123', $transaction->reference);
        $this->assertEquals('Test payment', $transaction->narration);
    }

    public function test_it_checks_transaction_status_successfully(): void
    {
        // Create a test transaction
        $transaction = WalletTransaction::create([
            'provider' => 'mtn',
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
            'https://api.mtn.com/collection/token/' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://api.mtn.com/collection/v1_0/requesttopay/test-provider-transaction-id' => Http::response([
                'status' => 'SUCCESSFUL',
                'amount' => '100',
                'currency' => 'ZMW',
                'payerMessage' => 'Test payment',
                'payeeNote' => 'REFERENCE123',
            ], 200),
        ]);

        $response = $this->mtnService->checkTransactionStatus('test-transaction-id');

        // Verify the authentication request
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.mtn.com/collection/token/';
        });

        // Verify the status check request
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.mtn.com/collection/v1_0/requesttopay/test-provider-transaction-id' &&
                   $request->header('Authorization')[0] === 'Bearer test-access-token' &&
                   $request->header('X-Target-Environment')[0] === 'sandbox' &&
                   $request->header('Ocp-Apim-Subscription-Key')[0] === 'test-subscription-key';
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
            'provider' => 'mtn',
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
            'referenceId' => 'test-provider-transaction-id',
            'status' => 'SUCCESSFUL',
            'amount' => '100',
            'currency' => 'ZMW',
            'payerMessage' => 'Test payment',
            'payeeNote' => 'REFERENCE123',
        ];

        $response = $this->mtnService->processCallback($payload);

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
            'provider' => 'mtn',
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
            'referenceId' => 'test-provider-transaction-id',
            'status' => 'FAILED',
            'reason' => 'Insufficient funds',
            'amount' => '100',
            'currency' => 'ZMW',
        ];

        $response = $this->mtnService->processCallback($payload);

        $this->assertTrue($response['success']);
        $this->assertEquals('failed', $response['status']);

        // Verify the transaction was updated
        $transaction->refresh();
        $this->assertEquals('failed', $transaction->status);
        $this->assertNotNull($transaction->failed_at);
        $this->assertEquals('Insufficient funds', $transaction->message);
    }
}
