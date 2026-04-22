<?php
declare(strict_types=1);


use App\Http\Controllers\ProductIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/products', ProductIndexController::class)
    ->middleware('redis.sliding_window:products,60,60,rate_limit.products.enabled');
