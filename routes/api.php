<?php

use App\Http\Controllers\HarvestController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\CorporationController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\TranslationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('translations/{locale}', [TranslationController::class, 'show'])
    ->whereIn('locale', ['en', 'lg', 'sw'])
    ->name('translations.show');

Route::get('harvest/public/{batchUuid}', [HarvestController::class, 'publicShow'])->name('harvest.public.show');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', function (Request $request) {
        return $request->user();
    })->name('user.current');

    Route::get('product', [ProductController::class, 'list'])->name('product.list');
    Route::post('product', [ProductController::class, 'post'])->name('product.post');
    Route::delete('product/{uuid}', [ProductController::class, 'delete'])->name('product.delete');
    Route::get('warehouse', [WarehouseController::class, 'list'])->name('warehouse.list');
    Route::post('warehouse', [WarehouseController::class, 'post'])->name('warehouse.post');
    Route::get('geocoding/reverse', [GeocodingController::class, 'reverse'])->name('geocoding.reverse');
    Route::get('countries', [CountryController::class, 'list'])->name('countries.list');
    Route::get('corporations', [CorporationController::class, 'list'])->name('corporation.list');
    Route::get('corporation', [CorporationController::class, 'get'])->name('corporation.get');
    Route::post('corporation', [CorporationController::class, 'post'])->name('corporation.post');
    Route::get('harvest', [HarvestController::class, 'list'])->name('harvest.list');
    Route::post('harvest', [HarvestController::class, 'post'])->name('harvest.post');
    Route::delete('harvest/{uuid}', [HarvestController::class, 'delete'])->name('harvest.delete');
    Route::get('inventory', [InventoryController::class, 'list'])->name('inventory.list');
    Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
});
