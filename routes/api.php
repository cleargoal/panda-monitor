<?php

declare(strict_types = 1);

use App\Http\Controllers\AdvertController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::middleware(['auth:sanctum',])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/auth-user', [AuthController::class, 'authUser']);

    Route::get('/adverts', [AdvertController::class, 'index'])->middleware('can:own,advert');
    Route::post('/adverts', [AdvertController::class, 'store']);
    Route::delete('/adverts/{advertId}', [AdvertController::class, 'destroy'])->name('adverts.destroy')->middleware('can:own,advert');
});
