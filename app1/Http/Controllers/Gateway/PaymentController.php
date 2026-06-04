<?php

namespace App\Http\Controllers\Gateway;

use App\Library\UddoktaPay;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function deposit(Request $request) {
    
      $request->validate([
          'amount' => 'required|numeric|min:1'
      ]);
      
      $user = Auth::user();
      $amount = $request->amount;
      
      $success_url = route('payment');
      $cancel_url = route('cancel.payment');
      
      $requestData = [
            'full_name'    => $user->name,
            'email'        => $user->email,
            'amount'       => $amount,
            'metadata'     => [
                'amount' => $amount,
            ],
            'redirect_url'  => $success_url,
            'return_type'   => 'GET',
            'cancel_url'    => $cancel_url,
        ];

        try {
            $paymentUrl = UddoktaPay::init_payment($requestData);
            return redirect($paymentUrl);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
    
    public function payment(Request $request) {
      
      $user = Auth::user();
      if (empty($request->invoice_id)) {
            die('Invalid Request');
        }
        $data = UddoktaPay::verify_payment($request->invoice_id);
        if (isset($data['status']) && $data['status'] == 'COMPLETED') {
            $amount = $data['metadata']['amount'];
            $user->increment('balance', $amount);
            return redirect()->route('addfunds')->with('message', 'Add money success.')->with('message_type', 'success');
        } else {
            return redirect()->route('addfunds')->with('message', 'Add money failed.')->with('message_type', 'error');
        }
    }
    
    public function payment_cancel(Request $request) {
      return redirect()->route('home');
    }
}
