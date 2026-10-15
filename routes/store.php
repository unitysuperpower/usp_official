<?php

use App\Http\Controllers\Store\AdminStoreController as Admin;
use App\Http\Controllers\Store\StoreController as Store;
use App\Http\Controllers\Store\StoreGatewayController as Gateway;
use App\Http\Middleware\PrivateStoreResponse;
use Illuminate\Support\Facades\Route;

Route::get('products', [Store::class,'index'])->name('store.catalog');
Route::get('products/{slug}', [Store::class,'show'])->name('store.product');
Route::get('products/releases/{release}/download',[Store::class,'download'])->middleware([PrivateStoreResponse::class,'throttle:30,1'])->name('store.download');
Route::middleware(['auth',PrivateStoreResponse::class])->prefix('account/products')->group(function () {
    Route::get('/',[Store::class,'library']);
    Route::get('plans/{plan}',[Store::class,'confirm']);
    Route::post('plans/{plan}/checkout',[Store::class,'checkout'])->middleware('throttle:10,1');
    Route::get('orders/{order:reference}',[Store::class,'order']);
    Route::post('orders/{order:reference}/cancel',[Store::class,'cancel']);
    Route::post('orders/{order:reference}/payments',[Store::class,'payment'])->middleware('throttle:10,1');
    Route::post('orders/{order:reference}/checkout',[Gateway::class,'checkout'])->middleware('throttle:10,1');
    Route::get('payments/{payment}/proof',[Store::class,'proof']);
    Route::get('licenses/{license}',[Store::class,'license']);
    Route::post('licenses/{license}/key',[Store::class,'key'])->middleware('throttle:15,1');
    Route::delete('licenses/{license}/activations/{activation}',[Store::class,'deactivate']);
});
Route::middleware(['auth','admin',PrivateStoreResponse::class])->prefix('admin/store')->group(function () {
    Route::get('products',[Admin::class,'index']);
    Route::get('products/create',[Admin::class,'edit']);
    Route::post('products',[Admin::class,'save']);
    Route::get('products/{product}',[Admin::class,'show'])->whereNumber('product');
    Route::get('products/{product}/edit',[Admin::class,'edit']);
    Route::delete('products/{product}',[Admin::class,'destroy']);
    Route::delete('products/{product}/plans/{plan}',[Admin::class,'destroyPlan']);
    Route::delete('releases/{release}',[Admin::class,'destroyRelease']);
    Route::put('products/{product}',[Admin::class,'save']);
    Route::post('products/{product}/plans',[Admin::class,'plan']);
    Route::put('products/{product}/plans/{plan}',[Admin::class,'plan']);
    Route::post('products/{product}/releases',[Admin::class,'release']);
    Route::patch('releases/{release}',[Admin::class,'visibility']);
    Route::put('settings',[Admin::class,'settings'])->middleware('throttle:10,1');
    Route::get('orders',[Admin::class,'orders']);
    Route::get('orders/{order:reference}',[Admin::class,'order']);
    Route::patch('payments/{payment}',[Admin::class,'review']);
    Route::get('licenses',[Admin::class,'licenses']);
    Route::patch('licenses/{license}',[Admin::class,'license']);
});
