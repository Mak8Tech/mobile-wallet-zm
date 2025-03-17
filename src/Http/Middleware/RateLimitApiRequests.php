<?php

namespace Mak8Tech\MobileWalletZm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RateLimitApiRequests
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $prefix
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, $prefix = 'default')
    {
        $key = $prefix . ':' . $this->resolveRequestSignature($request);
        
        // Get rate limit configuration
        $maxAttempts = Config::get('mobile-wallet-zm.rate_limits.' . $prefix, 60);
        $decaySeconds = Config::get('mobile-wallet-zm.rate_limit_decay_minutes', 1) * 60;
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildRateLimitResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decaySeconds);

        $response = $next($request);

        return $this->addRateLimitHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request)
    {
        return sha1(
            $request->ip() .
            $request->method() .
            $request->getHost() .
            $request->path()
        );
    }

    /**
     * Create a 'too many attempts' response.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Illuminate\Http\JsonResponse
     */
    protected function buildRateLimitResponse($key, $maxAttempts)
    {
        $seconds = $this->limiter->availableIn($key);

        return new JsonResponse([
            'error' => 'Too Many Attempts',
            'message' => 'Too many requests. Please try again in ' . ceil($seconds / 60) . ' minutes.',
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Add the rate limit headers to the given response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addRateLimitHeaders($response, $maxAttempts, $remainingAttempts)
    {
        return tap($response, function ($response) use ($maxAttempts, $remainingAttempts) {
            $response->headers->add([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => $remainingAttempts,
            ]);
        });
    }

    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts)
    {
        return $maxAttempts - $this->limiter->attempts($key) + 1;
    }
} 