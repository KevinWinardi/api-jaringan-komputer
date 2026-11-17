<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'name' => 'pengguna',
            'email' => 'pengguna@gmail.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);
        User::create([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $response = Http::timeout(30)->get(
            'https://openlibrary.org/search.json',
            [
                'q' => 'subject:computer_networks',
                'fields' => 'key,title,author_name,first_publish_year,cover_i,subject',
                'limit' => 100,
            ]
        );

        $response->throw();

        $books = collect($response->json('docs', []))
            ->filter(function ($book) {
                return !empty($book['key'])
                    && !empty($book['title'])
                    && !empty($book['author_name']);
            })
            ->unique('key')
            ->values();

        foreach ($books as $book) {
            // Mengambil seluruh penulis
            $authors = collect($book['author_name'])
                ->filter()
                ->map(fn($author) => Str::limit($author, 200, ''))
                ->unique()
                ->values()
                ->all();

            // Mengambil seluruh kategori
            $categories = collect($book['subject'])
                ->filter()
                ->map(fn($category) => Str::limit($category, 200, ''))
                ->unique()
                ->values()
                ->all();

            if (empty($authors) || empty($categories)) {
                continue;
            }

            // Validasi tahun
            $year = $book['first_publish_year'] ?? null;

            if (
                !is_numeric($year) ||
                $year < 1 ||
                $year > now()->year + 1
            ) {
                $year = null;
            }

            $coverId = $book['cover_i'] ?? null;

            if (!is_numeric($coverId) || $coverId < 1) {
                $coverId = null;
            }

            // Kode unik dan konsisten maksimal 24 karakter
            $code = 'BK-' . substr(
                sha1($book['key']),
                0,
                20
            );

            DB::table('books')->updateOrInsert(
                [
                    'open_library_key' => $book['key'],
                ],
                [
                    'kode' => $code,
                    'judul' => Str::limit(
                        $book['title'],
                        250,
                        ''
                    ),
                    'penulis' => json_encode(
                        $authors,
                        JSON_UNESCAPED_UNICODE
                    ),
                    'tahun' => $year,
                    'kategori' => json_encode(
                        $categories,
                        JSON_UNESCAPED_UNICODE
                    ),
                    'stok' => 5,
                    'cover_id' => $coverId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
