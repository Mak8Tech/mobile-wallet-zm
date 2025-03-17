<?php

namespace Mak8Tech\MobileWalletZm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mak8Tech\MobileWalletZm\Exceptions\MobileWalletException;
use Mak8Tech\MobileWalletZm\Facades\MobileWallet;

class WebhookController extends Controller
{
    /**
     * Handle the incoming webhook from MTN.
     */
    public function handleMTN(Request $request)
    {
        return $this->processWebhook($request, 'mtn');
    }

    /**
     * Handle the incoming webhook from Airtel.
     */
    public function handleAirtel(Request $request)
    {
        return $this->processWebhook($request, 'airtel');
    }

    /**
     * Handle the incoming webhook from Zamtel.
     */
    public function handleZamtel(Request $request)
    {
        return $this->processWebhook($request, 'zamtel');
    }

    /**
     * Process the webhook request for the specified provider.
     *
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processWebhook(Request $request, string $provider)
    {
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
