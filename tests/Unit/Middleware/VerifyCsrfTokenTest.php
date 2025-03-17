<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Mak8Tech\MobileWalletZm\Http\Middleware\VerifyCsrfToken;
use Mak8Tech\MobileWalletZm\Tests\TestCase;
use Mockery;
use Symfony\Component\HttpFoundation\Response;

class VerifyCsrfTokenTest extends TestCase
{
    /** @test */
    public function it_excludes_webhook_endpoints_from_csrf_verification()
    {
        // Create a mock request for a webhook endpoint
        $request = Request::create('/api/mobile-wallet/webhook/mtn', 'POST');

        // Create a mock middleware instance
        $middleware = $this->getMockBuilder(VerifyCsrfToken::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['tokensMatch'])
            ->getMock();

        // Set up the mock to expect that tokensMatch is not called
        $middleware->expects($this->never())
            ->method('tokensMatch');

        // Create a response closure
        $next = function ($request) {
            return new Response('OK');
        };

        // Execute the middleware
        $response = $middleware->handle($request, $next);

        // Assert the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_allows_api_requests_with_valid_api_token()
    {
        // Set up config
        config(['mobile_wallet.api_token' => 'test-api-token']);

        // Create a mock request for a payment endpoint with a valid API token
        $request = Request::create('/api/mobile-wallet/payment', 'POST');
        $request->headers->set('X-API-Token', 'test-api-token');

        // Create a mock middleware instance
        $middleware = $this->getMockBuilder(VerifyCsrfToken::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['tokensMatch'])
            ->getMock();

        // Set up the mock to expect that tokensMatch is not called
        $middleware->expects($this->never())
            ->method('tokensMatch');

        // Create a response closure
        $next = function ($request) {
            return new Response('OK');
        };

        // Execute the middleware
        $response = $middleware->handle($request, $next);

        // Assert the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_verifies_csrf_token_for_api_requests_without_api_token()
    {
        // Create a mock request for a payment endpoint without an API token
        $request = Request::create('/api/mobile-wallet/payment', 'POST');

        // Create a mock middleware instance
        $middleware = $this->getMockBuilder(VerifyCsrfToken::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['tokensMatch', 'addCookieToResponse'])
            ->getMock();

        // Set up the mock to expect that tokensMatch is called and returns true
        $middleware->expects($this->once())
            ->method('tokensMatch')
            ->willReturn(true);

        $middleware->expects($this->once())
            ->method('addCookieToResponse')
            ->willReturnArgument(1);

        // Create a response closure
        $next = function ($request) {
            return new Response('OK');
        };

        // Execute the middleware
        $response = $middleware->handle($request, $next);

        // Assert the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
