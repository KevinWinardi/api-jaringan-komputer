<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Book::class);
    }

    public function rules(): array
    {
        return [
            'kode' => [
                'sometimes',
                'required',
                'string',
                'max:24',
                Rule::unique('books')->ignore($this->route('book'))
            ],
            'judul' => 'sometimes|required|string|max:250',
            'penulis' => 'sometimes|required|array',
            'penulis.*' => 'required|string|max:200',
            'tahun' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:' . (now()->year + 1),
            ],
            'kategori' => 'sometimes|required|array',
            'kategori.*' => 'required|string|max:200',
            'stok' => 'sometimes|required|integer|min:0',
            'open_library_key' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('books', 'open_library_key')
                    ->ignore($this->route('book')),
            ],
            'cover_id' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
