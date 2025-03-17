<?php

namespace Mak8Tech\MobileWalletZm\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;
use Illuminate\Http\Request;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // Webhook endpoints are excluded as they are called by external services
        'api/mobile-wallet/webhook/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        // Check if the request is an API request with a valid API token
        if ($this->isApiRequest($request) && $this->hasValidApiToken($request)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * Determine if the request is an API request.
     *
     * @return bool
     */
    protected function isApiRequest(Request $request)
    {
        return $request->is('api/mobile-wallet/*') && ! $request->is('api/mobile-wallet/webhook/*');
    }

    /**
     * Determine if the request has a valid API token.
     *
     * @return bool
     */
    protected function hasValidApiToken(Request $request)
    {
        $token = $request->header('X-API-Token');

        if (empty($token)) {
            return false;
        }

        // Verify the token against the configured API token
        return hash_equals(config('mobile_wallet.api_token'), $token);
    }
}
