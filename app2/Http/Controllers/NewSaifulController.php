<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class NewSaifulController extends Controller
{
    /**
     * Receive Order (Secret key না মিললে ঢুকতেই পারবে না)
     */
    public function receive(Request $request)
    {
        $request->validate([
            'order_id'        => 'required|string',
            'uid'             => 'required|string',
            'variation_name'  => 'required|string',
            'status'          => 'required|string',
            'secret_key'      => 'required|string',
        ]);

        // 🔐 SECRET KEY CHECK (MAIN GATE)
        if ($request->secret_key !== config('services.new_saiful.secret')) {
            return response()->json([
                'message' => 'Unauthorized: Invalid secret key'
            ], 403);
        }

        // ❌ Duplicate order protection
        $exists = Order::where('external_order_id', $request->order_id)->first();
        if ($exists) {
            return response()->json([
                'message' => 'Order already exists'
            ], 409);
        }

        Order::create([
            'external_order_id' => $request->order_id,
            'uid'               => $request->uid,
            'variation_name'    => $request->variation_name,
            'status'            => $request->status, // requested / pending
            'source'            => 'NEW_SAIFUL',
        ]);

        return response()->json([
            'message' => 'Order received successfully'
        ], 201);
    }

    /**
     * Update Order Status (accepted / completed)
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|string',
            'status'     => 'required|in:accepted,completed',
            'secret_key' => 'required|string',
        ]);

        // 🔐 Again secret check (mandatory)
        if ($request->secret_key !== config('services.new_saiful.secret')) {
            return response()->json([
                'message' => 'Unauthorized: Invalid secret key'
            ], 403);
        }

        $order = Order::where('external_order_id', $request->order_id)->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        // 🔒 completed হলে আর পরিবর্তন হবে না
        if ($order->status === 'completed') {
            return response()->json([
                'message' => 'Order already completed'
            ], 409);
        }

        $order->update([
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'status'  => $order->status
        ]);
    }
}
