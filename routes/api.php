<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('inventory', [InventoryController::class, 'post'])->name('inventory.post');
    Route::post('product', [ProductController::class, 'post'])->name('product.post');
});
