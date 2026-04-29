<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/products/{sku}', [ProductController::class, 'apiSearch']);
Route::get('/products-list', [ProductController::class, 'apiIndex']);
Route::post('/products', [ProductController::class, 'apiStore']);
Route::post('/purchases', [PurchaseController::class, 'store']);
Route::post('/sales', [\App\Http\Controllers\SaleController::class, 'store']);
Route::get('/clients/search', [\App\Http\Controllers\ClientController::class, 'apiSearch']);
Route::post('/clients', [\App\Http\Controllers\ClientController::class, 'store']);
Route::get('/providers', [\App\Http\Controllers\ProviderController::class, 'apiIndex']);
Route::post('/providers', [\App\Http\Controllers\ProviderController::class, 'apiStore']);
Route::get('/sales/next-id', [\App\Http\Controllers\SaleController::class, 'getNextId']);
Route::get('/sales/{id}', [\App\Http\Controllers\SaleController::class, 'show']);
Route::put('/sales/{id}', [\App\Http\Controllers\SaleController::class, 'update']);
Route::get('/purchases/{id}', [\App\Http\Controllers\PurchaseController::class, 'show']);
Route::put('/purchases/{id}', [\App\Http\Controllers\PurchaseController::class, 'update']);
