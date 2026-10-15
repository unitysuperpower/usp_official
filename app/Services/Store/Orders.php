<?php

namespace App\Services\Store;

use App\Models\ProductLicense;
use App\Models\ProductOrder;
use App\Models\ProductPayment;
use App\Models\ProductPlan;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\Education;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Orders
{
    public function create(User $user, ProductPlan $plan, ?ProductLicense $renewal = null): ProductOrder
    {
        return DB::transaction(function () use ($user, $plan, $renewal) {
            // Serialize checkout creation for this account, including double clicks.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $plan = ProductPlan::with('product')->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            Education::ensure($plan->published && $plan->product->published, 'This plan is not available.');
            if ($renewal) {
                Education::ensure($renewal->user_id === $user->id && $renewal->digital_product_id === $plan->digital_product_id
                    && $renewal->site_limit === $plan->site_limit && $renewal->status === 'active', 'Choose a renewal plan with the same website limit.');
            }
            $rate = StoreSetting::find(1)?->usd_pkr_rate;
            Education::ensure($rate > 0, 'Checkout is being configured. Please contact us for assistance.', 'payment');
            $existing = ProductOrder::where('user_id', $user->id)->where('product_plan_id', $plan->id)
                ->where('renewal_license_id', $renewal?->id)->where('status', 'pending')
                ->where(fn ($q) => $q->where('quote_expires_at', '>', now())->orWhereHas('payments', fn ($q) => $q->whereIn('status', ['pending','review_required'])))
                ->latest()->first();
            if ($existing) { return $existing; }
            return ProductOrder::create([
                'reference' => (string) Str::uuid(), 'user_id' => $user->id, 'digital_product_id' => $plan->digital_product_id,
                'product_plan_id' => $plan->id, 'product_name' => $plan->product->name, 'plan_name' => $plan->name,
                'usd_minor' => $plan->price_minor, 'usd_pkr_rate' => $rate,
                'amount_minor' => (int) round($plan->price_minor * (float) $rate), 'currency' => 'PKR',
                'term_months' => $plan->term_months, 'site_limit' => $plan->site_limit,
                'renewal_license_id' => $renewal?->id, 'quote_expires_at' => now()->addDay(),
            ]);
        });
    }

    public function locked(ProductOrder $order, callable $callback): mixed
    {
        return DB::transaction(fn () => $callback(ProductOrder::whereKey($order->id)->lockForUpdate()->firstOrFail()));
    }

    public function payable(ProductOrder $order): void
    {
        Education::ensure($order->status === 'pending' && $order->quote_expires_at->isFuture(), 'This order is paid, cancelled, or its quote has expired.');
        Education::ensure(! $order->payments()->whereIn('status', ['pending','approved','review_required'])->exists(), 'An existing payment needs review before another attempt.');
    }

    // The caller must hold the order lock. Unique order/license linkage prevents duplicate fulfillment.
    public function approve(ProductOrder $order, ProductPayment $payment, array $review): void
    {
        Education::ensure($order->status === 'pending', 'This order cannot be fulfilled again.');
        $order->update(['status' => 'paid', 'paid_at' => now()]);
        app(Licensing::class)->issue($order);
        $payment->update(array_merge($review, ['status' => 'approved', 'reviewed_at' => now()]));
        \App\Support\SiteNotifications::orderConfirmed($order->fresh());
    }
}
