<?php

use App\Http\Controllers\HarvestController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\CorporationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('product', [ProductController::class, 'post'])->name('product.post');
    Route::post('warehouse', [WarehouseController::class, 'post'])->name('warehouse.post');
    Route::post('corporation', [CorporationController::class, 'post'])->name('corporation.post');
    Route::post('harvest', [HarvestController::class, 'post'])->name('harvest.post');
});
