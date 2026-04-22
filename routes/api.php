<?php
declare(strict_types=1);


use App\Http\Controllers\ProductIndexController;
use App\Http\Middleware\ProductsRateLimit;
use Illuminate\Support\Facades\Route;

Route::get('/products', ProductIndexController::class)
    ->middleware(ProductsRateLimit::class);
