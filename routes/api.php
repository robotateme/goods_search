<?php

use App\Http\Controllers\ProductIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/products', ProductIndexController::class);
