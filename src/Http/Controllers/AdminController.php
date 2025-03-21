<?php

namespace Mak8Tech\MobileWalletZm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Mak8Tech\MobileWalletZm\Models\WalletTransaction;

class AdminController extends Controller
{
    /**
     * Display a listing of the transactions.
     */
    public function index(Request $request)
    {
        $query = WalletTransaction::query();

        // Filter by provider if provided
        if ($request->has('provider')) {
            $query->where('provider', $request->provider);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by phone number if provided
        if ($request->has('phone_number')) {
            $query->where('phone_number', 'like', '%'.$request->phone_number.'%');
        }

        // Filter by date range if provided
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Paginate the results
        $transactions = $query->latest()->paginate(
            $request->per_page ?? 15
        );

        if ($request->expectsJson()) {
            return response()->json($transactions);
        }

        return view('mobile-wallet::admin.transactions.index', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * Display the specified transaction.
     */
    public function show(WalletTransaction $transaction)
    {
        if (request()->expectsJson()) {
            return response()->json($transaction);
        }

        return view('mobile-wallet::admin.transactions.show', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, WalletTransaction $transaction)
    {
        // Validate the request
        $request->validate([
            'status' => 'sometimes|required|in:pending,paid,failed',
            'message' => 'sometimes|nullable|string',
        ]);

        // Update the transaction
        $transaction->update($request->only(['status', 'message']));

        if ($request->status === 'paid' && ! $transaction->paid_at) {
            $transaction->paid_at = now();
            $transaction->save();
        } elseif ($request->status === 'failed' && ! $transaction->failed_at) {
            $transaction->failed_at = now();
            $transaction->save();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Transaction updated successfully',
                'transaction' => $transaction,
            ]);
        }

        return redirect()->route('mobile-wallet.admin.transactions.show', $transaction)
            ->with('success', 'Transaction updated successfully');
    }

    /**
     * Generate a report of transactions.
     */
    public function report(Request $request)
    {
        // Validate the request
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'provider' => 'nullable|string',
            'status' => 'nullable|string',
            'format' => 'nullable|in:csv,xlsx,pdf',
        ]);

        $query = WalletTransaction::query()
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date);

        // Apply filters
        if ($request->has('provider')) {
            $query->where('provider', $request->provider);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Get the transactions
        $transactions = $query->get();

        // Calculate totals
        $total = $transactions->count();
        $totalAmount = $transactions->sum('amount');
        $totalSuccessful = $transactions->where('status', 'paid')->count();
        $totalFailed = $transactions->where('status', 'failed')->count();
        $totalPending = $transactions->where('status', 'pending')->count();

        $data = [
            'transactions' => $transactions,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total' => $total,
            'total_amount' => $totalAmount,
            'total_successful' => $totalSuccessful,
            'total_failed' => $totalFailed,
            'total_pending' => $totalPending,
        ];

        // Generate the requested format
        switch ($request->format ?? 'json') {
            case 'csv':
                return $this->generateCsvReport($data);
            case 'xlsx':
                return $this->generateXlsxReport($data);
            case 'pdf':
                return $this->generatePdfReport($data);
            default:
                return response()->json($data);
        }
    }

    /**
     * Generate a CSV report.
     */
    protected function generateCsvReport($data)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transactions_report.csv"',
        ];

        // Create the CSV content
        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'ID', 'Provider', 'Phone Number', 'Amount', 'Currency',
                'Status', 'Transaction ID', 'Created At', 'Paid At', 'Failed At',
            ]);

            // Add rows
            foreach ($data['transactions'] as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    $transaction->provider,
                    $transaction->phone_number,
                    $transaction->amount,
                    $transaction->currency,
                    $transaction->status,
                    $transaction->transaction_id,
                    $transaction->created_at,
                    $transaction->paid_at,
                    $transaction->failed_at,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Generate an XLSX report.
     */
    protected function generateXlsxReport($data)
    {
        // This would typically use a library like PhpSpreadsheet
        // For simplicity, we're just returning JSON with a message
        return response()->json([
            'message' => 'XLSX report generation would be implemented here',
            'data' => $data,
        ]);
    }

    /**
     * Generate a PDF report.
     */
    protected function generatePdfReport($data)
    {
        // This would typically use a library like DOMPDF
        // For simplicity, we're just returning JSON with a message
        return response()->json([
            'message' => 'PDF report generation would be implemented here',
            'data' => $data,
        ]);
    }
}
