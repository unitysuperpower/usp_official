<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\LicenseActivation;
use App\Models\ProductRelease;
use App\Services\Store\Licensing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class LicenseApiController extends Controller
{
    public function handle(Request $request, Licensing $licensing)
    {
        $data = $request->validate([
            'action' => 'required|in:activate_license,check_license,deactivate_license,update',
            'product' => 'required|string|max:100|regex:/^[a-z0-9-]+$/',
            'license' => 'required|string|max:256', 'url' => 'required|string|max:512',
            'token' => 'nullable|string|max:128',
        ]);
        $result = $licensing->request($data);
        if ($data['action'] === 'update' && $result['success']) {
            $release = ProductRelease::whereHas('product', fn ($q) => $q->where('slug', $data['product']))
                ->where('published', true)->where('edition', 'pro')->latest('id')->first();
            $result['update'] = $release ? [
                'version' => $release->version, 'slug' => $data['product'],
                'url' => url('/products/'.$data['product']), 'changelog' => $release->changelog,
                'package' => URL::temporarySignedRoute('store.package', now()->addMinutes(30), ['release' => $release->id, 'activation' => $result['activation_id']]),
            ] : null;
        }
        unset($result['activation_id']);
        return response()->json($result);
    }

    public function package(Request $request, ProductRelease $release, LicenseActivation $activation)
    {
        abort_unless($request->hasValidSignature() && $release->published && $release->edition === 'pro'
            && $activation->license->usable() && $activation->license->digital_product_id === $release->digital_product_id, 403);
        abort_unless(Storage::disk('local')->exists($release->package_path), 404);
        return Storage::disk('local')->download($release->package_path, $release->product->slug.'.zip', ['X-Content-Type-Options' => 'nosniff']);
    }
}
