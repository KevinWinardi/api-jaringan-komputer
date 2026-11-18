<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    // Endpoint publik
    Route::get('/books', [BookController::class, 'index']);

    Route::get('/books/{book}', [BookController::class, 'show'])
        ->whereNumber('book');

    Route::get('/stats', [BookController::class, 'stats']);

    Route::get('/open-library', [BookController::class, 'openLibrary'])
        ->middleware('throttle:30,1');

    // Login
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    // Endpoint yang memerlukan autentikasi
    Route::middleware('auth:sanctum')->group(function (): void {

        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Operasi tulis memerlukan token kelola
        Route::middleware('abilities:book:write')->group(function (): void {

            Route::post('/books', [BookController::class, 'store']);

            Route::patch('/books/{book}', [BookController::class, 'update'])
                ->whereNumber('book');

            Route::delete('/books/{book}', [BookController::class, 'destroy'])
                ->whereNumber('book');
        });
    });
});
