<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SourceController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::middleware(['auth:sanctum',])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/auth-user', [AuthController::class, 'authUser']);

    Route::apiResource('/source', SourceController::class);
});
