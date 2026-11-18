<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'kode',
        'judul',
        'penulis',
        'tahun',
        'kategori',
        'stok',
        'open_library_key',
        'cover_id',
        'tingkat_kesulitan',
    ];

    protected $casts = [
        'penulis' => 'array',
        'kategori' => 'array',
        'tahun' => 'integer',
        'stok' => 'integer',
        'cover_id' => 'integer',
    ];
}
