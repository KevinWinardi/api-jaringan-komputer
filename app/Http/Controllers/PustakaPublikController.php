<?php

namespace App\Http\Controllers;

use App\Services\PustakaOpenLibrary;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class PustakaPublikController extends Controller
{
    private const PDF_LIMIT = 64 * 1024 * 1024;

    public function home()
    {
        $books = config('pustaka.books');

        return view('pustaka.home', [
            'featured' => array_slice($books, 0, 3, true),
            'bookCount' => count($books),
            'topics' => config('pustaka.topics'),
        ]);
    }

    public function catalog(Request $request)
    {
        $topics = config('pustaka.topics');
        $books = config('pustaka.books');
        $input = $request->query();
        $query = is_string($input['q'] ?? null) ? mb_substr(trim($input['q']), 0, 120) : '';
        $selected = $input['topik'] ?? '';
        $selected = is_string($selected) && isset($topics[$selected]) ? $selected : '';
        $normalizedQuery = $this->searchText($query);
        $terms = $normalizedQuery === '' ? [] : explode(' ', $normalizedQuery);

        $results = array_filter($books, function (array $book) use ($topics, $selected, $terms) {
            if ($selected !== '' && $book['topic'] !== $selected) {
                return false;
            }

            $text = $this->searchText(implode(' ', [
                $book['title'], $book['author'], $book['summary'], $topics[$book['topic']],
                ...$book['tags'],
            ]));

            foreach ($terms as $term) {
                if (! str_contains($text, $term)) {
                    return false;
                }
            }

            return true;
        });

        return view('pustaka.catalog', [
            'topics' => $topics,
            'query' => $query,
            'selected' => $selected,
            'results' => $results,
            'bookCount' => count($books),
        ]);
    }

    public function openLibrary(Request $request, PustakaOpenLibrary $openLibrary)
    {
        $input = $request->query();
        $query = is_string($input['q'] ?? null) ? mb_substr(trim($input['q']), 0, 120) : '';
        $results = $query !== '' ? $openLibrary->search($query) : [];

        return view('pustaka.open-library', compact('query', 'results'));
    }

    public function detail(string $buku)
    {
        return view('pustaka.detail', $this->details($buku));
    }

    public function reader(string $buku)
    {
        return view('pustaka.reader', $this->details($buku));
    }

    public function preview(string $buku): Response
    {
        $book = $this->book($buku);
        $path = $this->cachedPdf($buku, $book);

        if ($path === null) {
            return response()->view('pustaka.pdf-unavailable', compact('book'), 502);
        }

        return response()
            ->file($path, $this->pdfHeaders())
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $buku.'.pdf');
    }

    public function download(string $buku): Response
    {
        $book = $this->book($buku);
        $path = $this->cachedPdf($buku, $book);

        if ($path === null) {
            return redirect()->away($book['pdf']);
        }

        return response()->download($path, $buku.'.pdf', $this->pdfHeaders());
    }

    private function searchText(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($value))) ?? '');
    }

    private function details(string $slug): array
    {
        $book = $this->book($slug);

        return [
            'slug' => $slug,
            'book' => $book,
            'topic' => config('pustaka.topics')[$book['topic']],
        ];
    }

    private function book(string $slug): array
    {
        $books = config('pustaka.books');
        abort_unless(isset($books[$slug]), 404);

        return $books[$slug];
    }

    private function pdfHeaders(): array
    {
        return [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    private function cachedPdf(string $slug, array $book): ?string
    {
        $directory = storage_path('app/pustaka-pdf');
        $path = $directory.'/'.$slug.'-'.substr(hash('sha256', $book['pdf']), 0, 12).'.pdf';
        if (is_file($path)) {
            return $path;
        }

        if ((! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory))
            || ! is_writable($directory)) {
            return null;
        }

        $urls = array_filter([$book['pdf'], $book['alternate_pdf'] ?? null]);

        // The catalog is the only source of URLs. Requests cannot supply a URL or a disk path.
        foreach ($urls as $url) {
            $temporary = tempnam($directory, 'pdf-');
            if ($temporary === false) {
                return null;
            }

            try {
                $result = Http::withHeaders([
                    'Accept' => 'application/pdf, application/octet-stream',
                    'User-Agent' => 'PustakaJaringan/1.0 (educational PDF library)',
                ])->connectTimeout(4)->timeout(count($urls) === 1 ? 15 : 8)->withOptions([
                    'sink' => $temporary,
                    'allow_redirects' => ['max' => 4, 'protocols' => ['https']],
                    'progress' => static function ($expected, $received) {
                        if ($expected > self::PDF_LIMIT || $received > self::PDF_LIMIT) {
                            throw new RuntimeException('The publisher PDF exceeds the cache size limit.');
                        }
                    },
                ])->get($url);

                if ($result->ok() && $this->isCompletePdf($temporary)) {
                    if (is_file($path) || @rename($temporary, $path) || is_file($path)) {
                        return $path;
                    }
                }
            } catch (ConnectionException|RuntimeException) {
                // Try the publisher's alternate link if one is available.
            } finally {
                if (is_file($temporary)) {
                    File::delete($temporary);
                }
            }
        }

        return null;
    }

    private function isCompletePdf(string $path): bool
    {
        clearstatcache(true, $path);
        $size = filesize($path);
        if ($size === false || $size < 12 || $size > self::PDF_LIMIT) {
            return false;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        try {
            if (fread($handle, 5) !== '%PDF-' || fseek($handle, -min($size, 4096), SEEK_END) !== 0) {
                return false;
            }

            return str_contains((string) stream_get_contents($handle), '%%EOF');
        } finally {
            fclose($handle);
        }
    }
}
