<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    public $fillable = [
        'kode',
        'judul',
        'penulis',
        'tahun',
        'kategori',
        'stok',
        'open_library_key',
        'cover_id'
    ];

    public $casts = [
        'penulis' => 'array',
        'kategori' => 'array',
    ];
}
