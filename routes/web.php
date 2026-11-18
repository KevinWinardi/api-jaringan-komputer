
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Website Utama Pustaka Jaringan
|--------------------------------------------------------------------------
|
| Halaman utama menggunakan katalog terbaru yang terintegrasi
| dengan REST API, SQLite, Open Library, dan Laravel Sanctum.
|
*/

Route::view('/', 'katalog')
    ->name('katalog.home');

/*
|--------------------------------------------------------------------------
| Alamat Alternatif
|--------------------------------------------------------------------------
|
| URL /aplikasi tetap dipertahankan untuk kompatibilitas.
|
*/

Route::view('/aplikasi', 'katalog')
    ->name('katalog.aplikasi');

/*
|--------------------------------------------------------------------------
| Website Pustaka Versi Awal
|--------------------------------------------------------------------------
|
| Beranda lama tersedia melalui /pustaka.
| Halaman katalog dan pembaca PDF tetap dipertahankan.
|
*/

require __DIR__.'/pustaka.php';
