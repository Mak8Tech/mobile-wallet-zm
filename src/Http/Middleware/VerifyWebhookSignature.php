<?php

namespace Mak8Tech\MobileWalletZm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Mak8Tech\MobileWalletZm\Contracts\SignatureVerifier;
use Mak8Tech\MobileWalletZm\Exceptions\WebhookException;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $provider
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Mak8Tech\MobileWalletZm\Exceptions\WebhookException
     */
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        // Bypass signature verification in non-production environments if configured to do so
        if (config('mobile_wallet.bypass_signature_verification_in_testing', false) && App::environment('local', 'testing')) {
            return $next($request);
        }

        // Only verify if signature verification is enabled
        if (config('mobile_wallet.verify_webhook_signatures', true)) {
            // Resolve the appropriate signature verifier based on the provider
            $verifier = App::makeWith(SignatureVerifier::class, ['provider' => $provider]);
            
            if (!$verifier->verifySignature($request)) {
                throw new WebhookException('Invalid webhook signature', 403);
            }
        }

        return $next($request);
    }
} 