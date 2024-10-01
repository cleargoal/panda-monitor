<?php

declare(strict_types = 1);

use App\Http\Controllers\AdvertController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/verify/{id}/{hash}', [AuthController::class, 'verifyEmailByLink'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
Route::middleware(['auth:sanctum',])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/auth-user', [AuthController::class, 'authUser']);

    Route::get('/adverts', [AdvertController::class, 'index']);
    Route::post('/adverts', [AdvertController::class, 'store']);
    Route::delete('/adverts/{advert}', [AdvertController::class, 'destroy'])->name('adverts.destroy')->can('destroy','advert');
});
