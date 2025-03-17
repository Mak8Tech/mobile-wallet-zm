<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Mak8Tech\MobileWalletZm\Http\Middleware\RateLimitApiRequests;
use Mak8Tech\MobileWalletZm\Tests\TestCase;
use Mockery;
use Symfony\Component\HttpFoundation\Response;

class RateLimitApiRequestsTest extends TestCase
{
    /** @test */
    public function it_allows_requests_within_rate_limit()
    {
        // Mock the rate limiter
        $rateLimiter = Mockery::mock(RateLimiter::class);
        $rateLimiter->shouldReceive('tooManyAttempts')->once()->andReturn(false);
        $rateLimiter->shouldReceive('hit')->once();
        $rateLimiter->shouldReceive('attempts')->once()->andReturn(1);
        
        // Create the middleware
        $middleware = new RateLimitApiRequests($rateLimiter);
        
        // Create a request
        $request = Request::create('/api/test', 'GET');
        
        // Create a response closure
        $next = function ($request) {
            return new Response('OK');
        };
        
        // Execute the middleware
        $response = $middleware->handle($request, $next, 'default');
        
        // Assert the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }
    
    /** @test */
    public function it_blocks_requests_exceeding_rate_limit()
    {
        // Mock the rate limiter
        $rateLimiter = Mockery::mock(RateLimiter::class);
        $rateLimiter->shouldReceive('tooManyAttempts')->once()->andReturn(true);
        $rateLimiter->shouldReceive('availableIn')->once()->andReturn(60);
        
        // Create the middleware
        $middleware = new RateLimitApiRequests($rateLimiter);
        
        // Create a request
        $request = Request::create('/api/test', 'GET');
        
        // Create a response closure that should not be called
        $next = function ($request) {
            return new Response('OK');
        };
        
        // Execute the middleware
        $response = $middleware->handle($request, $next, 'default');
        
        // Assert the response
        $this->assertEquals(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertStringContainsString('Too Many Attempts', $response->getContent());
    }
    
    /** @test */
    public function it_uses_custom_rate_limit_for_different_prefixes()
    {
        // Mock the rate limiter
        $rateLimiter = Mockery::mock(RateLimiter::class);
        $rateLimiter->shouldReceive('tooManyAttempts')->once()->andReturn(false);
        $rateLimiter->shouldReceive('hit')->once();
        $rateLimiter->shouldReceive('attempts')->once()->andReturn(1);
        
        // Create the middleware
        $middleware = new RateLimitApiRequests($rateLimiter);
        
        // Create a request
        $request = Request::create('/api/test', 'GET');
        
        // Create a response closure
        $next = function ($request) {
            return new Response('OK');
        };
        
        // Execute the middleware with a custom prefix
        $response = $middleware->handle($request, $next, 'payment');
        
        // Assert the response
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 