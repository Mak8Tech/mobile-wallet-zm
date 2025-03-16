<?php

namespace Mak8Tech\MobileWalletZm\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhook
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The verification process depends on the provider.
        // This is a simple example. You should adapt this to match your provider's requirements.
        
        $signature = $request->header('X-Signature');
        $webhookSecret = config('mobile_wallet.webhook.secret');
        
        if (!$signature || !$webhookSecret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Simple HMAC verification example
        $calculatedSignature = hash_hmac('sha256', $request->getContent(), $webhookSecret);
        
        if (!hash_equals($calculatedSignature, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
