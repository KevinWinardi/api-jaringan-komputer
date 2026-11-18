<?php

use App\Http\Controllers\PustakaPublikController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::name('pustaka.')
    ->withoutMiddleware([
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        ValidateCsrfToken::class,
    ])
    ->group(function () {
        Route::get('/', [PustakaPublikController::class, 'home'])->name('home');
        Route::get('/katalog', [PustakaPublikController::class, 'catalog'])->name('catalog');
        Route::get('/katalog/open-library', [PustakaPublikController::class, 'openLibrary'])->name('open-library');
        Route::view('/tentang', 'pustaka.about')->name('about');

        Route::prefix('katalog/{buku}')->where(['buku' => '[a-z0-9]+(?:-[a-z0-9]+)*'])->group(function () {
            Route::get('/', [PustakaPublikController::class, 'detail'])->name('detail');
            Route::get('/baca', [PustakaPublikController::class, 'reader'])->name('reader');
            Route::get('/pdf', [PustakaPublikController::class, 'preview'])->name('preview');
            Route::get('/unduh', [PustakaPublikController::class, 'download'])->name('download');
        });
    });
