<?php
declare(strict_types=1);


use App\Http\Controllers\OpenApiSpecController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/openapi.{format}', OpenApiSpecController::class)
    ->whereIn('format', ['yaml', 'json']);
