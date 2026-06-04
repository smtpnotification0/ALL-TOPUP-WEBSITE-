<?php

namespace App\Http\Controllers;

use App\Constants\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AutoTopupController extends Controller
{
    public function update(Request $request)
    {
        $payload = $request->getContent();
        $data = json_decode($payload, true);
        $order_id_param = $request->query('order');

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        // Helper to parse Buffer-like arrays
        $parseBuffer = function ($array) use (&$parseBuffer) {
            foreach ($array as $key => $value) {
                if (is_array($value) && isset($value['type']) && $value['type'] === 'Buffer' && isset($value['data'])) {
                    $array[$key] = base64_encode(implode(array_map("chr", $value['data'])));
                } elseif (is_array($value)) {
                    $array[$key] = $parseBuffer($value);
                }
            }
            return $array;
        };

        $parsedData = $parseBuffer($data);

        $status = $parsedData['data']['status'] ?? null;
        $orderState = $parsedData['data']['orderState'] ?? [];
        $errorCode = $orderState['orderFailedErrorCode'] ?? 0;
        $reason = ($orderState['orderFailedMessage'] ?? '') . ' (Error Code: ' . $errorCode . ')';


        $order = Order::where('id', $order_id_param)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if (in_array($order->status, ['complete', 'cancel'])) {
            return response()->json(['message' => 'Order already processed']);
        }

        switch ($status) {
            case 'success':
            case 'finish':
                $order->status = 'complete';
                break;

            case 'error':
            case 'failed':
                $order->status = 'processing';
                $order->message = $reason ?? 'Order failed';
                break;

            case 'update':
                return response()->json(['message' => 'Update received']);
        }

        $order->save();

        if (str_contains(strtolower($orderState['orderFailedMessage'] ?? ''), 'invalid uid') || str_contains(strtolower($orderState['orderFailedMessage'] ?? ''), 'invalid region')) {
            $order->status = 'cancel';
            $order->save();
            $user = User::where('id', $order->user_id)->first();
            $user->increment('balance', $order->price);
            $user->decrement('total_order');
            $user->decrement('total_spent', $order->price);
        }

        return response()->json(['message' => 'Webhook processed successfully']);
    }

    /**
     * Handle webhook result from Humayun bot panel
     * Only processes when status is "Completed"
     */
    public function humayunWebhook(Request $request)
    {
        try {
            $data = $request->all();

            // Validate required fields
            if (!isset($data['order_id']) || !isset($data['status'])) {
                Log::warning('Humayun webhook: Missing required fields', ['data' => $data]);
                return response()->json(['error' => 'Missing required fields: order_id, status'], 400);
            }

            $orderId = $data['order_id'];
            $status = $data['status'];

            // Find the order
            $order = Order::find($orderId);

            if (!$order) {
                Log::warning("Humayun webhook: Order not found", ['order_id' => $orderId]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            $errorMessage = $data['message'] ?? $data['error_message'] ?? null;
            
            // Check for invalid UID/region or canceled by admin error message - trigger cancel and refund (special case)
            $isInvalidUid = $errorMessage && (
                str_contains(strtolower($errorMessage), 'invalid uid') || 
                str_contains(strtolower($errorMessage), 'invalid region') ||
                str_contains(strtolower($errorMessage), 'not bd server') ||
                str_contains(strtolower($errorMessage), 'wrong uid')
            );
            
            $isCanceledByAdmin = $errorMessage && str_contains(strtolower($errorMessage), 'canceled by admin');
            
            if (($isInvalidUid || $isCanceledByAdmin) && strtolower($status) === 'failed') {
                if ($isInvalidUid) {
                    Log::info("Humayun webhook: Invalid UID/region detected for order {$orderId}, canceling and refunding");
                } else {
                    Log::info("Humayun webhook: Order canceled by admin for order {$orderId}, canceling and refunding");
                }
                
                // Only cancel and refund if order is in processing/auto-processing status
                if (in_array($order->status, [OrderStatus::PROCESSING, OrderStatus::AUTOPROCESSING])) {
                    $user = $order->user;
                    $refundAmount = $order->amount;

                    if ($user && $refundAmount > 0) {
                        // Refund the amount to user wallet
                        $user->increment('balance', $refundAmount);
                        
                        // Update order status to cancel
                        $order->status = OrderStatus::CANCEL;
                        // Update delivery message based on cancel reason
                        $order->delivery_message = $isInvalidUid ? 'Invalid uid or not BD server' : 'Order canceled by Admin';
                        $order->save();

                        Log::info("Humayun webhook: Order {$orderId} canceled and refunded ৳{$refundAmount} to user {$user->id}");
                        
                        return response()->json([
                            'message' => $isInvalidUid ? 'Order canceled and refunded due to invalid UID/region' : 'Order canceled and refunded by admin',
                            'order_id' => $orderId,
                            'status' => $order->status,
                            'refunded' => true
                        ]);
                    } else {
                        Log::warning("Humayun webhook: Could not refund order {$orderId} - user not found or amount is 0");
                        $order->status = OrderStatus::CANCEL;
                        $order->delivery_message = $isInvalidUid ? 'Invalid uid or not BD server' : 'Order canceled by Admin';
                        $order->save();
                    }
                } else {
                    Log::info("Humayun webhook: Order {$orderId} already in status '{$order->status}', canceling anyway");
                    $order->status = OrderStatus::CANCEL;
                    $order->delivery_message = $isInvalidUid ? 'Invalid uid or not BD server' : 'Order canceled by Admin';
                    $order->save();
                }
                
                // Return after handling invalid UID
                return response()->json([
                    'message' => 'Order status updated successfully',
                    'order_id' => $orderId,
                    'status' => $order->status
                ]);
            }
            
            // Only process if status is "Completed" (as per requirement)
            if (strtolower($status) === 'completed' || strtolower($status) === 'complete') {
                // Update order status to complete
                $order->status = OrderStatus::COMPLETE;
                $order->save();

                Log::info("Humayun webhook: Order {$orderId} marked as complete");
                return response()->json(['message' => 'Order status updated successfully', 'order_id' => $orderId]);
            } else {
                // Log but don't update for other statuses (Hold, Failed without invalid UID, etc.)
                Log::info("Humayun webhook: Received status '{$status}' for order {$orderId}, but only processing 'Completed' status or 'Failed' with invalid UID");
                return response()->json(['message' => 'Status received but not processed (only Completed status or Failed with invalid UID is processed)', 'order_id' => $orderId]);
            }
        } catch (\Exception $e) {
            Log::error('Humayun webhook error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle webhook result from bot panel automation service
     * Updates order status based on bot panel result
     */
    public function automationWebhook(Request $request)
    {
        try {
            $data = $request->all();

            // Validate required fields
            if (!isset($data['order_id']) || !isset($data['status'])) {
                Log::warning('Automation webhook: Missing required fields', ['data' => $data]);
                return response()->json(['error' => 'Missing required fields: order_id, status'], 400);
            }

            $orderId = $data['order_id'];
            $status = strtolower($data['status']);
            $errorMessage = $data['message'] ?? $data['error_message'] ?? null;

            // Find the order
            $order = Order::find($orderId);

            if (!$order) {
                Log::warning("Automation webhook: Order not found", ['order_id' => $orderId]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Check for invalid UID/region or canceled by admin error message - trigger cancel and refund
            $isInvalidUid = $errorMessage && (
                str_contains(strtolower($errorMessage), 'invalid uid') || 
                str_contains(strtolower($errorMessage), 'invalid region') ||
                str_contains(strtolower($errorMessage), 'not bd server') ||
                str_contains(strtolower($errorMessage), 'wrong uid')
            );
            
            $isCanceledByAdmin = $errorMessage && str_contains(strtolower($errorMessage), 'canceled by admin');
            
            // Only process if status is "Failed" (consistency check)
            if (($isInvalidUid || $isCanceledByAdmin) && $status === 'failed') {
                if ($isInvalidUid) {
                    Log::info("Automation webhook: Invalid UID/region detected for order {$orderId}, canceling and refunding");
                } else {
                    Log::info("Automation webhook: Order canceled by admin for order {$orderId}, canceling and refunding");
                }
                Log::info("Automation webhook: Error message = '{$errorMessage}', Status = '{$status}', Current order status = '{$order->status}'");
                
                // For canceled by admin, refund if order is processing or auto-processing (not completed or already canceled)
                // For invalid UID, only refund if order is processing or auto-processing (same logic)
                // Both cases: only refund if order is in active processing state
                if (in_array($order->status, [OrderStatus::PROCESSING, OrderStatus::AUTOPROCESSING])) {
                    $user = $order->user;
                    $refundAmount = $order->amount;

                    if ($user && $refundAmount > 0) {
                        // Refund the amount to user wallet
                        $user->increment('balance', $refundAmount);
                        
                        // Update order status to cancel
                        $order->status = OrderStatus::CANCEL;
                        // Update delivery message based on cancel reason
                        $order->delivery_message = $isInvalidUid ? 'Invalid uid or not BD server' : 'Order canceled by Admin';
                        $order->save();

                        Log::info("Automation webhook: Order {$orderId} canceled and refunded ৳{$refundAmount} to user {$user->id}");
                        
                        return response()->json([
                            'message' => $isInvalidUid ? 'Order canceled and refunded due to invalid UID/region' : 'Order canceled and refunded by admin',
                            'order_id' => $orderId,
                            'status' => $order->status,
                            'refunded' => true
                        ]);
                    } else {
                        Log::warning("Automation webhook: Could not refund order {$orderId} - user not found or amount is 0");
                        $order->status = OrderStatus::CANCEL;
                        $order->delivery_message = $isInvalidUid ? 'Invalid uid or not BD server' : 'Order canceled by Admin';
                        $order->save();
                    }
                } else {
                    // Cancel order without refund (already completed or canceled)
                    Log::info("Automation webhook: Order {$orderId} already in status '{$order->status}', canceling anyway without refund");
                    $order->status = OrderStatus::CANCEL;
                    $order->delivery_message = $isInvalidUid ? 'Invalid uid or not BD server' : 'Order canceled by Admin';
                    $order->save();
                }
                
                // Return after handling invalid UID - don't process normal status logic
                Log::info("Automation webhook: Order {$orderId} status updated to '{$order->status}'");
                return response()->json([
                    'message' => 'Order status updated successfully',
                    'order_id' => $orderId,
                    'status' => $order->status
                ]);
            } else {
                // Map bot panel statuses to website order statuses
                // Only process "completed" status - do not change auto-processing to processing
                switch ($status) {
                    case 'completed':
                    case 'complete':
                        $order->status = OrderStatus::COMPLETE;
                        $order->save();
                        break;
                    
                    case 'processing':
                        // Only update to processing if not already in auto-processing
                        // Do not change auto-processing to processing
                        if ($order->status !== OrderStatus::AUTOPROCESSING) {
                            $order->status = OrderStatus::PROCESSING;
                            $order->save();
                        }
                        break;
                    
                    case 'hold':
                    case 'failed':
                        // For hold/failed, do not change status if in auto-processing
                        // Keep current status (auto-processing stays auto-processing)
                        if ($order->status !== OrderStatus::AUTOPROCESSING) {
                            // Only update if not auto-processing
                            $order->status = OrderStatus::PROCESSING;
                            $order->save();
                        }
                        break;
                    
                    default:
                        Log::warning("Automation webhook: Unknown status '{$status}' for order {$orderId}");
                        return response()->json(['error' => 'Unknown status'], 400);
                }
            }

            Log::info("Automation webhook: Order {$orderId} status updated to '{$order->status}'");
            return response()->json([
                'message' => 'Order status updated successfully',
                'order_id' => $orderId,
                'status' => $order->status
            ]);
        } catch (\Exception $e) {
            Log::error('Automation webhook error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}