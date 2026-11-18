@extends('pustaka.layout')

@section('content')
    <section class="shell hero">
        <div class="hero-copy">
            <p class="eyebrow">Perpustakaan digital</p>
            <h1>Buku jaringan<br>komputer<span>.</span></h1>
            <p class="hero-description">Temukan bacaan tentang cara kerja internet, protokol jaringan, dan pemrograman jaringan. Baca langsung di peramban atau unduh PDF untuk belajar nanti.</p>
            <a class="button button-primary" href="{{ route('pustaka.catalog') }}">Jelajahi katalog <span aria-hidden="true">→</span></a>
        </div>
        <div class="hero-visual" aria-hidden="true">
            @foreach($featured as $book)
                @include('pustaka.cover', ['book' => $book, 'topic' => $topics[$book['topic']]])
            @endforeach
        </div>
    </section>

    <section class="shell topics-strip" aria-labelledby="topik-title">
        <h2 id="topik-title">Jelajahi per topik</h2>
        <div class="topic-links">
            @foreach($topics as $key => $topic)
                <a href="{{ route('pustaka.catalog', ['topik' => $key]) }}">{{ $topic }} <span aria-hidden="true">↗</span></a>
            @endforeach
        </div>
    </section>

    <section class="shell content-section" aria-labelledby="pilihan-title">
        <div class="section-heading">
            <div>
                <h2 id="pilihan-title">Bacaan pilihan</h2>
                <p>Mulai dari dasar internet, lalu lanjutkan ke arsitektur dan protokol.</p>
            </div>
            <a class="text-link" href="{{ route('pustaka.catalog') }}">Lihat semua {{ $bookCount }} buku <span aria-hidden="true">→</span></a>
        </div>
        <div class="book-grid">
            @foreach($featured as $slug => $book)
                @include('pustaka.card', compact('slug', 'book', 'topics'))
            @endforeach
        </div>
    </section>
@endsection
