<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Services;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mak8Tech\MobileWalletZm\Models\WalletTransaction;
use Mak8Tech\MobileWalletZm\Services\AirtelService;
use Mak8Tech\MobileWalletZm\Tests\TestCase;

class AirtelServiceTest extends TestCase
{
    protected AirtelService $airtelService;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure the Airtel settings
        config([
            'mobile_wallet.airtel.base_url' => 'https://api.airtel.com',
            'mobile_wallet.airtel.api_key' => 'test-api-key',
            'mobile_wallet.airtel.api_secret' => 'test-api-secret',
            'mobile_wallet.airtel.client_id' => 'test-client-id',
            'mobile_wallet.airtel.environment' => 'sandbox',
            'mobile_wallet.currency' => 'ZMW',
        ]);

        $this->airtelService = new AirtelService(
            config('mobile_wallet.airtel.base_url'),
            config('mobile_wallet.airtel.api_key'),
            config('mobile_wallet.airtel.api_secret')
        );
    }

    public function test_it_authenticates_successfully(): void
    {
        // Mock the HTTP response for authentication
        Http::fake([
            'https://api.airtel.com/auth/oauth2/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $accessToken = $this->airtelService->authenticate();

        // Verify the request was sent with the correct headers and data
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.airtel.com/auth/oauth2/token' &&
                   $request->hasHeader('Authorization') &&
                   $request['grant_type'] === 'client_credentials';
        });

        $this->assertEquals('test-access-token', $accessToken);
    }

    public function test_it_requests_payment_successfully(): void
    {
        // Mock the HTTP responses for authentication and payment request
        Http::fake([
            'https://api.airtel.com/auth/oauth2/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://api.airtel.com/merchant/v1/payments/' => Http::response([
                'status' => 'SUCCESS',
                'transaction' => [
                    'id' => 'test-provider-transaction-id',
                    'status' => 'PENDING',
                ],
            ], 200),
        ]);

        $response = $this->airtelService->requestPayment(
            '0977123456',
            100.00,
            'REFERENCE123',
            'Test payment'
        );

        // Verify the authentication request
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.airtel.com/auth/oauth2/token';
        });

        // Verify the payment request was sent with the correct data
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.airtel.com/merchant/v1/payments/' &&
                   $request->header('Authorization')[0] === 'Bearer test-access-token' &&
                   $request['reference'] === 'REFERENCE123' &&
                   $request['subscriber']['country'] === 'ZM' &&
                   $request['subscriber']['currency'] === 'ZMW' &&
                   $request['subscriber']['msisdn'] === '260977123456' &&
                   $request['transaction']['amount'] === 100.00;
        });

        $this->assertTrue($response['success']);
        $this->assertEquals('pending', $response['status']);
        $this->assertArrayHasKey('transaction_id', $response);
        $this->assertArrayHasKey('provider_transaction_id', $response);

        // Verify a transaction was created in the database
        $transaction = WalletTransaction::where('transaction_id', $response['transaction_id'])->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('airtel', $transaction->provider);
        $this->assertEquals('0977123456', $transaction->phone_number);
        $this->assertEquals(100.00, $transaction->amount);
        $this->assertEquals('REFERENCE123', $transaction->reference);
        $this->assertEquals('Test payment', $transaction->narration);
    }

    public function test_it_checks_transaction_status_successfully(): void
    {
        // Create a test transaction
        $transaction = WalletTransaction::create([
            'provider' => 'airtel',
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
            'https://api.airtel.com/auth/oauth2/token' => Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://api.airtel.com/standard/v1/payments/test-provider-transaction-id' => Http::response([
                'status' => 'SUCCESS',
                'transaction' => [
                    'id' => 'test-provider-transaction-id',
                    'status' => 'SUCCESSFUL',
                    'amount' => 100.00,
                    'currency' => 'ZMW',
                ],
            ], 200),
        ]);

        $response = $this->airtelService->checkTransactionStatus('test-transaction-id');

        // Verify the authentication request
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.airtel.com/auth/oauth2/token';
        });

        // Verify the status check request
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.airtel.com/standard/v1/payments/test-provider-transaction-id' &&
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
            'provider' => 'airtel',
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
            'transaction' => [
                'id' => 'test-provider-transaction-id',
                'status' => 'SUCCESSFUL',
                'amount' => 100.00,
                'currency' => 'ZMW',
            ],
            'status' => 'SUCCESS',
        ];

        $response = $this->airtelService->processCallback($payload);

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
            'provider' => 'airtel',
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
            'transaction' => [
                'id' => 'test-provider-transaction-id',
                'status' => 'FAILED',
                'amount' => 100.00,
                'currency' => 'ZMW',
            ],
            'status' => 'FAILED',
            'message' => 'Insufficient funds',
        ];

        $response = $this->airtelService->processCallback($payload);

        $this->assertTrue($response['success']);
        $this->assertEquals('failed', $response['status']);

        // Verify the transaction was updated
        $transaction->refresh();
        $this->assertEquals('failed', $transaction->status);
        $this->assertNotNull($transaction->failed_at);
        $this->assertEquals('Insufficient funds', $transaction->message);
    }
}
