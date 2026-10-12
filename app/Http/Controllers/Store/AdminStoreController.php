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
            'products' => DigitalProduct::withCount('plans','releases')->when($search, fn ($q) => $q->where('name','like','%'.$search.'%'))->latest()->paginate(20)->withQueryString(),
            'stats' => ['products' => DigitalProduct::count(), 'activeLicenses' => ProductLicense::where('status','active')->where('expires_at','>',now())->count(), 'pendingPayments' => ProductPayment::whereIn('status',['pending','review_required'])->count(), 'paidOrders' => ProductOrder::where('status','paid')->count()],
            'rate' => StoreSetting::find(1)?->usd_pkr_rate,
        ]);
    }

    public function edit(?DigitalProduct $product = null)
    {
        return ReactPage::render('admin.store.editor', ['product' => $product?->load('plans','releases')]);
    }

    public function save(Request $request, ?DigitalProduct $product = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150', 'slug' => ['required','string','max:100','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('digital_products')->ignore($product?->id)],
            'summary' => 'required|string|max:500', 'description' => 'nullable|string|max:20000',
            'free_features' => 'nullable|string|max:4000','pro_features' => 'nullable|string|max:4000', 'published' => 'nullable|boolean',
        ]);
        if ($product) { Education::ensure($data['slug'] === $product->slug, 'The product identifier cannot change after creation.', 'slug'); }
        foreach (['free_features','pro_features'] as $field) { $data[$field] = array_values(array_filter(array_map('trim', explode("\n", $data[$field] ?? '')))); }
        $data['published'] = $request->boolean('published');
        $product ? $product->update($data) : $product = DigitalProduct::create($data);
        return redirect('/admin/store/products/'.$product->id.'/edit')->with('success','Product saved.');
    }

    public function plan(Request $request, DigitalProduct $product, ?ProductPlan $plan = null)
    {
        abort_if($plan && $plan->digital_product_id !== $product->id,404);
        $data = $request->validate([
            'name'=>'required|string|max:100', 'price'=>'required|numeric|decimal:0,2|min:0.01|max:100000',
            'term_months'=>'required|integer|in:1,12', 'site_limit'=>'nullable|integer|min:1|max:100000', 'published'=>'nullable|boolean',
        ]);
        $data['price_minor'] = (int) round($data['price'] * 100); unset($data['price']);
        $data['published'] = $request->boolean('published');
        $plan ? $plan->update($data) : $product->plans()->create($data);
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
        $data=$request->validate(['status'=>'nullable|in:pending,paid,cancelled','search'=>'nullable|string|max:100']);
        $query=ProductOrder::with('user')->withCount('payments')->latest();
        if (! empty($data['status'])) { $query->where('status',$data['status']); }
        if (! empty($data['search'])) { $query->where(fn ($q)=>$q->where('reference','like','%'.$data['search'].'%')->orWhereHas('user',fn ($q)=>$q->where('email','like','%'.$data['search'].'%')->orWhere('name','like','%'.$data['search'].'%'))); }
        return ReactPage::render('admin.store.orders',['orders'=>$query->paginate(20)->withQueryString()]);
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
            else { $payment->update($review); }
        });
        return back()->with('success','Payment reviewed. Approved orders receive their license automatically.');
    }

    public function licenses(Request $request)
    {
        return ReactPage::render('admin.store.licenses',['licenses'=>ProductLicense::with('product','user')->withCount('activations')->latest()->paginate(20)]);
    }

    public function license(Request $request, ProductLicense $license)
    {
        $data=$request->validate(['status'=>'required|in:active,revoked','admin_note'=>'required|string|min:8|max:2000']);
        DB::transaction(function () use ($license,$data) { ProductLicense::whereKey($license->id)->lockForUpdate()->firstOrFail()->update($data); });
        return back()->with('success','License status updated. Revoked licenses cannot activate or download Pro updates.');
    }
}
