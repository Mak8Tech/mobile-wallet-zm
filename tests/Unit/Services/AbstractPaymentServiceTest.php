<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Services;

use Mak8Tech\MobileWalletZm\Services\AbstractPaymentService;
use Mak8Tech\MobileWalletZm\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class AbstractPaymentServiceTest extends TestCase
{
    protected MockInterface $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock of the abstract class
        $this->service = Mockery::mock(AbstractPaymentService::class, ['https://api.example.com', 'api-key', 'api-secret'])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->service->shouldReceive('formatPhoneNumber')->andReturnUsing(function ($number) {
            return preg_replace('/[^0-9]/', '', $number);
        });
    }

    public function test_it_creates_a_transaction(): void
    {
        // Set provider name using reflection
        $reflectionClass = new \ReflectionClass($this->service);
        $reflectionProperty = $reflectionClass->getProperty('provider');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->service, 'test-provider');

        $transaction = $this->service->createTransaction(
            '0977123456',
            100.00,
            'REFERENCE123',
            'Test payment'
        );

        $this->assertEquals('test-provider', $transaction->provider);
        $this->assertEquals('0977123456', $transaction->phone_number);
        $this->assertEquals(100.00, $transaction->amount);
        $this->assertEquals('REFERENCE123', $transaction->reference);
        $this->assertEquals('Test payment', $transaction->narration);
        $this->assertEquals('pending', $transaction->status);
    }

    public function test_it_formats_phone_number_correctly(): void
    {
        // Call the protected method using reflection
        $reflectionClass = new \ReflectionClass($this->service);
        $reflectionMethod = $reflectionClass->getMethod('formatPhoneNumber');
        $reflectionMethod->setAccessible(true);

        // Test various phone number formats
        $this->assertEquals('260977123456', $reflectionMethod->invoke($this->service, '0977123456'));
        $this->assertEquals('260977123456', $reflectionMethod->invoke($this->service, '+260977123456'));
        $this->assertEquals('260977123456', $reflectionMethod->invoke($this->service, '260977123456'));
        $this->assertEquals('260977123456', $reflectionMethod->invoke($this->service, '0977 123 456'));
    }

    public function test_it_gets_http_client(): void
    {
        // Call the protected method using reflection
        $reflectionClass = new \ReflectionClass($this->service);
        $reflectionMethod = $reflectionClass->getMethod('getHttpClient');
        $reflectionMethod->setAccessible(true);

        // Get HTTP client without access token
        $client = $reflectionMethod->invoke($this->service);
        $this->assertInstanceOf(\Illuminate\Http\Client\PendingRequest::class, $client);

        // Get HTTP client with access token
        $clientWithToken = $reflectionMethod->invoke($this->service, 'test-token');
        $this->assertInstanceOf(\Illuminate\Http\Client\PendingRequest::class, $clientWithToken);
    }

    public function test_it_updates_transaction_with_response(): void
    {
        $transaction = $this->createTransaction();
        
        // Call the protected method using reflection
        $reflectionClass = new \ReflectionClass($this->service);
        $reflectionMethod = $reflectionClass->getMethod('updateTransactionWithResponse');
        $reflectionMethod->setAccessible(true);

        $response = ['status' => 'success', 'message' => 'Payment successful'];
        $updatedTransaction = $reflectionMethod->invoke($this->service, $transaction, $response, 'paid');

        $this->assertEquals('paid', $updatedTransaction->status);
        $this->assertEquals($response, $updatedTransaction->raw_response);
    }

    protected function createTransaction(): \Mak8Tech\MobileWalletZm\Models\WalletTransaction
    {
        return \Mak8Tech\MobileWalletZm\Models\WalletTransaction::create([
            'provider' => 'test-provider',
            'phone_number' => '0977123456',
            'amount' => 100.00,
            'currency' => 'ZMW',
            'reference' => 'REFERENCE123',
            'narration' => 'Test payment',
            'status' => 'pending',
        ]);
    }
} 