<?php

use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\HarvestController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\CorporationController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\TranslationController;
use App\Http\Middleware\SetCurrentTenantFromUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('translations/{locale}', [TranslationController::class, 'show'])
    ->whereIn('locale', ['en', 'lg', 'sw'])
    ->name('translations.show');

Route::get('harvest/public/{batchUuid}', [HarvestController::class, 'publicShow'])->name('harvest.public.show');

Route::middleware(['auth:sanctum'])->get('user', function (Request $request) {
    return $request->user();
})->name('user.current');

Route::middleware(['auth:sanctum', SetCurrentTenantFromUser::class])->group(function () {
    Route::get('geocoding/reverse', [GeocodingController::class, 'reverse'])->name('geocoding.reverse');
    Route::get('countries', [CountryController::class, 'list'])->name('countries.list');
    Route::get('corporation', [CorporationController::class, 'get'])->name('corporation.get');
    Route::post('corporation', [CorporationController::class, 'post'])->name('corporation.post');
});

Route::middleware(['auth:sanctum', SetCurrentTenantFromUser::class, 'needsTenant'])->group(function () {
    Route::get('product', [ProductController::class, 'list'])->name('product.list');
    Route::post('product', [ProductController::class, 'post'])->name('product.post');
    Route::delete('product/{uuid}', [ProductController::class, 'delete'])->name('product.delete');
    Route::get('warehouse', [WarehouseController::class, 'list'])->name('warehouse.list');
    Route::post('warehouse', [WarehouseController::class, 'post'])->name('warehouse.post');
    Route::delete('warehouse/{uuid}', [WarehouseController::class, 'delete'])->name('warehouse.delete');
    Route::get('corporations', [CorporationController::class, 'list'])->name('corporation.list');
    Route::get('harvest', [HarvestController::class, 'list'])->name('harvest.list');
    Route::post('harvest', [HarvestController::class, 'post'])->name('harvest.post');
    Route::delete('harvest/{uuid}', [HarvestController::class, 'delete'])->name('harvest.delete');
    Route::get('inventory', [InventoryController::class, 'list'])->name('inventory.list');
    Route::post('inventory/sell', [InventoryController::class, 'sell'])->name('inventory.sell');
    Route::get('sales', [InventoryController::class, 'salesHistory'])->name('sales.history');
    Route::get('sales/{uuid}', [InventoryController::class, 'getSale'])->name('sales.get');
    Route::put('sales/{uuid}', [InventoryController::class, 'updateSale'])->name('sales.update');
    Route::delete('sales/{uuid}', [InventoryController::class, 'deleteSale'])->name('sales.delete');
    Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies.list');
    Route::get('currencies/rate/{from}/{to}', [CurrencyController::class, 'rate'])->name('currencies.rate');
    Route::post('currencies/convert', [CurrencyController::class, 'convert'])->name('currencies.convert');
    Route::put('currencies/{code}', [CurrencyController::class, 'update'])->name('currencies.update');
    Route::get('users', [UserController::class, 'list'])->name('users.list');
    Route::post('users', [UserController::class, 'post'])->name('users.post');
    Route::put('users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{id}', [UserController::class, 'delete'])->name('users.delete');
    Route::get('users/corporations', [UserController::class, 'corporations'])->name('users.corporations');
});
