<?php

use App\Http\Controllers\HarvestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/harvest/{batchUuid}', [HarvestController::class, 'show'])->name('harvest.show');

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
