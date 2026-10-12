<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\EducationSetting;
use App\Models\ProductOrder;
use App\Models\ProductPayment;
use App\Services\JazzCashService;
use App\Services\Store\Orders;
use App\Support\Education;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreGatewayController extends Controller
{
    public function checkout(Request $request, ProductOrder $order, JazzCashService $gateway, Orders $orders)
    {
        abort_unless($order->user_id === $request->user()->id,404);
        EducationSetting::firstOrCreate(['id'=>1]);
        return DB::transaction(function () use ($order,$gateway,$orders) {
            EducationSetting::whereKey(1)->lockForUpdate()->firstOrFail();
            Education::ensure($gateway->configured(),'Online checkout is not configured. Choose an available manual method.','payment');
            $payment=$orders->locked($order,function ($fresh) use ($orders,$gateway) {
                Education::ensure($fresh->status==='pending','This order is no longer payable.');
                $pending=$fresh->payments()->whereIn('status',['pending','approved','review_required'])->first();
                if ($pending) {
                    Education::ensure($pending->method==='jazzcash_online' && $pending->status==='pending' && $pending->expires_at?->isFuture() && $pending->gateway_environment===$gateway->setting('environment'),'An existing payment must be reconciled first.');
                    return $pending;
                }
                $orders->payable($fresh);
                return $fresh->payments()->create(['method'=>'jazzcash_online','reference'=>'S'.strtoupper(bin2hex(random_bytes(9))),'gateway_environment'=>$gateway->setting('environment'),'expires_at'=>now()->addMinutes(30)]);
            });
            $fields=[
                'pp_Version'=>'1.1','pp_TxnType'=>'MWALLET','pp_Language'=>'EN',
                'pp_MerchantID'=>$gateway->setting('merchant_id'),'pp_Password'=>$gateway->setting('password'),
                'pp_BankID'=>'TBANK','pp_ProductID'=>'RETL','pp_TxnRefNo'=>$payment->reference,
                'pp_Amount'=>(string)$order->amount_minor,'pp_TxnCurrency'=>'PKR',
                'pp_TxnDateTime'=>$payment->created_at->copy()->timezone('Asia/Karachi')->format('YmdHis'),
                'pp_TxnExpiryDateTime'=>$payment->expires_at->copy()->timezone('Asia/Karachi')->format('YmdHis'),
                'pp_BillReference'=>'STORE'.$order->id,'pp_Description'=>'USP plugin license',
                // Same merchant-approved return URL; the signed bill prefix routes fulfillment.
                'pp_ReturnURL'=>$gateway->setting('return_url'),
            ];
            $fields['pp_SecureHash']=$gateway->hash($fields);
            return response()->view('store.checkout',['fields'=>$fields,'endpoint'=>$gateway->setting('checkout_url'),'order'=>$order]);
        });
    }

    public function callback(Request $request, JazzCashService $gateway, Orders $orders)
    {
        abort_if(app()->isProduction() && $gateway->setting('environment')!=='live',503);
        abort_unless($gateway->valid($request->post()),400,'Invalid signature.');
        $data=$request->validate([
            'pp_TxnRefNo'=>'required|string|max:100','pp_Amount'=>'required|regex:/^[0-9]+$/','pp_TxnCurrency'=>'required|in:PKR',
            'pp_MerchantID'=>'required|string','pp_ResponseCode'=>'required|string|max:20','pp_BillReference'=>'required|string',
            'pp_RetreivalReferenceNo'=>'nullable|string|max:100',
        ]);
        abort_unless(hash_equals((string)$gateway->setting('merchant_id'),$data['pp_MerchantID']),400);
        $payment=ProductPayment::where('method','jazzcash_online')->where('reference',$data['pp_TxnRefNo'])->firstOrFail();
        $order=$payment->order;
        abort_unless($payment->gateway_environment===$gateway->setting('environment') && (string)$order->amount_minor===ltrim($data['pp_Amount'],'0') && $data['pp_BillReference']==='STORE'.$order->id,400);
        $orders->locked($order,function ($order) use ($payment,$data,$orders) {
            $payment=ProductPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->status==='approved') { return; }
            $values=['gateway_code'=>$data['pp_ResponseCode'],'gateway_reference'=>$data['pp_RetreivalReferenceNo']??null];
            if ($data['pp_ResponseCode']==='000') {
                if ($order->status==='pending' && $payment->status==='pending') {
                    $orders->approve($order,$payment,$values+['review_note'=>'Confirmed by signed JazzCash response.']);
                    return;
                }
                $values+=['status'=>'review_required','review_note'=>'Late payment received after reconciliation. Review in merchant portal.'];
            } elseif ($payment->status==='pending') { $values+=['review_note'=>'JazzCash has not confirmed receipt. Reconcile before retrying.']; }
            $payment->update($values);
        });
        return response()->view('store.payment-result')->header('Cache-Control','private, no-store');
    }
}
