<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Mak8Tech\MobileWalletZm\Http\Middleware\AuthorizeAdminOperations;
use Mak8Tech\MobileWalletZm\Tests\TestCase;
use Mockery;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAdminOperationsTest extends TestCase
{
    /** @test */
    public function it_allows_super_admin_users()
    {
        // Create a user with super admin flag
        $user = new stdClass;
        $user->super_admin = true;

        // Mock Auth facade
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        // Create a request
        $request = Request::create('/admin/mobile-wallet/transactions', 'GET');

        // Create the middleware
        $middleware = new AuthorizeAdminOperations;

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
    public function it_allows_users_with_specific_permission()
    {
        // Create a user with permissions
        $user = new stdClass;
        $user->permissions = ['mobile-wallet.transactions.view'];

        // Mock Auth facade
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        // Create a request
        $request = Request::create('/admin/mobile-wallet/transactions', 'GET');

        // Create the middleware
        $middleware = new AuthorizeAdminOperations;

        // Create a response closure
        $next = function ($request) {
            return new Response('OK');
        };

        // Execute the middleware with a permission
        $response = $middleware->handle($request, $next, 'mobile-wallet.transactions.view');

        // Assert the response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_denies_users_without_required_permission()
    {
        // Create a user without the required permission
        $user = new stdClass;
        $user->permissions = ['some.other.permission'];

        // Mock Auth facade
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        // Create a request
        $request = Request::create('/admin/mobile-wallet/transactions', 'GET');
        $request->headers->set('Accept', 'application/json');

        // Create the middleware
        $middleware = new AuthorizeAdminOperations;

        // Create a response closure that should not be called
        $next = function ($request) {
            return new Response('OK');
        };

        // Execute the middleware with a permission
        $response = $middleware->handle($request, $next, 'mobile-wallet.transactions.view');

        // Assert the response
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('Insufficient permissions', $response->getContent());
    }

    /** @test */
    public function it_denies_unauthenticated_users()
    {
        // Mock Auth facade to simulate unauthenticated user
        Auth::shouldReceive('check')->once()->andReturn(false);

        // Create a request
        $request = Request::create('/admin/mobile-wallet/transactions', 'GET');
        $request->headers->set('Accept', 'application/json');

        // Create the middleware
        $middleware = new AuthorizeAdminOperations;

        // Create a response closure that should not be called
        $next = function ($request) {
            return new Response('OK');
        };

        // Execute the middleware
        $response = $middleware->handle($request, $next);

        // Assert the response
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('Unauthenticated', $response->getContent());
    }

    /** @test */
    public function it_bypasses_authorization_when_disabled_in_config()
    {
        // Set config to disable authorization
        Config::set('mobile_wallet.admin.disable_authorization', true);

        // Create a request
        $request = Request::create('/admin/mobile-wallet/transactions', 'GET');

        // Create the middleware
        $middleware = new AuthorizeAdminOperations;

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
    public function it_uses_custom_super_admin_check_if_provided()
    {
        // Create a user
        $user = new stdClass;
        $user->is_admin = true;

        // Set config to use custom super admin check
        Config::set('mobile_wallet.admin.super_admin_check', function ($user) {
            return $user->is_admin === true;
        });

        // Mock Auth facade
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        // Create a request
        $request = Request::create('/admin/mobile-wallet/transactions', 'GET');

        // Create the middleware
        $middleware = new AuthorizeAdminOperations;

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
