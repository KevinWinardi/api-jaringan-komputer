<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->validate([
            'q' => [
                'sometimes',
                'string',
                'max:120',
            ],

            'kategori' => [
                'sometimes',
                'string',
                'max:200',
            ],

            'tingkat_kesulitan' => [
                'sometimes',
                'string',
                'in:pemula,menengah,lanjutan',
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:50',
            ],
        ]);

        $query = Book::query();

        $keyword = trim($input['q'] ?? '');
        $kategori = trim($input['kategori'] ?? '');

        // Pencarian judul, penulis, dan kode buku
        if ($keyword !== '') {
            $like = '%'.$keyword.'%';

            $query->where(function ($builder) use ($like): void {
                $builder
                    ->where('judul', 'like', $like)
                    ->orWhere('kode', 'like', $like)
                    ->orWhere('penulis', 'like', $like);
            });
        }

        // Filter kategori yang tersimpan sebagai JSON
        if ($kategori !== '') {
            $query->whereJsonContains(
                'kategori',
                $kategori
            );
        }

        // Filter berdasarkan tingkat kesulitan
        if (isset($input['tingkat_kesulitan'])) {
            $query->where(
                'tingkat_kesulitan',
                $input['tingkat_kesulitan']
            );
        }

        $perPage = (int) ($input['per_page'] ?? 8);

        return BookResource::collection(
            $query
                ->orderBy('id')
                ->paginate($perPage)
                ->withQueryString()
        );
    }

    public function show(Book $book): BookResource
    {
        return new BookResource($book);
    }

    public function stats(): JsonResponse
    {
        $counts = [
            'pemula' => 0,
            'menengah' => 0,
            'lanjutan' => 0,
            'belum_ditentukan' => 0,
        ];

        // Ringkasan dihitung langsung dari database
        $groups = Book::query()
            ->selectRaw(
                'tingkat_kesulitan, COUNT(*) AS jumlah'
            )
            ->groupBy('tingkat_kesulitan')
            ->get();

        foreach ($groups as $group) {
            $key = $group->tingkat_kesulitan
                ?? 'belum_ditentukan';

            if (! array_key_exists($key, $counts)) {
                $key = 'belum_ditentukan';
            }

            $counts[$key] += (int) $group->jumlah;
        }

        return response()->json([
            'total_book' => array_sum($counts),
            'tingkat_kesulitan' => $counts,
        ]);
    }

    public function openLibrary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => [
                'required',
                'string',
                'min:2',
                'max:120',
            ],
        ]);

        $keyword = trim($data['q']);

        if ($keyword === '') {
            return response()->json([
                'message' => 'Parameter q wajib berisi kata kunci.',
                'errors' => [
                    'q' => [
                        'Parameter q wajib berisi kata kunci.',
                    ],
                ],
            ], 422);
        }

        $cacheKey = 'open_library_search_'
            .sha1(mb_strtolower($keyword));

        try {
            $result = Cache::remember(
                $cacheKey,
                now()->addMinutes(5),
                function () use ($keyword): array {
                    $response = Http::acceptJson()
                        ->connectTimeout(10)
                        ->timeout(20)
                        ->get(
                            'https://openlibrary.org/search.json',
                            [
                                'q' => $keyword,
                                'limit' => 8,
                                'fields' => implode(',', [
                                    'key',
                                    'title',
                                    'author_name',
                                    'first_publish_year',
                                    'cover_i',
                                    'subject',
                                ]),
                            ]
                        );

                    if (! $response->successful()) {
                        throw new RuntimeException(
                            'Permintaan Open Library gagal.'
                        );
                    }

                    $payload = $response->json();

                    if (
                        ! is_array($payload)
                        || ! isset($payload['docs'])
                        || ! is_array($payload['docs'])
                    ) {
                        throw new RuntimeException(
                            'Format respons Open Library tidak valid.'
                        );
                    }

                    $books = [];
                    $legacyDocs = [];

                    foreach ($payload['docs'] as $row) {
                        if (! is_array($row)) {
                            continue;
                        }

                        $key = $row['key'] ?? null;
                        $title = $row['title'] ?? null;

                        if (
                            ! is_string($key)
                            || ! preg_match(
                                '~^/works/OL[0-9]+W$~',
                                $key
                            )
                            || ! is_string($title)
                            || trim($title) === ''
                        ) {
                            continue;
                        }

                        // Normalisasi penulis
                        $authors = collect(
                            is_array($row['author_name'] ?? null)
                                ? $row['author_name']
                                : []
                        )
                            ->filter(
                                fn ($name) =>
                                    is_string($name)
                                    && trim($name) !== ''
                            )
                            ->map(
                                fn ($name) =>
                                    mb_substr(
                                        trim($name),
                                        0,
                                        200
                                    )
                            )
                            ->take(5)
                            ->values()
                            ->all();

                        if ($authors === []) {
                            $authors = [
                                'Penulis tidak diketahui',
                            ];
                        }

                        // Normalisasi tahun
                        $year = $row['first_publish_year']
                            ?? null;

                        $year = is_numeric($year)
                            && (int) $year >= 1
                            && (int) $year <= now()->year + 1
                                ? (int) $year
                                : null;

                        // Normalisasi cover
                        $coverId = $row['cover_i'] ?? null;

                        $coverId = is_numeric($coverId)
                            && (int) $coverId > 0
                                ? (int) $coverId
                                : null;

                        // Normalisasi kategori
                        $subjects = collect(
                            is_array($row['subject'] ?? null)
                                ? $row['subject']
                                : []
                        )
                            ->filter(
                                fn ($subject) =>
                                    is_string($subject)
                                    && trim($subject) !== ''
                            )
                            ->map(
                                fn ($subject) =>
                                    mb_substr(
                                        trim($subject),
                                        0,
                                        200
                                    )
                            )
                            ->take(3)
                            ->values()
                            ->all();

                        $totalKategori = $subjects === []
                            ? ['Jaringan Komputer']
                            : $subjects;

                        // Metadata siap digunakan pada formulir impor
                        $books[] = [
                            'kode' => 'OL-'.substr(
                                sha1($key),
                                0,
                                16
                            ),
                            'judul' => mb_substr(
                                trim($title),
                                0,
                                250
                            ),
                            'penulis' => $authors,
                            'tahun' => $year,
                            'kategori' => $totalKategori,
                            'stok' => 0,
                            'open_library_key' => $key,
                            'cover_id' => $coverId,
                            'source_url' =>
                                'https://openlibrary.org'.$key,

                            // Tingkat kesulitan ditentukan pengelola
                            'tingkat_kesulitan' => null,
                        ];

                        // Menjaga kompatibilitas frontend lama
                        $legacyDocs[] = [
                            'key' => $key,
                            'title' => mb_substr(
                                trim($title),
                                0,
                                250
                            ),
                            'author_name' => $authors,
                            'first_publish_year' => $year,
                            'cover_i' => $coverId,
                            'subject' => $subjects,
                        ];
                    }

                    $totalFound = max(
                        (int) (
                            $payload['numFound']
                            ?? $payload['num_found']
                            ?? count($books)
                        ),
                        count($books)
                    );

                    return [
                        'data' => $books,
                        'meta' => [
                            'source' => 'Open Library',
                            'query' => $keyword,
                            'count' => count($books),
                            'total_found' => $totalFound,
                        ],
                        'docs' => $legacyDocs,
                        'numFound' => $totalFound,
                    ];
                }
            );
        } catch (
            ConnectionException | RuntimeException $exception
        ) {
            report($exception);

            return response()->json([
                'message' =>
                    'Layanan Open Library sedang tidak dapat diakses.',
            ], 503);
        }

        return response()->json($result);
    }

    public function store(StoreBookRequest $request)
    {
        Gate::authorize('create', Book::class);

        $book = Book::create(
            $request->validated()
        );

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateBookRequest $request,
        Book $book
    ): BookResource {
        Gate::authorize('update', $book);

        $book->update(
            $request->validated()
        );

        return new BookResource(
            $book->refresh()
        );
    }

    public function destroy(Book $book)
    {
        Gate::authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
