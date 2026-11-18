@extends('pustaka.layout')

@section('title', 'Katalog Buku — Pustaka Jaringan')

@section('content')
    <div class="shell page-head catalog-head">
        <p class="eyebrow">Koleksi perpustakaan</p>
        <h1>Katalog buku<span>.</span></h1>
        <p>Cari referensi berdasarkan judul, penulis, atau topik jaringan komputer.</p>
    </div>

    <section class="shell catalog-body" aria-label="Daftar buku">
        @include('pustaka.catalog-sources', ['external' => false])
        <form class="search-box" role="search" method="get" action="{{ route('pustaka.catalog') }}">
            <div class="search-field">
                <label for="q">Judul, penulis, atau kata kunci</label>
                <input type="search" id="q" name="q" maxlength="120" value="{{ $query }}" placeholder="Misalnya TCP, Ethernet, atau Dordal">
            </div>
            <div class="search-field">
                <label for="topik">Topik</label>
                <select id="topik" name="topik">
                    <option value="">Semua topik</option>
                    @foreach($topics as $key => $topic)
                        <option value="{{ $key }}" @selected($selected === $key)>{{ $topic }}</option>
                    @endforeach
                </select>
            </div>
            <button class="button button-primary" type="submit">Cari buku</button>
        </form>

        <div class="results-head">
            <p>{{ count($results) }} dari {{ $bookCount }} buku @if($query !== '') untuk “{{ $query }}” @endif</p>
            @if($query !== '' || $selected !== '')
                <a class="text-link" href="{{ route('pustaka.catalog') }}">Reset pencarian</a>
            @endif
        </div>

        @if($results === [])
            <div class="empty-state">
                <h2>Tidak ada buku yang cocok</h2>
                <p>Coba kata kunci yang lebih singkat atau pilih semua topik.</p>
            </div>
        @else
            <div class="book-grid">
                @foreach($results as $slug => $book)
                    @include('pustaka.card', compact('slug', 'book', 'topics'))
                @endforeach
            </div>
        @endif
    </section>
@endsection
