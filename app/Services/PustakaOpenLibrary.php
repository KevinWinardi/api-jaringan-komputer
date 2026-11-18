<?php

namespace App\Services;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PustakaOpenLibrary
{
    private const ENDPOINT = 'https://openlibrary.org/search.json';
    private const LOCK = 'pustaka.open-library.in-flight';
    private const COOLDOWN = 'pustaka.open-library.cooldown';
    private const LIMIT = 12;

    public function search(string $query): ?array
    {
        $terms = preg_split('/[^a-z0-9]+/', strtolower(Str::ascii($query)), -1, PREG_SPLIT_NO_EMPTY);
        if ($terms === false || $terms === []) {
            return [];
        }

        $normalized = implode(' ', $terms);
        $terms = in_array($normalized, ['jaringan komputer', 'computer networks', 'computer networking'], true) ? [] : $terms;
        $subject = '(subject:"computer networks" OR subject:"computer networking" OR subject:"TCP/IP")';
        $solrQuery = implode(' AND ', [$subject, ...array_map(
            static fn (string $term) => '"'.$term.'"',
            array_slice($terms, 0, self::LIMIT)
        )]);

        /** @var Repository $cache */
        $cache = Cache::store('file');
        $key = 'pustaka.open-library.search.v2.'.hash('sha256', $solrQuery);
        $cached = $cache->get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $store = $cache->getStore();
        if (! $store instanceof LockProvider) {
            Log::warning('Pustaka: Open Library cache store has no lock support.');

            return null;
        }

        $result = $store->lock(self::LOCK, 45)->get(function () use ($cache, $key, $solrQuery) {
            $cached = $cache->get($key);
            if (is_array($cached)) {
                return $cached;
            }

            if ($cache->has(self::COOLDOWN)) {
                return null;
            }

            $startedAt = microtime(true);

            try {
                $docs = $this->requestDocs($solrQuery, self::LIMIT);

                if ($docs === null) {
                    return null;
                }

                $publicWorks = [];
                $books = [];
                foreach (array_slice($docs, 0, self::LIMIT) as $doc) {
                    if (! is_array($doc)) {
                        continue;
                    }

                    $work = $this->workId($doc['key'] ?? null);
                    $title = $doc['title'] ?? null;
                    if ($work === null || ! is_string($title) || trim($title) === '') {
                        continue;
                    }

                    $books[$work] = $this->book($work, $title, $doc);
                    if (($doc['ebook_access'] ?? null) === 'public') {
                        $publicWorks[$work] = true;
                    }
                }

                $minutes = 15;
                if ($publicWorks !== []) {
                    $pause = 1 - (microtime(true) - $startedAt);
                    if ($pause > 0) {
                        usleep((int) ceil($pause * 1_000_000));
                    }

                    $works = array_keys($publicWorks);
                    $availabilityQuery = implode(' OR ', array_map(
                        static fn (string $work) => 'key:"/works/'.$work.'"',
                        $works
                    ));
                    $availability = $this->requestDocs($availabilityQuery, count($works), withAvailability: true);
                    if ($availability === null) {
                        $minutes = 2;
                    }

                    foreach ($availability ?? [] as $doc) {
                        if (! is_array($doc)) {
                            continue;
                        }
                        $work = $this->workId($doc['key'] ?? null);
                        $scan = $doc['availability'] ?? null;
                        if ($work === null || ! isset($publicWorks[$work]) || ! is_array($scan)) {
                            continue;
                        }

                        $identifier = $scan['identifier'] ?? null;
                        if (($scan['status'] ?? null) === 'open'
                            && ($scan['is_restricted'] ?? false) === false
                            && is_string($identifier)
                            && preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,99}\z/', $identifier)) {
                            $books[$work]['reader'] = 'https://archive.org/embed/'.rawurlencode($identifier);
                            $books[$work]['archive'] = 'https://archive.org/details/'.rawurlencode($identifier);
                        }
                    }
                }

                $results = array_values($books);
                $cache->put($key, $results, now()->addMinutes($minutes));

                return $results;
            } finally {
                $cache->put(self::COOLDOWN, true, 1);
            }
        });

        return is_array($result) ? $result : null;
    }

    private function requestDocs(string $query, int $limit, bool $withAvailability = false): ?array
    {
        $operation = $withAvailability ? 'availability' : 'search';
        $timeout = $withAvailability ? 5 : 20;
        $fields = $withAvailability ? 'key,ia,availability' : 'key,title,author_name,first_publish_year,ebook_access';
        $contact = config('pustaka.open_library_contact');
        $userAgent = 'PustakaJaringan/1.2';
        if (is_string($contact) && filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $userAgent .= ' ('.$contact.')';
        }

        try {
            $response = Http::acceptJson()
                ->withUserAgent($userAgent)
                ->connectTimeout(min(10, $timeout))
                ->timeout($timeout)
                ->withoutRedirecting()
                ->get(self::ENDPOINT, ['q' => $query, 'fields' => $fields, 'limit' => $limit]);
        } catch (ConnectionException $exception) {
            preg_match('/cURL error ([0-9]+)/', $exception->getMessage(), $match);
            Log::warning('Pustaka: Open Library connection failed.', [
                'operation' => $operation,
                'curl' => isset($match[1]) ? (int) $match[1] : null,
            ]);

            return null;
        }

        $docs = $response->ok() ? $response->json('docs') : null;
        if (! is_array($docs)) {
            Log::warning('Pustaka: Open Library response could not be used.', [
                'operation' => $operation,
                'http_status' => $response->status(),
            ]);

            return null;
        }

        return $docs;
    }

    private function workId(mixed $key): ?string
    {
        return is_string($key) && preg_match('~\A(?:/works/)?(OL[0-9]+W)\z~', $key, $work)
            ? $work[1]
            : null;
    }

    private function book(string $work, string $title, array $doc): array
    {
        $authors = is_array($doc['author_name'] ?? null) ? $doc['author_name'] : [];
        $authors = array_filter($authors, static fn ($author) => is_string($author) && trim($author) !== '');

        return [
            'title' => Str::limit(trim($title), 220),
            'author' => Str::limit(implode(', ', array_slice($authors, 0, 4)), 200),
            'year' => is_int($doc['first_publish_year'] ?? null) ? $doc['first_publish_year'] : null,
            'url' => 'https://openlibrary.org/works/'.$work,
            'reader' => null,
            'archive' => null,
        ];
    }
}
