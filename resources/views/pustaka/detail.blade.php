@extends('pustaka.layout')

@section('title', $book['title'].' — Pustaka Jaringan')
@section('description', $book['summary'])

@section('content')
    <div class="shell detail-shell">
        <nav aria-label="Jejak navigasi" class="breadcrumbs"><a href="{{ route('pustaka.catalog') }}">← Kembali ke katalog</a></nav>

        <article class="detail-panel">
            <div class="detail-art art-{{ $book['tone'] }}">
                @include('pustaka.cover', compact('book', 'topic'))
            </div>
            <div class="detail-info">
                <p class="eyebrow">{{ $topic }} <span aria-hidden="true">·</span> PDF</p>
                <h1>{{ $book['title'] }}</h1>
                <p class="detail-author">{{ $book['author'] }}</p>
                <h2>Tentang buku ini</h2>
                <p>{{ $book['summary'] }}</p>
                <ul class="tags" aria-label="Topik buku">
                    @foreach($book['tags'] as $tag)
                        <li>{{ $tag }}</li>
                    @endforeach
                </ul>
                <div class="button-row">
                    <a class="button button-primary" href="{{ route('pustaka.reader', ['buku' => $slug]) }}">Pratinjau PDF <span aria-hidden="true">↗</span></a>
                    <a class="button button-secondary" href="{{ route('pustaka.download', ['buku' => $slug]) }}">Unduh PDF <span aria-hidden="true">↓</span></a>
                </div>
                <dl class="book-meta">
                    <div><dt>Bahasa</dt><dd>Inggris</dd></div>
                    <div><dt>Lisensi</dt><dd><a href="{{ $book['license_url'] }}" target="_blank" rel="noopener noreferrer">{{ $book['license'] }}</a></dd></div>
                    <div><dt>Referensi</dt><dd><a href="{{ $book['source'] }}" target="_blank" rel="noopener noreferrer">Halaman sumber ↗</a></dd></div>
                </dl>
            </div>
        </article>
    </div>
@endsection
