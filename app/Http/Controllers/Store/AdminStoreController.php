<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use App\Models\ProductLicense;
use App\Models\ProductOrder;
use App\Models\ProductPayment;
use App\Models\ProductPlan;
use App\Models\ProductRelease;
use App\Models\StoreSetting;
use App\Services\Store\Orders;
use App\Services\Store\Packages;
use App\Support\Education;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminStoreController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr((string) $request->query('search'), 0, 100);
        return ReactPage::render('admin.store.products', [
            'products' => DigitalProduct::withCount('plans','releases','orders')->when($search, fn ($q) => $q->where('name','like','%'.$search.'%'))->latest()->paginate(20)->withQueryString(),
            'stats' => ['products' => DigitalProduct::count(), 'activeLicenses' => ProductLicense::where('status','active')->where('expires_at','>',now())->count(), 'pendingPayments' => ProductPayment::whereIn('status',['pending','review_required'])->count(), 'paidOrders' => ProductOrder::where('status','paid')->count()],
            'rate' => StoreSetting::find(1)?->usd_pkr_rate,
        ]);
    }

    public function show(DigitalProduct $product)
    {
        $product->load(['plans' => fn ($q) => $q->orderBy('price_minor'), 'releases' => fn ($q) => $q->latest('id')])->loadCount('orders', 'licenses');
        return ReactPage::render('admin.store.product', [
            'product' => $product,
            'stats' => [
                'paidOrders' => $product->orders()->where('status', 'paid')->count(),
                'pendingOrders' => $product->orders()->where('status', 'pending')->count(),
                'activeLicenses' => $product->licenses()->where('status', 'active')->where('expires_at', '>', now())->count(),
                'revenuePkr' => (int) $product->orders()->where('status', 'paid')->sum('amount_minor'),
            ],
            'orders' => $product->orders()->with('user')->latest()->take(10)->get(),
        ]);
    }

    /** Products with orders keep their sales and license history: unpublish them instead. */
    public function destroy(DigitalProduct $product)
    {
        Education::ensure(! $product->orders()->exists() && ! $product->licenses()->exists(), 'This product has orders or licenses, so it cannot be deleted. Unpublish it instead to hide it from the store.', 'product');
        $paths = $product->releases()->pluck('package_path')->all();
        DB::transaction(function () use ($product) {
            $product->releases()->delete();
            $product->plans()->delete();
            $product->delete();
        });
        \Illuminate\Support\Facades\Storage::disk('local')->delete($paths);
        return redirect('/admin/store/products')->with('success', 'Product "'.$product->name.'" deleted.');
    }

    public function destroyPlan(DigitalProduct $product, ProductPlan $plan)
    {
        abort_if($plan->digital_product_id !== $product->id, 404);
        Education::ensure(! ProductOrder::where('product_plan_id', $plan->id)->exists(), 'Customers have ordered this plan, so it cannot be deleted. Edit it and turn off "Available for purchase" instead.', 'plan');
        $plan->delete();
        return back()->with('success', 'Plan deleted.');
    }

    public function destroyRelease(ProductRelease $release)
    {
        $path = $release->package_path;
        $release->delete();
        \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
        return back()->with('success', 'Release '.$release->version.' deleted.');
    }

    public function edit(?DigitalProduct $product = null)
    {
        return ReactPage::render('admin.store.editor', ['product' => $product?->load('plans','releases')]);
    }

    public function save(Request $request, ?DigitalProduct $product = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150', 'slug' => ['required','string','max:100','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('digital_products')->ignore($product?->id)],
            'tagline' => 'nullable|string|max:160', 'category' => 'nullable|string|max:60',
            'summary' => 'required|string|max:500', 'description' => 'nullable|string|max:20000',
            'free_features' => 'nullable|string|max:4000','pro_features' => 'nullable|string|max:4000', 'published' => 'nullable|boolean',
            'activation_steps' => 'nullable|string|max:4000',
            'faq' => 'nullable|array|max:30', 'faq.*.q' => 'nullable|string|max:300', 'faq.*.a' => 'nullable|string|max:3000',
            'requirements.wordpress' => 'nullable|string|max:20', 'requirements.php' => 'nullable|string|max:20', 'requirements.tested' => 'nullable|string|max:20',
            'docs_url' => 'nullable|url:https|max:2000', 'support_url' => 'nullable|url:https|max:2000',
            'sort_order' => 'nullable|integer|min:0|max:10000',
            'icon' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048', 'remove_icon' => 'nullable|boolean',
            'screenshots' => 'nullable|array|max:8', 'screenshots.*' => 'image|mimes:png,jpg,jpeg,webp|max:6144',
            'captions' => 'nullable|array', 'captions.*' => 'nullable|string|max:160',
            'remove_screenshots' => 'nullable|array', 'remove_screenshots.*' => 'string',
        ], ['url' => 'Use a full https:// link.']);
        if ($product) { Education::ensure($data['slug'] === $product->slug, 'The product identifier cannot change after creation.', 'slug'); }
        $lines = fn ($text) => array_values(array_filter(array_map('trim', explode("\n", $text ?? ''))));
        $values = [
            'name' => $data['name'], 'slug' => $data['slug'], 'summary' => $data['summary'], 'description' => $data['description'] ?? null,
            'tagline' => $data['tagline'] ?? null, 'category' => ($data['category'] ?? null) ?: 'WordPress plugin',
            'free_features' => $lines($data['free_features'] ?? ''), 'pro_features' => $lines($data['pro_features'] ?? ''),
            'activation_steps' => $lines($data['activation_steps'] ?? ''),
            'faq' => collect($data['faq'] ?? [])->filter(fn ($item) => filled($item['q'] ?? null) && filled($item['a'] ?? null))->map(fn ($item) => ['q' => trim($item['q']), 'a' => trim($item['a'])])->values()->all(),
            'requirements' => array_filter(array_map(fn ($v) => $v ? trim($v) : null, $data['requirements'] ?? [])),
            'docs_url' => $data['docs_url'] ?? null, 'support_url' => $data['support_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 100, 'published' => $request->boolean('published'),
        ];

        // Images live on the public disk; old files are removed only after the new ones are stored.
        $images = app(\App\Services\ImageOptimizationService::class);
        $obsolete = [];
        if ($request->hasFile('icon') || $request->boolean('remove_icon')) {
            if ($product?->image_path) { $obsolete[] = $product->image_path; }
            $values['image_path'] = $request->hasFile('icon') ? $images->processImage($request->file('icon'), 'store/icons', false) : null;
        }
        $shots = collect($product?->screenshots ?? []);
        $remove = $data['remove_screenshots'] ?? [];
        $obsolete = array_merge($obsolete, $shots->whereIn('path', $remove)->pluck('path')->all());
        $shots = $shots->whereNotIn('path', $remove)->map(fn ($s) => ['path' => $s['path'], 'caption' => $data['captions'][$s['path']] ?? $s['caption'] ?? null])->values();
        foreach ($request->file('screenshots', []) as $file) {
            Education::ensure($shots->count() < 8, 'A product can show up to 8 screenshots.', 'screenshots');
            $shots->push(['path' => $images->processImage($file, 'store/screenshots', false), 'caption' => null]);
        }
        $values['screenshots'] = $shots->all();

        $product ? $product->update($values) : $product = DigitalProduct::create($values);
        foreach ($obsolete as $path) { $images->deleteImage($path); }
        return redirect('/admin/store/products/'.$product->id.'/edit'.($request->input('_tab') ? '?tab='.preg_replace('/[^a-z]/', '', $request->input('_tab')) : ''))->with('success','Product saved.');
    }

    public function plan(Request $request, DigitalProduct $product, ?ProductPlan $plan = null)
    {
        abort_if($plan && $plan->digital_product_id !== $product->id,404);
        $data = $request->validate([
            'name'=>'required|string|max:100', 'price'=>'required|numeric|decimal:0,2|min:0.01|max:100000',
            'term_months'=>'required|integer|in:1,3,6,12,24', 'site_limit'=>'nullable|integer|min:1|max:100000', 'published'=>'nullable|boolean',
            'description'=>'nullable|string|max:255', 'features'=>'nullable|string|max:3000', 'is_featured'=>'nullable|boolean',
        ]);
        $data['price_minor'] = (int) round($data['price'] * 100); unset($data['price']);
        $data['published'] = $request->boolean('published');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['features'] = array_values(array_filter(array_map('trim', explode("\n", $data['features'] ?? ''))));
        DB::transaction(function () use ($product, $plan, $data) {
            // One recommended plan per billing term keeps the pricing table clear.
            if ($data['is_featured']) { $product->plans()->where('term_months', $data['term_months'])->when($plan, fn ($q) => $q->whereKeyNot($plan->id))->update(['is_featured' => false]); }
            $plan ? $plan->update($data) : $product->plans()->create($data);
        });
        return back()->with('success','Plan saved. Existing order prices and licenses are unchanged.');
    }

    public function release(Request $request, DigitalProduct $product, Packages $packages)
    {
        $data = $request->validate([
            'version'=>['required','string','max:30','regex:/^\d+\.\d+\.\d+(?:-[a-zA-Z0-9.]+)?$/', Rule::unique('product_releases')->where('digital_product_id',$product->id)->where('edition',$request->input('edition'))],
            'edition'=>'required|in:free,pro', 'package'=>'required|file|mimes:zip|max:20480', 'changelog'=>'nullable|string|max:10000', 'published'=>'nullable|boolean',
        ]);
        unset($data['package']); $data['published']=$request->boolean('published');
        $latest = $product->releases()->where('edition', $data['edition'])->latest('id')->first();
        Education::ensure(! $latest || version_compare($data['version'], $latest->version, '>'), 'A new release must have a higher version than the previous release.', 'version');
        $packages->publish($product,$request->file('package'),$data);
        return back()->with('success','Release uploaded to private storage.');
    }

    public function visibility(Request $request, ProductRelease $release)
    {
        $request->validate(['published'=>'required|boolean']);
        $release->update(['published'=>$request->boolean('published')]);
        return back()->with('success','Release visibility updated.');
    }

    public function settings(Request $request)
    {
        $data=$request->validate(['usd_pkr_rate'=>'required|numeric|min:1|max:10000','current_password'=>'required|current_password']);
        StoreSetting::updateOrCreate(['id'=>1],['usd_pkr_rate'=>$data['usd_pkr_rate']]);
        return back()->with('success','Exchange rate saved. New orders use this rate; existing quotes keep their original total.');
    }

    public function orders(Request $request)
    {
        $data=$request->validate(['status'=>'nullable|in:pending,paid,cancelled,review','search'=>'nullable|string|max:100']);
        $query=ProductOrder::with(['user', 'payments' => fn ($q) => $q->latest()])->withCount('payments')->latest();
        $review = fn ($q) => $q->whereHas('payments', fn ($p) => $p->whereIn('status', ['pending','review_required']));
        if (($data['status'] ?? null) === 'review') { $review($query); }
        elseif (! empty($data['status'])) { $query->where('status',$data['status']); }
        if (! empty($data['search'])) { $query->where(fn ($q)=>$q->where('reference','like','%'.$data['search'].'%')->orWhereHas('user',fn ($q)=>$q->where('email','like','%'.$data['search'].'%')->orWhere('name','like','%'.$data['search'].'%'))); }
        $counts = ProductOrder::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all();
        $counts['review'] = $review(ProductOrder::query())->count();
        $counts['all'] = array_sum(array_intersect_key($counts, array_flip(['pending', 'paid', 'cancelled'])));
        return ReactPage::render('admin.store.orders',['orders'=>$query->paginate(20)->withQueryString(), 'counts' => $counts, 'filters' => $data,
            'revenue' => ProductOrder::where('status', 'paid')->selectRaw('currency, sum(amount_minor) as total')->groupBy('currency')->pluck('total', 'currency')]);
    }

    public function order(ProductOrder $order)
    {
        return ReactPage::render('admin.store.order',['order'=>$order->load('payments','user','license')]);
    }

    public function review(Request $request, ProductPayment $payment, Orders $orders)
    {
        $data=$request->validate(['status'=>'required|in:approved,rejected,resolved','review_note'=>'required|string|min:8|max:2000']);
        $orders->locked($payment->order,function ($order) use ($payment,$data,$request,$orders) {
            $payment=ProductPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            Education::ensure(in_array($payment->status,['pending','review_required'],true),'This payment has already been reviewed.');
            Education::ensure($payment->method !== 'jazzcash_online' || $data['status'] !== 'rejected', 'For JazzCash, reconcile in the merchant portal and mark resolved if no payment was received.');
            $review=$data+['reviewed_by'=>$request->user()->id,'reviewed_at'=>now()];
            if ($data['status']==='approved') { $orders->approve($order,$payment,$review); }
            else {
                $payment->update($review);
                if ($data['status']==='rejected') { \App\Support\SiteNotifications::paymentRejected($order,$payment->fresh()); }
            }
        });
        return back()->with('success','Payment reviewed. Approved orders receive their license automatically.');
    }

    public function licenses(Request $request)
    {
        $data = $request->validate(['status' => 'nullable|in:active,expiring,expired,revoked', 'search' => 'nullable|string|max:100']);
        $scopes = [
            'active' => fn ($q) => $q->where('status', 'active')->where('expires_at', '>', now()),
            'expiring' => fn ($q) => $q->where('status', 'active')->whereBetween('expires_at', [now(), now()->addDays(14)]),
            'expired' => fn ($q) => $q->where('status', 'active')->where('expires_at', '<=', now()),
            'revoked' => fn ($q) => $q->where('status', 'revoked'),
        ];
        $query = ProductLicense::with('product:id,name,slug','user:id,name,email')->withCount('activations')->latest()
            ->when($data['status'] ?? null, fn ($q, $s) => $scopes[$s]($q))
            ->when($data['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w->where('key_hint', 'like', "%{$s}%")->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"))));
        return ReactPage::render('admin.store.licenses',['licenses'=>$query->paginate(20)->withQueryString(), 'filters' => $data,
            'counts' => collect($scopes)->map(fn ($scope) => $scope(ProductLicense::query())->count())->put('all', ProductLicense::count())]);
    }

    public function license(Request $request, ProductLicense $license)
    {
        $data=$request->validate(['status'=>'required|in:active,revoked','admin_note'=>'required|string|min:8|max:2000']);
        DB::transaction(function () use ($license,$data) { ProductLicense::whereKey($license->id)->lockForUpdate()->firstOrFail()->update($data); });
        return back()->with('success','License status updated. Revoked licenses cannot activate or download Pro updates.');
    }
}
