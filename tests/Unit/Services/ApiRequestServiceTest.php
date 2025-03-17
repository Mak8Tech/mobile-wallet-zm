<?php

namespace Mak8Tech\MobileWalletZm\Tests\Unit\Services;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mak8Tech\MobileWalletZm\Exceptions\ApiRequestException;
use Mak8Tech\MobileWalletZm\Services\ApiRequestService;
use Mak8Tech\MobileWalletZm\Tests\TestCase;

class ApiRequestServiceTest extends TestCase
{
    /** @test */
    public function it_makes_a_successful_get_request()
    {
        // Mock HTTP facade
        Http::fake([
            'api.example.com/users*' => Http::response(['data' => [['id' => 1, 'name' => 'John Doe']]], 200),
        ]);

        // Create the API request service
        $api = new ApiRequestService('https://api.example.com');

        // Make a GET request
        $response = $api->get('users');

        // Assert the response
        $this->assertEquals(200, $response->status());
        $this->assertEquals([['id' => 1, 'name' => 'John Doe']], $response->json('data'));

        // Assert that the request was made with the correct URL and method
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.example.com/users' && 
                   $request->method() === 'GET';
        });
    }

    /** @test */
    public function it_makes_a_successful_post_request()
    {
        // Mock HTTP facade
        Http::fake([
            'api.example.com/users' => Http::response(['id' => 1, 'name' => 'John Doe'], 201),
        ]);

        // Create the API request service
        $api = new ApiRequestService('https://api.example.com');

        // Make a POST request
        $response = $api->post('users', ['name' => 'John Doe']);

        // Assert the response
        $this->assertEquals(201, $response->status());
        $this->assertEquals(['id' => 1, 'name' => 'John Doe'], $response->json());

        // Assert that the request was made with the correct URL, method, and data
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.example.com/users' && 
                   $request->method() === 'POST' &&
                   $request->data() === ['name' => 'John Doe'];
        });
    }

    /** @test */
    public function it_retries_a_failed_request_and_eventually_succeeds()
    {
        // Mock HTTP facade
        Http::fake([
            'api.example.com/users' => Http::sequence()
                ->push(new ConnectException('Connection failed', new GuzzleRequest('GET', 'api.example.com/users')))
                ->push(new ConnectException('Connection failed', new GuzzleRequest('GET', 'api.example.com/users')))
                ->push(['data' => 'success'], 200),
        ]);

        // Adjust retry settings for the test
        config(['mobile_wallet.request.retries' => 2]);
        config(['mobile_wallet.request.retry_delay' => 1]); // 1ms to speed up test

        // Create the API request service
        $api = new ApiRequestService('https://api.example.com');

        // Make a GET request
        $response = $api->get('users');

        // Assert the response
        $this->assertEquals(200, $response->status());
        $this->assertEquals('success', $response->json('data'));

        // Assert that the request was made 3 times (2 failures + 1 success)
        Http::assertSentCount(3);
    }

    /** @test */
    public function it_throws_an_exception_after_all_retries_fail()
    {
        // Mock HTTP facade to always throw ConnectException
        Http::fake(function () {
            throw new ConnectException('Connection failed', new GuzzleRequest('GET', 'api.example.com/users'));
        });

        // Adjust retry settings for the test
        config(['mobile_wallet.request.retries' => 2]);
        config(['mobile_wallet.request.retry_delay' => 1]); // 1ms to speed up test

        // Create the API request service
        $api = new ApiRequestService('https://api.example.com');

        // Expect an exception to be thrown
        $this->expectException(ApiRequestException::class);
        $this->expectExceptionMessage('API request failed after 3 attempts');

        // Make a GET request
        $api->get('users');

        // Assert that the request was made 3 times (all failures)
        Http::assertSentCount(3);
    }

    /** @test */
    public function it_adds_custom_headers_to_requests()
    {
        // Mock HTTP facade
        Http::fake([
            'api.example.com/users' => Http::response(['data' => 'success'], 200),
        ]);

        // Create the API request service with custom headers
        $api = new ApiRequestService('https://api.example.com', [
            'X-API-Key' => 'test-api-key',
            'Accept' => 'application/json',
        ]);

        // Add another header
        $api->withHeader('Authorization', 'Bearer token123');

        // Make a GET request
        $api->get('users');

        // Assert that the request was made with the custom headers
        Http::assertSent(function (Request $request) {
            return $request->hasHeader('X-API-Key', 'test-api-key') &&
                   $request->hasHeader('Accept', 'application/json') &&
                   $request->hasHeader('Authorization', 'Bearer token123');
        });
    }

    /** @test */
    public function it_handles_different_http_methods()
    {
        // Mock HTTP facade
        Http::fake([
            'api.example.com/*' => Http::response(['data' => 'success'], 200),
        ]);

        // Create the API request service
        $api = new ApiRequestService('https://api.example.com');

        // Make different types of requests
        $api->get('users');
        $api->post('users', ['name' => 'John']);
        $api->put('users/1', ['name' => 'John Doe']);
        $api->delete('users/1');

        // Assert that the requests were made with the correct methods
        Http::assertSent(function (Request $request) {
            return $request->method() === 'GET' && $request->url() === 'https://api.example.com/users';
        });

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST' && $request->url() === 'https://api.example.com/users';
        });

        Http::assertSent(function (Request $request) {
            return $request->method() === 'PUT' && $request->url() === 'https://api.example.com/users/1';
        });

        Http::assertSent(function (Request $request) {
            return $request->method() === 'DELETE' && $request->url() === 'https://api.example.com/users/1';
        });
    }
} 