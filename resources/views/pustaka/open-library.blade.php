@extends('pustaka.layout')

@section('title', 'Cari di Open Library — Pustaka Jaringan')

@push('head')
    <script src="{{ asset('pustaka/open-library.js') }}" defer></script>
@endpush

@section('content')
    <div class="shell page-head catalog-head">
        <p class="eyebrow">Katalog jaringan komputer</p>
        <h1>Cari di Open Library<span>.</span></h1>
        <p>Cari judul, penulis, atau kata kunci. Pratinjau muncul ketika Open Library menandai buku dapat dibaca bebas.</p>
    </div>

    <section class="shell catalog-body" aria-label="Pencarian Open Library">
        @include('pustaka.catalog-sources', ['external' => true])
        <form class="search-box upstream-search" role="search" method="get" action="{{ route('pustaka.open-library') }}">
            <div class="search-field">
                <label for="q">Judul, penulis, atau kata kunci</label>
                <input type="search" id="q" name="q" maxlength="120" value="{{ $query }}" placeholder="Misalnya computer networks, routing, atau Peterson" required>
            </div>
            <button class="button button-primary" type="submit">Cari di Open Library</button>
        </form>

        @if($query === '')
            <p class="results-head">Coba cari dengan kata kunci bahasa Inggris, misalnya “computer networks”.</p>
        @elseif($results === null)
            <div class="empty-state upstream-notice" data-open-library-recovery="{{ $query }}" role="status"><h2>Open Library belum merespons</h2>
                <p>Coba cari lagi dalam beberapa saat, atau buka koleksi pustaka untuk membaca PDF dari penerbit.</p>
                <a class="text-link" href="https://openlibrary.org/search?q={{ rawurlencode($query) }}" target="_blank" rel="noopener noreferrer">Cari langsung di Open Library ↗</a><br>
                <a class="text-link" href="{{ route('pustaka.catalog') }}">Buka koleksi pustaka</a>
            </div>
        @elseif($results === [])
            <div class="empty-state upstream-notice">
                <h2>Belum ada hasil untuk “{{ $query }}”</h2>
                <p>Coba kata kunci yang lebih singkat atau nama penulis.</p>
            </div>
        @else
            <div class="results-head">{{ count($results) }} hasil Open Library untuk “{{ $query }}”</div>
            <div class="book-grid">
                @foreach($results as $result)
                    <article class="book-card upstream-book">
                        <div class="book-info">
                            <p class="book-topic">Open Library @if($result['year']) · {{ $result['year'] }} @endif</p>
                            <h2 class="book-title">{{ $result['title'] }}</h2>
                            @if($result['author'] !== '')
                                <p class="book-author">{{ $result['author'] }}</p>
                            @endif
                            <div class="upstream-actions">
                                @if($result['reader'])
                                    <a class="book-link" href="{{ $result['archive'] }}" target="_blank" rel="noopener noreferrer"
                                       data-book-preview="{{ $result['reader'] }}" data-book-title="{{ $result['title'] }}">
                                        Pratinjau buku <span aria-hidden="true">↗</span>
                                    </a>
                                @else
                                    <p class="book-author">Akses baca bebas belum terkonfirmasi.</p>
                                @endif
                                <a class="book-link" href="{{ $result['url'] }}" target="_blank" rel="noopener noreferrer">Detail di Open Library <span aria-hidden="true">↗</span></a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <dialog class="archive-reader" aria-labelledby="archive-reader-title" data-archive-reader>
                <div class="archive-reader-header">
                    <h2 id="archive-reader-title">Pratinjau buku</h2>
                    <form method="dialog"><button class="button button-secondary" type="submit" autofocus>Tutup</button></form>
                </div>
                <a class="text-link archive-fallback" target="_blank" rel="noopener noreferrer" data-archive-fallback>Buka di Internet Archive ↗</a>
                <iframe class="reader-frame" title="Pratinjau buku dari Internet Archive" referrerpolicy="no-referrer"></iframe>
            </dialog>
        @endif
    </section>
@endsection
