<?php

namespace Mak8Tech\MobileWalletZm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mak8Tech\MobileWalletZm\Facades\MobileWallet;

class MobileWalletController extends Controller
{
    /**
     * Initiate a payment.
     */
    public function initiatePayment(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'provider' => 'sometimes|string|in:mtn,airtel,zamtel',
            'reference' => 'sometimes|string',
            'narration' => 'sometimes|string',
        ]);

        $provider = $validated['provider'] ?? null;

        try {
            $result = MobileWallet::provider($provider)->requestPayment(
                $validated['phone_number'],
                $validated['amount'],
                $validated['reference'] ?? null,
                $validated['narration'] ?? null
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check the status of a transaction.
     */
    public function checkStatus(Request $request, string $transactionId)
    {
        $provider = $request->query('provider');

        try {
            $result = MobileWallet::provider($provider)->checkTransactionStatus($transactionId);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
