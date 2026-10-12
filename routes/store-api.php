<?php
use App\Http\Controllers\Store\LicenseApiController;
use App\Http\Middleware\PrivateStoreResponse;
use Illuminate\Support\Facades\Route;

Route::middleware([PrivateStoreResponse::class, 'throttle:60,1'])->group(function () {
    Route::post('licenses/v1', [LicenseApiController::class,'handle']);
    Route::get('licenses/packages/{release}/{activation}',[LicenseApiController::class,'package'])->name('store.package');
});
