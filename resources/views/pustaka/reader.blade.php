@extends('pustaka.layout')

@section('title', 'Pratinjau '.$book['title'].' — Pustaka Jaringan')

@section('content')
    <section class="shell reader-shell">
        <nav aria-label="Jejak navigasi" class="breadcrumbs"><a href="{{ route('pustaka.detail', ['buku' => $slug]) }}">← Kembali ke detail buku</a></nav>
        <div class="reader-header">
            <div>
                <p class="eyebrow">Pratinjau PDF</p>
                <h1>{{ $book['title'] }}</h1>
                <p>{{ $book['author'] }}</p>
            </div>
            <div class="button-row">
                <a class="button button-secondary" href="{{ $book['pdf'] }}" target="_blank" rel="noopener noreferrer">Buka PDF sumber ↗</a>
                <a class="button button-primary" href="{{ route('pustaka.download', ['buku' => $slug]) }}">Unduh PDF ↓</a>
            </div>
        </div>
        <p id="preview-help" class="reader-help">Jika PDF tidak tampil di sini, gunakan tombol Buka PDF sumber atau Unduh PDF.</p>
        <iframe class="reader-frame" src="{{ route('pustaka.preview', ['buku' => $slug]) }}" title="Pratinjau {{ $book['title'] }}" aria-describedby="preview-help"></iframe>
    </section>
@endsection
