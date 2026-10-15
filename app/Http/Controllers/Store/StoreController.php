<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use App\Models\LicenseActivation;
use App\Models\ProductLicense;
use App\Models\ProductOrder;
use App\Models\ProductPayment;
use App\Models\ProductPlan;
use App\Models\ProductRelease;
use App\Models\StoreSetting;
use App\Services\JazzCashService;
use App\Services\Store\Orders;
use App\Support\Education;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function index()
    {
        return ReactPage::render('store.catalog', ['products' => DigitalProduct::where('published', true)->with(['plans' => fn ($q) => $q->where('published', true)->orderBy('price_minor')])->paginate(12)]);
    }

    public function show(string $slug)
    {
        $product = DigitalProduct::where('slug', $slug)->where('published', true)->firstOrFail();
        return ReactPage::render('store.product', [
            'product' => $product->load(['plans' => fn ($q) => $q->where('published', true)->orderBy('price_minor')]),
            'freeRelease' => $product->releases()->where('published', true)->where('edition', 'free')->latest('id')->first(),
            'checkoutReady' => (float) StoreSetting::find(1)?->usd_pkr_rate > 0 && (count(Education::methods()) > 0 || app(JazzCashService::class)->configured()),
        ]);
    }

    public function library(Request $request)
    {
        return ReactPage::render('store.library', [
            'licenses' => ProductLicense::where('user_id', $request->user()->id)->with('product')->withCount('activations')->latest()->paginate(12, ['*'], 'licenses_page'),
            'orders' => ProductOrder::where('user_id', $request->user()->id)->latest()->paginate(12, ['*'], 'orders_page'),
        ]);
    }

    public function confirm(Request $request, ProductPlan $plan)
    {
        abort_unless($plan->published && $plan->product->published, 404);
        $request->validate(['renewal' => 'nullable|integer']);
        $renewal = $request->filled('renewal') ? ProductLicense::where('user_id', $request->user()->id)->findOrFail($request->input('renewal')) : null;
        if ($renewal) { abort_unless($renewal->digital_product_id === $plan->digital_product_id && $renewal->site_limit === $plan->site_limit && $renewal->status === 'active', 404); }
        return ReactPage::render('store.checkout', ['plan' => $plan->load('product'), 'rate' => StoreSetting::find(1)?->usd_pkr_rate, 'renewal' => $renewal]);
    }

    public function checkout(Request $request, ProductPlan $plan, Orders $orders)
    {
        $data = $request->validate(['renewal_license_id' => 'nullable|integer']);
        $renewal = ! empty($data['renewal_license_id']) ? ProductLicense::where('user_id', $request->user()->id)->findOrFail($data['renewal_license_id']) : null;
        $order = $orders->create($request->user(), $plan, $renewal);
        return redirect('/account/products/orders/'.$order->reference);
    }

    public function order(Request $request, ProductOrder $order)
    {
        $this->owner($request, $order);
        return ReactPage::render('store.order', [
            'order' => $order->load('payments','license'), 'methods' => Education::methods(),
            'onlineReady' => app(JazzCashService::class)->configured(),
            'licenseId' => $order->renewal_license_id ?? $order->license?->id,
        ]);
    }

    public function cancel(Request $request, ProductOrder $order, Orders $orders)
    {
        $this->owner($request, $order);
        $orders->locked($order, function ($order) {
            Education::ensure($order->status === 'pending' && ! $order->payments()->whereIn('status', ['pending','approved','review_required'])->exists(), 'Resolve the existing payment before cancelling.');
            $order->update(['status' => 'cancelled']);
        });
        return back()->with('success', 'Order cancelled.');
    }

    public function payment(Request $request, ProductOrder $order, Orders $orders)
    {
        $this->owner($request, $order);
        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(Education::methods()))],
            'transaction_reference' => ['required','string','min:4','max:100','regex:/^[A-Za-z0-9-]+$/', Rule::unique('product_payments')->where('method', $request->input('method'))],
            'proof' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);
        // Store outside the public disk; only this account and admins may download it.
        $path = $request->file('proof')->store('products/proofs', 'local');
        abort_unless($path, 503);
        try {
            $orders->locked($order, function ($fresh) use ($orders, $data, $path) {
                $orders->payable($fresh);
                $payment = $fresh->payments()->create([
                    'method' => $data['method'], 'transaction_reference' => $data['transaction_reference'],
                    'proof_path' => $path, 'reference' => 'M'.strtoupper(bin2hex(random_bytes(12))),
                ]);
                \App\Support\SiteNotifications::paymentReceived($fresh, $payment);
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Payment submitted. Your license will appear after verification.');
    }

    public function proof(Request $request, ProductPayment $payment)
    {
        abort_unless($request->user()->is_admin || $payment->order->user_id === $request->user()->id, 404);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);
        return Storage::disk('local')->download($payment->proof_path, 'payment-proof.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function license(Request $request, ProductLicense $license)
    {
        $this->owner($request, $license);
        return ReactPage::render('store.license', [
            'license' => $license->load('product','activations'),
            'plans' => $license->product->plans()->where('published', true)->where('site_limit', $license->site_limit)->get(),
            'releases' => $license->product->releases()->where('published', true)->latest('id')->take(10)->get(),
        ]);
    }

    public function key(Request $request, ProductLicense $license)
    {
        $this->owner($request, $license);
        return response()->json(['key' => $license->key_secret]);
    }

    public function deactivate(Request $request, ProductLicense $license, LicenseActivation $activation)
    {
        $this->owner($request, $license);
        abort_unless($activation->product_license_id === $license->id, 404);
        DB::transaction(function () use ($license, $activation) {
            ProductLicense::whereKey($license->id)->lockForUpdate()->firstOrFail();
            $activation->delete();
        });
        return back()->with('success', 'Website released. You can activate another installation.');
    }

    public function download(Request $request, ProductRelease $release)
    {
        abort_unless($release->published, 404);
        if ($release->edition !== 'free') {
            abort_unless($request->user() && ProductLicense::where('user_id', $request->user()->id)
                ->where('digital_product_id', $release->digital_product_id)->where('status', 'active')
                ->where('expires_at', '>', now())->exists(), 403);
        } else { abort_unless($release->product->published, 404); }
        abort_unless(Storage::disk('local')->exists($release->package_path), 404);
        return Storage::disk('local')->download($release->package_path, $release->product->slug.'-'.$release->version.'.zip', ['X-Content-Type-Options' => 'nosniff']);
    }

    private function owner(Request $request, ProductOrder|ProductLicense $item): void
    {
        abort_unless($item->user_id === $request->user()->id, 404);
    }
}
