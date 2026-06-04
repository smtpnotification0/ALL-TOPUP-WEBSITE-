<?php  
  
namespace App\Http\Controllers;  
  
use App\Constants\Status;  
use App\Constants\OrderStatus;  
use App\Constants\TopupProvider;  
use App\Library\UddoktaPay;  
use Illuminate\Http\Request;  
use Illuminate\Support\Facades\Auth;  
use Illuminate\Support\Facades\DB;  
use Illuminate\Support\Facades\Http;  
use Illuminate\Support\Str;  
use Exception;  
use App\Models\Order;  
use App\Models\Variation;  
use App\Models\Voucher;  
  
class OrdersController extends Controller  
{  
    public function buynow(Request $request)  
    {  
        $variation = Variation::where('stock', '>', 0)  
            ->with(['product', 'vouchers' => function ($query) {  
                $query->where('status', Status::AVAILABLE);  
            }])  
            ->findOrFail($request->variation_id);  
  
        $quantity = $request->input('quantity', 1);  
  
        if ($variation->product->isVoucher() && $variation->vouchers->count() < $quantity) {  
            return back()->with('error', __('Sorry, this voucher is out of stock.'));  
        }  
  
        $amount_cal = round($variation->price * $quantity, 2);  
        $profit_cal = max(0, $amount_cal - $variation->buy_rate);  
        $profit_cal = number_format($profit_cal, 2, '.', '');  

        $orderData = [  
            'user_id'      => Auth::id(),  
            'product_id'   => $variation->product->id,  
            'variation_id' => $variation->id,  
            'quantity'     => $quantity,  
            'amount'       => $amount_cal,
            'profit'       => $profit_cal,  
        ];  
  
        if (in_array($variation->product->type, [Status::TOPUP, Status::INGAME, Status::SUBSCRIPTION])) {  
            $orderData['account_info'] = $request->input('account_info');  
        }  
  
        // Wallet Payment  
        if (gs()->wallet && $request->payment_method === Status::WALLET) {  
            try {  
                if ($amount_cal > Auth::user()->balance) {  
                    throw new Exception(('Insufficient Balance.'));  
                }  
  
                $vouchers = null;  
                if ($variation->product->isVoucher()) {  
                    $vouchers = Voucher::where('status', Status::AVAILABLE)  
                        ->where('variation_id', $variation->id)  
                        ->limit($quantity)  
                        ->orderBy('id', 'DESC')  
                        ->get();  
  
                    if ($vouchers->count() < $quantity) {  
                        throw new Exception(('Insufficient vouchers available.'));  
                    }  
                }  
  
                DB::transaction(function () use ($orderData, $vouchers) {  
                    $order = Order::create($orderData);  
                    $order->status = $order->product->isVoucher() ? Status::COMPLETE : Status::PROCESSING;  
                    $order->update();  
  
                    $user = $order->user;  
                    $user->balance -= $order->amount;  
                    $user->save();  
  
                    if ($order->product->isVoucher()) {  
                        $variation = $order->variation;  
                        $variation->stock -= $vouchers->count();  
                        $variation->save();  
  
                        $voucherCodes = [];  
                        foreach ($vouchers as $index => $voucher) {  
                            $voucherCodes[] = is_array($voucher->code) ? implode(',', $voucher->code) : $voucher->code;  
                            $voucher->status = Status::SOLD;  
                            $voucher->order_id = $order->id;  
                            $voucher->save();  
                        }  
                        $order->voucher_code = implode(', ', $voucherCodes); 
                        $order->update();  
                    } else {  
                        $variation = $order->variation;  
                        $variation->stock -= $order->quantity; 
                        $variation->save();  
                    }  
  
                    $this->handleReseller($order);  
  
                    try {  
                        if ($order->product->isTopup() && $order->variation->isAutomatic() && gs()->enable_auto_topup && gs()->topup_provider === TopupProvider::HUMAYUN && gs()->humayun_server_url && gs()->humayun_api_key && $order->status === Status::PROCESSING) {
                            $this->sendHumayunWebhook($order);
                        } elseif ($order->product->isTopup() && $order->variation->isAutomatic() && gs()->enable_auto_topup && gs()->free_fire_server_url && gs()->free_fire_server_api_key) {  
                            $this->autoTopup($order, $variation->provider_product_id);  
                        } else {  
                            $this->sendNotification("New Order: ID {$order->id}, Package {$order->variation->title}, Amount {$order->amount}");  
                        }  
                    } catch (Exception $e) { }  
                });  
  
                $redirect = $variation->product->isVoucher() ? route('codes') : route('orders');  
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'redirect_url' => $redirect,
                        'message' => 'Order Successful.'
                    ]);
                }
                
