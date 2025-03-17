<?php

namespace Mak8Tech\MobileWalletZm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mak8Tech\MobileWalletZm\Exceptions\MobileWalletException;
use Mak8Tech\MobileWalletZm\Facades\MobileWallet;

class WebhookController extends Controller
{
    /**
     * Handle the incoming webhook.
     */
    public function handle(Request $request, ?string $provider = null)
    {
        $provider = $provider ?? MobileWallet::getDefaultProvider();
        $payload = $request->all();

        try {
            $result = MobileWallet::provider($provider)->processCallback($payload);

            return response()->json($result);
        } catch (MobileWalletException $e) {
            return response()->json($e->toArray(), $e->getCode() ?: 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'unknown_error',
                    'message' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}
