<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Facades;

use Mak8Tech\MobileWalletZm\Facades\MobileWallet;
use Mak8Tech\MobileWalletZm\MobileWalletManager;
use Mak8Tech\MobileWalletZm\Tests\TestCase;

class MobileWalletTest extends TestCase
{
    public function test_facade_resolves_to_manager_instance(): void
    {
        $this->assertInstanceOf(MobileWalletManager::class, MobileWallet::getFacadeRoot());
    }

    public function test_facade_forwards_calls_to_manager(): void
    {
        // Mock the manager
        $managerMock = $this->mock(MobileWalletManager::class);

        // Set expectations for the manager
        $managerMock->shouldReceive('getDefaultProvider')
            ->once()
            ->andReturn('mtn');

        // Replace the facade root
        MobileWallet::swap($managerMock);

        // Test the facade call
        $result = MobileWallet::getDefaultProvider();
        $this->assertEquals('mtn', $result);
    }

    public function test_facade_forwards_provider_method_calls(): void
    {
        // Mock the manager
        $managerMock = $this->mock(MobileWalletManager::class);

        // Set expectations for provider method call
        $managerMock->shouldReceive('provider')
            ->with('mtn')
            ->once()
            ->andReturnSelf();

        $managerMock->shouldReceive('authenticate')
            ->once()
            ->andReturn('test-token');

        // Replace the facade root
        MobileWallet::swap($managerMock);

        // Test the chained call through the facade
        $result = MobileWallet::provider('mtn')->authenticate();
        $this->assertEquals('test-token', $result);
    }

    public function test_facade_forwards_dynamic_calls_to_default_provider(): void
    {
        // Mock the manager
        $managerMock = $this->mock(MobileWalletManager::class);

        // Set expectations for the __call method
        $managerMock->shouldReceive('requestPayment')
            ->with('0977123456', 100.00, 'REFERENCE123', 'Test payment')
            ->once()
            ->andReturn(['success' => true]);

        // Replace the facade root
        MobileWallet::swap($managerMock);

        // Test the dynamic call through the facade
        $result = MobileWallet::requestPayment('0977123456', 100.00, 'REFERENCE123', 'Test payment');
        $this->assertTrue($result['success']);
    }
}
