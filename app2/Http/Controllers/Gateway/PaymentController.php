<?php

namespace App\Http\Controllers\Gateway;

use App\Library\UddoktaPay;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /* ---------- existing UddoktaPay (kept) ---------- */
    public function deposit(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1']);
        $user = Auth::user();
        $amount = $request->amount;

        $requestData = [
            'full_name'    => $user->name,
            'email'        => $user->email,
            'amount'       => $amount,
            'metadata'     => ['amount' => $amount],
            'redirect_url' => route('payment'),
            'return_type'  => 'GET',
            'cancel_url'   => route('cancel.payment'),
        ];
        try {
            return redirect(UddoktaPay::init_payment($requestData));
        } catch (Exception $e) {
            return back()->with('message', $e->getMessage())->with('message_type', 'error');
        }
    }

    public function payment(Request $request)
    {
        $user = Auth::user();
        if (empty($request->invoice_id)) die('Invalid Request');
        $data = UddoktaPay::verify_payment($request->invoice_id);
        if (isset($data['status']) && $data['status'] == 'COMPLETED') {
            $amount = $data['metadata']['amount'];
            $user->increment('balance', $amount);
            return redirect()->route('addfunds')->with('message', 'Add money success.')->with('message_type', 'success');
        }
        return redirect()->route('addfunds')->with('message', 'Add money failed.')->with('message_type', 'error');
    }

    public function payment_cancel(Request $request)
    {
        return redirect()->route('home');
    }

    /* ====================================================== *
     *  Manual Payment system  ( /payment/{id} )
     * ====================================================== */

    /**
     * Show the manual-payment page.
     *   $id = identifier (e.g. order id or addfunds amount slug).
     *   ?page=checkout|addfunds&amount=...&order_id=...
     */
    public function manualPayment(Request $request, $id)
    {
        $user = Auth::user();

        $page    = $request->query('page', 'checkout');     // checkout | addfunds
        $amount  = (float) $request->query('amount', 0);
        $orderId = $request->query('order_id');

        // Numbers + webhook url come from settings table (admin editable).
        $numbers = [
            'bkash'  => optional(\App\Models\Setting::get('bkash_number'))  ?? '01XXXXXXXXX',
            'nagad'  => optional(\App\Models\Setting::get('nagad_number'))  ?? '01XXXXXXXXX',
            'rocket' => optional(\App\Models\Setting::get('rocket_number')) ?? '01XXXXXXXXX',
        ];

        return view('pages.payment', compact('id', 'page', 'amount', 'orderId', 'numbers', 'user'));
    }

    /**
     * Submit TX ID -> store transaction (pending) -> call webhook -> mark status.
     */
    public function submitManualPayment(Request $request, $id)
    {
        $request->validate([
            'method'         => 'required|in:bkash,nagad,rocket',
            'transaction_id' => 'required|string|min:6|max:64',
            'sender_number'  => 'required|string|min:6|max:20',
            'amount'         => 'required|numeric|min:1',
            'page'           => 'required|in:checkout,addfunds',
            'order_id'       => 'nullable|integer',
        ]);

        $user = Auth::user();

        // --- Duplicate guard (per user, 3s lock) ---
        $lockKey = "manual_pay_lock:{$user->id}:{$request->method}:{$request->transaction_id}";
        if (! Cache::add($lockKey, 1, 3)) {
            return response()->json([
                'success' => false,
                'status'  => 'duplicate',
                'message' => 'Duplicate request. Please wait a moment and try again.',
            ], 429);
        }

        // --- DB-level duplicate check ---
        if (Transaction::where('method', $request->method)
                ->where('transaction_id', $request->transaction_id)
                ->whereIn('status', ['pending', 'verifying', 'success'])
                ->exists()) {
            return response()->json([
                'success' => false,
                'status'  => 'duplicate',
                'message' => 'This Transaction ID is already used.',
            ], 409);
        }

        $receiver = optional(\App\Models\Setting::get($request->method . '_number')) ?? null;

        $tx = Transaction::create([
            'user_id'         => $user->id,
            'user_gmail'      => $user->email,
            'method'          => $request->method,
            'transaction_id'  => $request->transaction_id,
            'amount'          => $request->amount,
            'page'            => $request->page,
            'order_id'        => $request->order_id,
            'sender_number'   => $request->sender_number,
            'receiver_number' => $receiver,
            'status'          => 'verifying',
        ]);

        // --- Send to webhook for verification ---
        $webhookUrl = optional(\App\Models\Setting::get('payment_webhook_url'))
            ?? env('PAYMENT_WEBHOOK_URL');

        if (empty($webhookUrl)) {
            $tx->update(['status' => 'pending', 'note' => 'No webhook URL configured; awaiting manual review.']);
            return response()->json([
                'success' => true,
                'status'  => 'pending',
                'message' => 'Payment submitted. Awaiting manual review.',
                'tx_id'   => $tx->id,
            ]);
        }

        try {
            $resp = Http::timeout(15)->post($webhookUrl, [
                'method'         => $tx->method,
                'transaction_id' => $tx->transaction_id,
                'amount'         => $tx->amount,
                'sender_number'  => $tx->sender_number,
                'receiver_number'=> $tx->receiver_number,
                'user_id'        => $tx->user_id,
                'order_id'       => $tx->order_id,
                'page'           => $tx->page,
            ]);

            $json = $resp->json() ?? [];
            $tx->webhook_response = $json;

            $verified = ($resp->successful() && (
                ($json['status'] ?? '') === 'success'
                || ($json['verified'] ?? false) === true
            ));

            if ($verified) {
                $tx->status    = 'success';
                $tx->time_paid = now();
                $tx->save();

                $this->applySuccessfulPayment($tx);

                return response()->json([
                    'success' => true,
                    'status'  => 'success',
                    'message' => 'Payment verified successfully!',
                    'tx_id'   => $tx->id,
                ]);
            }

            $tx->status = 'failed';
            $tx->note   = $json['message'] ?? 'Verification failed.';
            $tx->save();

            return response()->json([
                'success' => false,
                'status'  => 'failed',
                'message' => $tx->note,
                'tx_id'   => $tx->id,
            ], 422);
        } catch (Exception $e) {
            $tx->update(['status' => 'failed', 'note' => $e->getMessage()]);
            Log::error('Manual payment webhook error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status'  => 'failed',
                'message' => 'Could not verify right now. Try again.',
            ], 500);
        }
    }

    /**
     * External webhook -> our site (POST). Verifies an existing TX and finalises it.
     * URL: /api/payment/webhook  (set in routes/api or routes/web)
     */
    public function incomingWebhook(Request $request)
    {
        $request->validate([
            'method'         => 'required|in:bkash,nagad,rocket',
            'transaction_id' => 'required|string',
            'status'         => 'required|in:success,failed',
        ]);

        $tx = Transaction::where('method', $request->method)
            ->where('transaction_id', $request->transaction_id)
            ->latest('id')->first();

        if (! $tx) return response()->json(['ok' => false, 'message' => 'Tx not found'], 404);

        if ($tx->status === 'success') {
            return response()->json(['ok' => true, 'message' => 'Already processed']);
        }

        if ($request->status === 'success') {
            $tx->status    = 'success';
            $tx->time_paid = now();
            $tx->webhook_response = $request->all();
            $tx->save();
            $this->applySuccessfulPayment($tx);
        } else {
            $tx->status = 'failed';
            $tx->note   = $request->input('message', 'Rejected by webhook');
            $tx->webhook_response = $request->all();
            $tx->save();
        }

        return response()->json(['ok' => true, 'status' => $tx->status]);
    }

    /**
     * On a successful tx: credit balance (addfunds) or mark the order paid (checkout).
     */
    protected function applySuccessfulPayment(Transaction $tx): void
    {
        if ($tx->page === 'addfunds' && $tx->user_id) {
            \App\Models\User::where('id', $tx->user_id)->increment('balance', $tx->amount);
            return;
        }

        if ($tx->page === 'checkout' && $tx->order_id) {
            $order = \App\Models\Order::find($tx->order_id);
            if ($order && $order->status !== \App\Constants\Status::COMPLETE) {
                $order->status = \App\Constants\Status::PROCESSING;
                $order->save();
            }
        }
    }
}
