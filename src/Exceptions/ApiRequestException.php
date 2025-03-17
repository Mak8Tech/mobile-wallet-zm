<?php

namespace Mak8Tech\MobileWalletZm\Exceptions;

use Exception;

class ApiRequestException extends Exception
{
    /**
     * Create a new API request exception.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create a new API request exception for a failed HTTP response.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @param  string  $message
     * @return static
     */
    public static function fromResponse($response, string $message = ''): self
    {
        $statusCode = $response->status();
        $content = $response->json() ?: $response->body();
        
        if (is_array($content)) {
            $content = json_encode($content, JSON_PRETTY_PRINT);
        }
        
        $message = $message ?: "API request failed with status code {$statusCode}";
        $message .= "\nResponse: " . $content;
        
        return new static($message, $statusCode);
    }

    /**
     * Create a new API request exception for a timeout.
     *
     * @param  int  $timeout
     * @param  string  $url
     * @return static
     */
    public static function timeout(int $timeout, string $url): self
    {
        return new static("API request to {$url} timed out after {$timeout} seconds.");
    }

    /**
     * Create a new API request exception for a connection error.
     *
     * @param  string  $url
     * @param  string  $error
     * @return static
     */
    public static function connectionError(string $url, string $error): self
    {
        return new static("API request to {$url} failed with connection error: {$error}");
    }

    /**
     * Create a new API request exception for a rate limit exceeded error.
     *
     * @param  int  $retryAfter
     * @param  string  $url
     * @return static
     */
    public static function rateLimited(int $retryAfter, string $url): self
    {
        return new static("API rate limit exceeded for {$url}. Retry after {$retryAfter} seconds.", 429);
    }
} 