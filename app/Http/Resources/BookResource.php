<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,

            'kode' => $this->kode,

            'judul' => $this->judul,

            'penulis' => $this->penulis,

            'tahun' => $this->tahun === null
                ? null
                : (int) $this->tahun,

            'kategori' => $this->kategori,

            'stok' => (int) $this->stok,

            'open_library_key' => $this->open_library_key,

            'cover_id' => $this->cover_id === null
                ? null
                : (int) $this->cover_id,

            'tingkat_kesulitan' => $this->tingkat_kesulitan,
        ];
    }
}
