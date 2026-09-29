<?php

namespace App\Http\Controllers\Education;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Services\JazzCashService;
use App\Support\Education;
use Illuminate\Http\Request;

class JazzCashController extends Controller
{
    public function checkout(Request $request, CourseEnrollment $enrollment, JazzCashService $gateway)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        Education::ensure($gateway->configured(), 'Online payment is not configured. Please use an available manual payment method.', 'payment');
        $payment = Education::locked($enrollment, function ($item) use ($gateway) {
            Education::ensure($item->status === 'accepted' && $item->fee_minor > 0 && $item->currency === 'PKR', 'This enrollment is not eligible for checkout.');
            $pending = $item->payments()->whereIn('status', ['pending', 'approved', 'review_required'])->first();
            if ($pending) {
                Education::ensure($pending->method === 'jazzcash_online' && $pending->status === 'pending' && $pending->expires_at?->isFuture() && $pending->gateway_environment === $gateway->setting('environment'), 'An existing payment must be resolved before paying again.');

                return $pending;
            }

            return $item->payments()->create([
                'method' => 'jazzcash_online', 'reference' => 'T'.strtoupper(bin2hex(random_bytes(9))),
                'amount_minor' => $item->fee_minor, 'currency' => $item->currency,
                'gateway_environment' => $gateway->setting('environment'), 'expires_at' => now()->addMinutes(30),
            ]);
        });

        return response()->view('education.checkout', ['payment' => $payment, 'fields' => $gateway->fields($payment), 'endpoint' => $gateway->setting('checkout_url')])
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function callback(Request $request, JazzCashService $gateway)
    {
        abort_if(app()->isProduction() && $gateway->setting('environment') !== 'live', 503);
        $fields = $request->post();
        abort_unless($gateway->valid($fields), 400, 'Invalid payment signature.');
        $data = $request->validate([
            'pp_TxnRefNo' => 'required|string|max:100', 'pp_Amount' => 'required|regex:/^[0-9]+$/',
            'pp_TxnCurrency' => 'required|in:PKR', 'pp_MerchantID' => 'required|string',
            'pp_ResponseCode' => 'required|string|max:20', 'pp_BillReference' => 'required|string',
            'pp_RetreivalReferenceNo' => 'nullable|string|max:100',
        ]);
        abort_unless(hash_equals((string) $gateway->setting('merchant_id'), $data['pp_MerchantID']), 400);
        $payment = CoursePayment::where('method', 'jazzcash_online')->where('reference', $data['pp_TxnRefNo'])->firstOrFail();
        abort_unless($payment->gateway_environment === $gateway->setting('environment') && (string) $payment->amount_minor === ltrim($data['pp_Amount'], '0') && $data['pp_TxnCurrency'] === $payment->currency && $data['pp_BillReference'] === 'EDU'.$payment->course_enrollment_id, 400, 'Payment details do not match.');
        Education::locked($payment->enrollment, function ($enrollment) use ($payment, $data) {
            $payment = CoursePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            // Replayed success and later failure messages cannot undo a paid enrollment.
            if (in_array($payment->status, ['approved', 'resolved'], true)) {
                return;
            }
            $values = ['gateway_code' => $data['pp_ResponseCode'], 'gateway_reference' => $data['pp_RetreivalReferenceNo'] ?? null];
            if ($data['pp_ResponseCode'] === '000') {
                if ($enrollment->status === 'accepted' && ! $enrollment->payments()->where('status', 'approved')->exists()) {
                    Education::paid($enrollment);
                    $values += ['status' => 'approved', 'reviewed_at' => now(), 'review_note' => 'Confirmed by signed JazzCash response.'];
                } else {
                    // A late success after reconciliation must remain visible for review.
                    $values += ['status' => 'review_required', 'review_note' => 'JazzCash reported receipt after this enrollment changed. Reconcile in the merchant portal.'];
                }
            } elseif ($payment->status === 'pending') {
                // Non-success responses can represent pending transactions. Do not
                // permit a second payment until the merchant resolves the attempt.
                $values += ['review_note' => 'JazzCash has not confirmed payment. Contact the education team with this reference.'];
            }
            $payment->update($values);
        });

        return response()->view('education.payment-result')->header('Cache-Control', 'private, no-store');
    }
}
