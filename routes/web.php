<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('welcome');
});

Route::get('/forgot-password', function () {
    return view('welcome');
});

Route::get('/set-password', function () {
    return view('welcome');
});

Route::get('/harvest/public/{batchUuid}', function () {
    return view('welcome');
});

Route::get('/harvest/{batchUuid}', function () {
    return view('welcome');
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