                return redirect($redirect)->with('message', 'Order Successful.')->with('message_type', 'success');  
            } catch (Exception $exception) {  
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $exception->getMessage()
                    ], 400);
                }
                
                return back()->with('message', $exception->getMessage())->with('message_type', 'error');  
            }  
        }  
  
        // UddoktaPay Payment  
        return $this->processUddoktaPay($variation, $orderData, $request);  
    }  
  
    private function processUddoktaPay($variation, $orderData, $request)  
    {  
        $user = Auth::user();  
        $success_url = route('payment.success'); 
        $cancel_url  = route('cancel.payment');  
  
        $requestData = [  
            'full_name'    => $user->name ?? 'Guest User',  
            'email'        => $user->email ?? 'customer@mail.com',  
            'amount'       => $orderData['amount'],  
            'metadata'     => [  
                'account_info' => $request->input('account_info'),  
                'variation_id' => $variation->id,  
                'quantity'     => $request->input('quantity', 1),  
                'user_id'      => Auth::id(), 
                'type'         => 'order'  
            ],  
            'redirect_url' => $success_url,  
            'return_type' => 'GET',
            'cancel_url'  => $cancel_url,  
        ];  
  
        try {  
            $paymentUrl = UddoktaPay::init_payment($requestData);  
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'payment_url' => $paymentUrl]);
            }
            return redirect($paymentUrl);  
        } catch (Exception $e) {  
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return back()->with('message', $e->getMessage())->with('message_type', 'error');  
        }  
    }  
  
    public function paymentSuccess(Request $request)  
    {  
        $transactionId = $request->query('transactionId') ?? $request->query('invoice_id');

        if (empty($transactionId)) { 
            return redirect()->route('orders')->with('message', 'Order place failed: Invalid transaction ID.')->with('message_type', 'error');  
        }  
  
        try {
            $data = UddoktaPay::verify_payment($transactionId);  
            
            if (isset($data['status']) && $data['status'] === 'COMPLETED') {  
                $metadata = is_string($data['metadata']) ? json_decode($data['metadata'], true) : $data['metadata'];
                
                if (!is_array($metadata) || ($metadata['type'] ?? null) !== 'order') {
                    return redirect()->route('orders')->with('message', 'Invalid metadata.')->with('message_type', 'error');
                }
                
                $variation_id = $metadata['variation_id'];
                $quantity = $metadata['quantity'] ?? 1;
                $variation = Variation::findOrFail($variation_id);

                $amount_cal = round($variation->price * $quantity, 2);  
                $profit_cal = number_format(max(0, $amount_cal - ($variation->buy_rate * $quantity)), 2, '.', '');  
      
                $orderData = [  
                    'user_id'      => $metadata['user_id'],
                    'product_id'   => $variation->product_id,  
                    'variation_id' => $variation->id,  
                    'quantity'     => $quantity,  
                    'amount'       => $amount_cal,  
                    'profit'       => $profit_cal,  
                    'account_info' => $metadata['account_info'] ?? null,  
                ];  
      
                $vouchers = null;  
                if ($variation->product->isVoucher()) {  
                    $vouchers = Voucher::where('status', Status::AVAILABLE)->where('variation_id', $variation->id)->limit($quantity)->get();
                    if ($vouchers->count() < $quantity) throw new Exception('Voucher out of stock.');
                }  
  
                DB::transaction(function () use ($orderData, $vouchers, $quantity, $variation) {  
                    $order = Order::create($orderData);  
                    $order->status = $order->product->isVoucher() ? Status::COMPLETE : Status::PROCESSING;  
                    $order->save();  
  
                    if ($order->product->isVoucher()) {  
                        $codes = [];  
                        foreach ($vouchers as $v) {  
                            $v->update(['status' => Status::SOLD, 'order_id' => $order->id]);  
                            $codes[] = $v->code;
                        }  
                        $order->update(['voucher_code' => implode(', ', $codes)]);  
                        $variation->decrement('stock', $vouchers->count());
                    } else {  
                        $variation->decrement('stock', $quantity);
                    }  
  
                    $this->handleReseller($order);  
                    
                    try {
                        if ($order->product->isTopup() && $order->variation->isAutomatic() && gs()->enable_auto_topup && gs()->topup_provider === TopupProvider::HUMAYUN && $order->status === Status::PROCESSING) {
                            $this->sendHumayunWebhook($order);
                        } elseif ($order->product->isTopup() && $order->variation->isAutomatic() && gs()->enable_auto_topup) {
                            $this->autoTopup($order, $variation->provider_product_id);
                        } else {
                            $this->sendNotification("New Order #{$order->id}");
                        }
                    } catch (Exception $e) {}
                });  
  
                $redirect = $variation->product->isVoucher() ? route('codes') : route('orders');  
                return redirect($redirect)->with('message', 'Order Successful.')->with('message_type', 'success');  
            }
            return redirect()->route('orders')->with('message', 'Payment not completed.')->with('message_type', 'error');
        } catch (Exception $e) {
            return redirect()->route('orders')->with('message', 'Error: ' . $e->getMessage())->with('message_type', 'error');
        }
    }  
  
    private function handleReseller(Order $order)  
    {  
        $user = $order->user;  
        if ($user && method_exists($user, 'isReseller') && $user->isReseller()) {  
            $percentageAmount = ($order->amount * $order->product->percentage) / 100;  
            $user->increment('balance', $percentageAmount);  
        }  
    }  
  
    private function autoTopup($order, $name)  
    {  
        $quantity = 1;  
        $url = route('auto.topup.webhook') . '?order=' . $order->id;  
        Http::withToken(gs()->free_fire_server_api_key)->post(gs()->free_fire_server_url, [  
            'quantity' => $quantity, 'selectedPackage' => ['id' => 1, 'tag_line' => $name], 'account_info' => $order->account_info, 'url' => $url, 'order_id' => $order->id, 'user_id' => 'nouser',  
        ]);  
        $order->update(['status' => OrderStatus::AUTOPROCESSING]);  
    }  
  
    private function sendHumayunWebhook(Order $order)
    {
        try {
            $uid = $order->account_info['player_id'] ?? null;
            if (!$uid) return;
            $webhookUrl = rtrim(gs()->humayun_server_url, '/') . '/webhook/humayun/order';
            $response = Http::post($webhookUrl, [
                'api_key' => gs()->humayun_api_key, 'order_id' => $order->id, 'uid' => $uid, 'variation_name' => $order->variation->title, 'status' => $order->status,
            ]);
            if ($response->successful()) {
                $order->update(['status' => OrderStatus::AUTOPROCESSING]);
            }
        } catch (Exception $e) { }
    }

    private function sendNotification($message)  
    {  
        $botToken = "7326710462:AAFrJM9S7eXMMEEMwsVAWJeIacMH63VbYYk";  
        $chatId = "-4810915480";  
        Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", ['chat_id' => $chatId, 'text' => $message]);  
    }  
}