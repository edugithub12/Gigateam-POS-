<?php

namespace App\Http\Controllers;

use App\Models\MpesaTransaction;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MpesaController extends Controller
{
    public function __construct(protected MpesaService $mpesa) {}

    // ── Initiate STK Push (called from POS via Livewire HTTP call) ─────────────
    public function initiate(Request $request)
    {
        $request->validate([
            'phone'     => 'required|string|min:9',
            'amount'    => 'required|numeric|min:1',
            'reference' => 'required|string',
            'sale_id'   => 'nullable|integer',
        ]);

        try {
            $result = $this->mpesa->stkPush(
                phone:       $request->phone,
                amount:      $request->amount,
                reference:   $request->reference,
                description: 'Gigateam POS Payment'
            );

            if (($result['ResponseCode'] ?? '') !== '0') {
                return response()->json([
                    'success' => false,
                    'message' => $result['ResponseDescription'] ?? 'STK Push failed',
                ], 422);
            }

            // Save pending transaction
            $transaction = MpesaTransaction::create([
                'merchant_request_id' => $result['MerchantRequestID'],
                'checkout_request_id' => $result['CheckoutRequestID'],
                'phone'               => $this->mpesa->normalizePhone($request->phone),
                'amount'              => $request->amount,
                'reference'           => $request->reference,
                'description'         => 'Gigateam POS Payment',
                'status'              => 'pending',
                'sale_id'             => $request->sale_id,
                'user_id'             => auth()->id(),
            ]);

            return response()->json([
                'success'             => true,
                'checkout_request_id' => $result['CheckoutRequestID'],
                'transaction_id'      => $transaction->id,
                'message'             => 'STK Push sent. Ask customer to check their phone.',
            ]);

        } catch (\Exception $e) {
            Log::error('STK Push error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Poll status (POS checks this every 3 seconds) ──────────────────────────
    public function status(Request $request)
    {
        $checkoutRequestId = $request->get('checkout_request_id');
        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if (!$transaction) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // If still pending, query Safaricom
        if ($transaction->status === 'pending') {
            try {
                $result = $this->mpesa->stkQuery($checkoutRequestId);
                $resultCode = $result['ResultCode'] ?? null;

                if ($resultCode === '0') {
                    $transaction->update([
                        'status'       => 'completed',
                        'result_code'  => '0',
                        'result_desc'  => $result['ResultDesc'] ?? 'Success',
                        'completed_at' => now(),
                    ]);

                    // Record payment against sale if sale_id exists
                    if ($transaction->sale_id) {
                        Payment::create([
                            'sale_id'   => $transaction->sale_id,
                            'amount'    => $transaction->amount,
                            'method'    => 'mpesa',
                            'reference' => $transaction->mpesa_receipt ?? $checkoutRequestId,
                            'user_id'   => $transaction->user_id,
                        ]);
                    }

                } elseif ($resultCode !== null && $resultCode !== '0') {
                    $transaction->update([
                        'status'      => 'failed',
                        'result_code' => $resultCode,
                        'result_desc' => $result['ResultDesc'] ?? 'Failed',
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('STK Query error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status'        => $transaction->status,
            'mpesa_receipt' => $transaction->mpesa_receipt,
            'amount'        => $transaction->amount,
            'result_desc'   => $transaction->result_desc,
        ]);
    }

    // ── Safaricom Callback (POST from Safaricom servers) ───────────────────────
    public function callback(Request $request)
    {
        Log::info('M-Pesa Callback', $request->all());

        $body    = $request->input('Body.stkCallback');
        $checkoutRequestId = $body['CheckoutRequestID'] ?? null;

        if (!$checkoutRequestId) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if (!$transaction) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $resultCode = $body['ResultCode'] ?? 1;

        if ($resultCode == 0) {
            // Payment successful — extract receipt
            $items   = collect($body['CallbackMetadata']['Item'] ?? []);
            $receipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
            $amount  = $items->firstWhere('Name', 'Amount')['Value'] ?? $transaction->amount;

            $transaction->update([
                'status'        => 'completed',
                'mpesa_receipt' => $receipt,
                'result_code'   => (string) $resultCode,
                'result_desc'   => $body['ResultDesc'] ?? 'Success',
                'completed_at'  => Carbon::now(),
            ]);

            // Record payment against sale
            if ($transaction->sale_id) {
                Payment::updateOrCreate(
                    ['sale_id' => $transaction->sale_id, 'method' => 'mpesa'],
                    [
                        'amount'    => $amount,
                        'reference' => $receipt,
                        'user_id'   => $transaction->user_id,
                    ]
                );

                // Mark sale as paid
                Sale::where('id', $transaction->sale_id)->update(['payment_status' => 'paid']);
            }

        } else {
            $transaction->update([
                'status'      => 'failed',
                'result_code' => (string) $resultCode,
                'result_desc' => $body['ResultDesc'] ?? 'Failed',
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}