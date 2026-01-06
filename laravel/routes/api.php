<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\ProductsPriceController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource('products', ProductsController::class);
Route::get('/products/{id}/prices', [ProductsPriceController::class, 'getPrices']);
Route::post('/products/{id}/prices', [ProductsPriceController::class, 'addPrice']);
