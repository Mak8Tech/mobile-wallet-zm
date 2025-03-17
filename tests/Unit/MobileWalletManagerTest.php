<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit;

use Illuminate\Contracts\Foundation\Application;
use Mak8Tech\MobileWalletZm\MobileWalletManager;
use Mak8Tech\MobileWalletZm\Services\AirtelService;
use Mak8Tech\MobileWalletZm\Services\MTNService;
use Mak8Tech\MobileWalletZm\Services\ZamtelService;
use Mak8Tech\MobileWalletZm\Tests\TestCase;
use Mockery;

class MobileWalletManagerTest extends TestCase
{
    protected MobileWalletManager $manager;
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Application
        $this->app = Mockery::mock(Application::class);

        // Set up config expectations
        $this->app->shouldReceive('make')
            ->with(MTNService::class)
            ->andReturn(Mockery::mock(MTNService::class));

        $this->app->shouldReceive('make')
            ->with(AirtelService::class)
            ->andReturn(Mockery::mock(AirtelService::class));

        $this->app->shouldReceive('make')
            ->with(ZamtelService::class)
            ->andReturn(Mockery::mock(ZamtelService::class));

        // Create the manager
        $this->manager = new MobileWalletManager($this->app);
    }

    public function test_it_gets_default_provider(): void
    {
        // Mock the config
        $this->app->shouldReceive('offsetGet')
            ->with('config')
            ->andReturn(['mobile_wallet.default' => 'mtn']);

        $this->assertEquals('mtn', $this->manager->getDefaultProvider());
    }

    public function test_it_sets_default_provider(): void
    {
        $this->manager->setDefaultProvider('airtel');
        $this->assertEquals('airtel', $this->manager->getDefaultProvider());
    }

    public function test_it_resolves_mtn_provider(): void
    {
        // Set up config expectations
        $this->app->shouldReceive('offsetGet')
            ->with('config')
            ->andReturn([
                'mobile_wallet.mtn' => [
                    'base_url' => 'https://api.mtn.com',
                    'api_key' => 'test-api-key',
                    'api_secret' => 'test-api-secret',
                ],
            ]);

        $provider = $this->manager->provider('mtn');
        $this->assertInstanceOf(MTNService::class, $provider);
    }

    public function test_it_resolves_airtel_provider(): void
    {
        // Set up config expectations
        $this->app->shouldReceive('offsetGet')
            ->with('config')
            ->andReturn([
                'mobile_wallet.airtel' => [
                    'base_url' => 'https://api.airtel.com',
                    'api_key' => 'test-api-key',
                    'api_secret' => 'test-api-secret',
                ],
            ]);

        $provider = $this->manager->provider('airtel');
        $this->assertInstanceOf(AirtelService::class, $provider);
    }

    public function test_it_resolves_zamtel_provider(): void
    {
        // Set up config expectations
        $this->app->shouldReceive('offsetGet')
            ->with('config')
            ->andReturn([
                'mobile_wallet.zamtel' => [
                    'base_url' => 'https://api.zamtel.com',
                    'api_key' => 'test-api-key',
                    'api_secret' => 'test-api-secret',
                ],
            ]);

        $provider = $this->manager->provider('zamtel');
        $this->assertInstanceOf(ZamtelService::class, $provider);
    }

    public function test_it_throws_exception_for_invalid_provider(): void
    {
        // Set up config expectations
        $this->app->shouldReceive('offsetGet')
            ->with('config')
            ->andReturn([
                'mobile_wallet.invalid' => [],
            ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->manager->provider('invalid');
    }

    public function test_it_reuses_resolved_provider(): void
    {
        // Set up config expectations
        $this->app->shouldReceive('offsetGet')
            ->with('config')
            ->andReturn([
                'mobile_wallet.mtn' => [
                    'base_url' => 'https://api.mtn.com',
                    'api_key' => 'test-api-key',
                    'api_secret' => 'test-api-secret',
                ],
            ]);

        $provider1 = $this->manager->provider('mtn');
        $provider2 = $this->manager->provider('mtn');

        $this->assertSame($provider1, $provider2);
    }

    public function test_it_proxies_method_calls_to_default_provider(): void
    {
        // Create a mock for the default provider
        $mockProvider = Mockery::mock(MTNService::class);
        $mockProvider->shouldReceive('authenticate')
            ->once()
            ->andReturn('test-token');

        // Set up manager to use the mock provider
        $managerMock = Mockery::mock(MobileWalletManager::class, [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $managerMock->shouldReceive('provider')
            ->with(null)
            ->andReturn($mockProvider);

        // Test the method proxy
        $result = $managerMock->authenticate();
        $this->assertEquals('test-token', $result);
    }
} 