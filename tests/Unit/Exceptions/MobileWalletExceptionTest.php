<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Exceptions;

use Mak8Tech\MobileWalletZm\Exceptions\AuthenticationException;
use Mak8Tech\MobileWalletZm\Exceptions\InvalidTransactionException;
use Mak8Tech\MobileWalletZm\Exceptions\MobileWalletException;
use Mak8Tech\MobileWalletZm\Exceptions\PaymentRequestException;
use Mak8Tech\MobileWalletZm\Exceptions\PaymentStatusException;
use Mak8Tech\MobileWalletZm\Exceptions\WebhookException;
use Mak8Tech\MobileWalletZm\Tests\TestCase;

class MobileWalletExceptionTest extends TestCase
{
    public function test_base_exception_has_correct_properties(): void
    {
        $exception = new MobileWalletException(
            'Test message',
            'test_error',
            'mtn',
            ['test' => 'data'],
            123
        );

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals('test_error', $exception->getErrorCode());
        $this->assertEquals('mtn', $exception->getProvider());
        $this->assertEquals(['test' => 'data'], $exception->getRawResponse());
        $this->assertEquals(123, $exception->getCode());
    }

    public function test_base_exception_converts_to_array(): void
    {
        $exception = new MobileWalletException(
            'Test message',
            'test_error',
            'mtn',
            ['test' => 'data']
        );

        $array = $exception->toArray();

        $this->assertFalse($array['success']);
        $this->assertEquals('test_error', $array['error']['code']);
        $this->assertEquals('Test message', $array['error']['message']);
        $this->assertEquals('mtn', $array['error']['provider']);
        $this->assertEquals(['test' => 'data'], $array['error']['details']);
    }

    public function test_base_exception_converts_to_json(): void
    {
        $exception = new MobileWalletException(
            'Test message',
            'test_error',
            'mtn',
            ['test' => 'data']
        );

        $json = $exception->toJson();
        $decoded = json_decode($json, true);

        $this->assertFalse($decoded['success']);
        $this->assertEquals('test_error', $decoded['error']['code']);
        $this->assertEquals('Test message', $decoded['error']['message']);
        $this->assertEquals('mtn', $decoded['error']['provider']);
        $this->assertEquals(['test' => 'data'], $decoded['error']['details']);
    }

    public function test_authentication_exception_has_correct_properties(): void
    {
        $exception = new AuthenticationException(
            'Authentication failed',
            'mtn',
            ['error' => 'Invalid credentials']
        );

        $this->assertEquals('Authentication failed', $exception->getMessage());
        $this->assertEquals('authentication_failed', $exception->getErrorCode());
        $this->assertEquals('mtn', $exception->getProvider());
        $this->assertEquals(['error' => 'Invalid credentials'], $exception->getRawResponse());
        $this->assertEquals(401, $exception->getCode());
    }

    public function test_payment_request_exception_has_correct_properties(): void
    {
        $exception = new PaymentRequestException(
            'Payment request failed',
            'airtel',
            ['error' => 'Insufficient funds']
        );

        $this->assertEquals('Payment request failed', $exception->getMessage());
        $this->assertEquals('payment_request_failed', $exception->getErrorCode());
        $this->assertEquals('airtel', $exception->getProvider());
        $this->assertEquals(['error' => 'Insufficient funds'], $exception->getRawResponse());
        $this->assertEquals(400, $exception->getCode());
    }

    public function test_payment_status_exception_has_correct_properties(): void
    {
        $exception = new PaymentStatusException(
            'Payment status check failed',
            'zamtel',
            ['error' => 'Transaction not found']
        );

        $this->assertEquals('Payment status check failed', $exception->getMessage());
        $this->assertEquals('payment_status_failed', $exception->getErrorCode());
        $this->assertEquals('zamtel', $exception->getProvider());
        $this->assertEquals(['error' => 'Transaction not found'], $exception->getRawResponse());
        $this->assertEquals(400, $exception->getCode());
    }

    public function test_invalid_transaction_exception_has_correct_properties(): void
    {
        $exception = new InvalidTransactionException(
            'Invalid transaction data',
            'mtn',
            ['transaction_id' => 'invalid']
        );

        $this->assertEquals('Invalid transaction data', $exception->getMessage());
        $this->assertEquals('invalid_transaction', $exception->getErrorCode());
        $this->assertEquals('mtn', $exception->getProvider());
        $this->assertEquals(['transaction_id' => 'invalid'], $exception->getRawResponse());
        $this->assertEquals(400, $exception->getCode());
    }

    public function test_webhook_exception_has_correct_properties(): void
    {
        $exception = new WebhookException(
            'Webhook processing failed',
            'airtel',
            ['payload' => 'invalid']
        );

        $this->assertEquals('Webhook processing failed', $exception->getMessage());
        $this->assertEquals('webhook_error', $exception->getErrorCode());
        $this->assertEquals('airtel', $exception->getProvider());
        $this->assertEquals(['payload' => 'invalid'], $exception->getRawResponse());
        $this->assertEquals(400, $exception->getCode());
    }
}
