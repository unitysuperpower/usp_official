<?php

namespace App\Services;

use App\Models\CoursePayment;

class JazzCashService
{
    public function setting(string $key): mixed
    {
        return \App\Models\EducationSetting::gatewayConfig()[$key] ?? null;
    }

    public function configured(): bool
    {
        $host = $this->setting('environment') === 'live' ? 'payments.jazzcash.com.pk' : 'sandbox.jazzcash.com.pk';

        return (! app()->isProduction() || $this->setting('environment') === 'live')
            && $this->setting('enabled')
            && in_array($this->setting('environment'), ['sandbox', 'live'], true)
            && filled($this->setting('merchant_id')) && filled($this->setting('password')) && filled($this->setting('integrity_salt'))
            && parse_url($this->setting('checkout_url') ?? '', PHP_URL_SCHEME) === 'https'
            && parse_url($this->setting('checkout_url') ?? '', PHP_URL_HOST) === $host
            && parse_url($this->setting('return_url') ?? '', PHP_URL_SCHEME) === 'https'
            && parse_url($this->setting('return_url') ?? '', PHP_URL_PATH) === '/education/jazzcash/return';
    }

    public function hash(array $fields): string
    {
        $fields = array_filter($fields, fn ($value, $key) => str_starts_with(strtolower($key), 'pp') && $key !== 'pp_SecureHash' && is_scalar($value) && (string) $value !== '', ARRAY_FILTER_USE_BOTH);
        ksort($fields, SORT_STRING);
        $message = $this->setting('integrity_salt').'&'.implode('&', $fields);

        return strtoupper(hash_hmac('sha256', mb_convert_encoding($message, 'ISO-8859-1', 'UTF-8'), $this->setting('integrity_salt')));
    }

    public function valid(array $fields): bool
    {
        if (! filled($this->setting('integrity_salt')) || ! is_string($fields['pp_SecureHash'] ?? null) || ! preg_match('/^[a-fA-F0-9]{64}$/', $fields['pp_SecureHash'])) {
            return false;
        }
        foreach ($fields as $key => $value) {
            if (str_starts_with(strtolower($key), 'pp') && ! is_scalar($value)) {
                return false;
            }
        }

        return hash_equals($this->hash($fields), strtoupper($fields['pp_SecureHash']));
    }

    public function fields(CoursePayment $payment): array
    {
        // JazzCash HTTP POST mobile-wallet flow. Credentials are required by the
        // gateway form; the signing salt is never sent to the browser.
        $fields = [
            'pp_Version' => '1.1', 'pp_TxnType' => 'MWALLET', 'pp_Language' => 'EN',
            'pp_MerchantID' => $this->setting('merchant_id'), 'pp_Password' => $this->setting('password'),
            'pp_BankID' => 'TBANK', 'pp_ProductID' => 'RETL',
            'pp_TxnRefNo' => $payment->reference, 'pp_Amount' => (string) $payment->amount_minor,
            'pp_TxnCurrency' => $payment->currency,
            'pp_TxnDateTime' => $payment->created_at->copy()->timezone('Asia/Karachi')->format('YmdHis'),
            'pp_TxnExpiryDateTime' => $payment->expires_at->copy()->timezone('Asia/Karachi')->format('YmdHis'),
            'pp_BillReference' => 'EDU'.$payment->course_enrollment_id,
            'pp_Description' => 'USP Education course enrollment',
            'pp_ReturnURL' => $this->setting('return_url'),
        ];
        $fields['pp_SecureHash'] = $this->hash($fields);

        return $fields;
    }
}
