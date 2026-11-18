<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Website Katalog REST API
|--------------------------------------------------------------------------
|
| Antarmuka Bootstrap yang terhubung dengan REST API Laravel.
| Menyediakan login, daftar koleksi, pencarian Open Library,
| serta pengelolaan buku untuk Admin.
|
*/

Route::view('/aplikasi', 'katalog')
    ->name('katalog.aplikasi');

/*
|--------------------------------------------------------------------------
| Website Pustaka Sebelumnya
|--------------------------------------------------------------------------
|
| Seluruh halaman dan fitur website lama tetap dipertahankan.
| Route utama, katalog PDF, detail, dan halaman Open Library
| yang sudah dibuat teman kelompok tidak dihapus.
|
*/

require __DIR__.'/pustaka.php';
