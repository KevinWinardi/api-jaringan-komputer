<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

class BookController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Book::class);
        $kategori = $request->query('kategori');
        return BookResource::collection(
            Book::when($kategori, function ($query, $kategori) {
                $query->where('kategori', $kategori);
            })
                ->orderBy('id')
                ->paginate(2)
        );
    }

    public function show(Book $book)
    {
        Gate::authorize('view', $book);
        return new BookResource($book);
    }

    public function openLibrary(Book $book)
    {
        Gate::authorize('viewOpenLibrary', $book);
        $query = request()->query('q');

        if (!$query) {
            return response()->json([
                'message' => 'Parameter q wajib diisi'
            ], 422);
        }

        $response = Http::acceptJson()
            ->timeout(15)
            ->get('https://openlibrary.org/search.json', [
                'q' => $query,
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Gagal mengambil data dari Open Library'
            ], $response->status());
        }

        return response()->json($response->json());
    }

    public function store(StoreBookRequest $request)
    {
        $book = Book::create($request->validated());

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $book->update($request->validated());
        return new BookResource($book->refresh());
    }

    public function destroy(Book $book)
    {
        Gate::authorize('delete', $book);
        $book->delete();
        return response()->noContent();
    }
}
