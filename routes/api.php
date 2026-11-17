<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::post('v1/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('abilities:book:read')->group(function () {
        Route::get('/books', [BookController::class, 'index']);
        Route::get('/books/{book}', [BookController::class, 'show'])
            ->whereNumber('book');
        Route::get('/open-library', [BookController::class, 'openLibrary']);

        Route::get('/stats', function () {
            Gate::authorize('stats');
            return response()->json([
                'total_book' => Book::count(),
            ]);
        });
    });

    Route::middleware('abilities:book:write')->group(function () {
        Route::post('/books', [BookController::class, 'store']);
        Route::patch('/books/{book}', [BookController::class, 'update'])
            ->whereNumber('book');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])
            ->whereNumber('book');
    });
});
