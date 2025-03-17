<?php

namespace Mak8Tech\MobileWalletZm\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mak8Tech\MobileWalletZm\Exceptions\ApiRequestException;

class ApiRequestService
{
    /**
     * The base URL for API requests.
     */
    protected string $baseUrl;

    /**
     * The default headers for API requests.
     */
    protected array $headers = [];

    /**
     * The number of times to retry a failed request.
     */
    protected int $retries;

    /**
     * The delay between retries in milliseconds.
     */
    protected int $retryDelay;

    /**
     * Create a new API request service instance.
     */
    public function __construct(string $baseUrl, array $headers = [])
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->headers = $headers;
        $this->retries = config('mobile_wallet.request.retries', 3);
        $this->retryDelay = config('mobile_wallet.request.retry_delay', 100);
    }

    /**
     * Add a header to the request.
     *
     * @return $this
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Add multiple headers to the request.
     *
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Make a GET request to the API.
     *
     * @throws \Mak8Tech\MobileWalletZm\Exceptions\ApiRequestException
     */
    public function get(string $endpoint, array $query = []): Response
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * Make a POST request to the API.
     *
     * @throws \Mak8Tech\MobileWalletZm\Exceptions\ApiRequestException
     */
    public function post(string $endpoint, array $data = [], array $query = []): Response
    {
        return $this->request('POST', $endpoint, [
            'json' => $data,
            'query' => $query,
        ]);
    }

    /**
     * Make a PUT request to the API.
     *
     * @throws \Mak8Tech\MobileWalletZm\Exceptions\ApiRequestException
     */
    public function put(string $endpoint, array $data = [], array $query = []): Response
    {
        return $this->request('PUT', $endpoint, [
            'json' => $data,
            'query' => $query,
        ]);
    }

    /**
     * Make a DELETE request to the API.
     *
     * @throws \Mak8Tech\MobileWalletZm\Exceptions\ApiRequestException
     */
    public function delete(string $endpoint, array $query = []): Response
    {
        return $this->request('DELETE', $endpoint, ['query' => $query]);
    }

    /**
     * Make a request to the API.
     *
     * @throws \Mak8Tech\MobileWalletZm\Exceptions\ApiRequestException
     */
    protected function request(string $method, string $endpoint, array $options = []): Response
    {
        $url = $this->buildUrl($endpoint);
        $attempts = 0;
        $maxAttempts = $this->retries + 1;

        while ($attempts < $maxAttempts) {
            $attempts++;

            try {
                $response = $this->makeRequest($method, $url, $options);

                // If the request was successful, return the response
                if ($response->successful()) {
                    return $response;
                }

                // If this is the last attempt, throw an exception
                if ($attempts >= $maxAttempts) {
                    throw ApiRequestException::fromResponse($response, "API request failed with status {$response->status()}");
                }

                // If the response indicates rate limiting, wait the specified time
                if ($response->status() === 429 && $response->header('Retry-After')) {
                    $retryAfter = (int) $response->header('Retry-After');
                    Log::warning("Rate limited by API. Retrying after {$retryAfter} seconds.", [
                        'url' => $url,
                        'attempt' => $attempts,
                        'retry_after' => $retryAfter,
                    ]);

                    // Sleep for the retry-after duration (in seconds, converted to milliseconds)
                    usleep($retryAfter * 1000000);
                } else {
                    // Otherwise, wait the configured retry delay
                    Log::warning("API request failed with status {$response->status()}. Retrying ({$attempts}/{$maxAttempts}).", [
                        'url' => $url,
                        'status' => $response->status(),
                        'response' => $response->json(),
                    ]);

                    usleep($this->retryDelay * 1000);
                }
            } catch (ConnectionException $e) {
                // If this is the last attempt, throw an exception
                if ($attempts >= $maxAttempts) {
                    throw ApiRequestException::connectionError($url, $e->getMessage());
                }

                Log::warning("API connection error: {$e->getMessage()}. Retrying ({$attempts}/{$maxAttempts}).", [
                    'url' => $url,
                    'exception' => $e->getMessage(),
                ]);

                usleep($this->retryDelay * 1000);
            }
        }

        // This should never be reached, but just in case
        throw ApiRequestException::connectionError($url, "Failed after {$maxAttempts} attempts");
    }

    /**
     * Make a request to the API.
     */
    protected function makeRequest(string $method, string $url, array $options = []): Response
    {
        $request = Http::withHeaders($this->headers);

        // Add timeout configuration
        $timeout = config('mobile_wallet.request.timeout', 30);
        $request->timeout($timeout);

        // Add any query parameters
        if (! empty($options['query'])) {
            $request->withQueryParameters($options['query']);
            unset($options['query']);
        }

        // Make the request
        return match (strtoupper($method)) {
            'GET' => $request->get($url),
            'POST' => $request->post($url, $options['json'] ?? []),
            'PUT' => $request->put($url, $options['json'] ?? []),
            'DELETE' => $request->delete($url, $options['json'] ?? []),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Build the full URL for the API request.
     */
    protected function buildUrl(string $endpoint): string
    {
        return $this->baseUrl.'/'.ltrim($endpoint, '/');
    }
}
