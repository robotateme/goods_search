<?php
declare(strict_types=1);


use App\Http\Controllers\ProductSearchShowController;
use App\Http\Controllers\ProductSearchStoreController;
use Illuminate\Support\Facades\Route;

Route::post('/product-searches', ProductSearchStoreController::class)->name('product-searches.store');
Route::get('/product-searches/{productSearchRequest}', ProductSearchShowController::class)->name('product-searches.show');
