<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::post('/products', [ProductController::class, 'store']);
Route::patch('/products/{product}', [ProductController::class, 'update']);

Route::delete('/products/{product}', [ProductController::class, 'destroy']);
