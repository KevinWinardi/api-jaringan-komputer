<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Book::class);
    }

    public function rules(): array
    {
        return [
            'kode' => 'required|string|max:24|unique:books',
            'judul' => 'required|string|max:250',
            'penulis' => 'required|array|min:1',
            'penulis.*' => 'required|string|max:200',
            'tahun' => [
                'nullable',
                'integer',
                'min:1',
                'max:' . (now()->year + 1),
            ],
            'kategori' => 'required|array|min:1',
            'kategori.*' => 'required|string|max:200',
            'stok' => 'required|integer|min:0',
            'open_library_key' => 'nullable|string|max:100|unique:books',
            'cover_id' => 'nullable|integer|min:1'
        ];
    }
}
