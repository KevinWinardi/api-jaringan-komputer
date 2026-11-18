<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Pustaka Jaringan — Perpustakaan Digital')</title>
    <meta name="description" content="@yield('description', 'Perpustakaan digital untuk belajar jaringan komputer. Cari, baca, dan unduh buku akses terbuka.')">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="theme-color" content="#f6f5ed">
    <link rel="icon" href="{{ asset('pustaka/icon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('pustaka/pustaka.css') }}">
    @stack('head')
</head>
<body>
    <a class="skip" href="#konten">Lewati ke konten</a>
    <header class="masthead">
        <div class="shell header-row">
            <a class="brand" href="{{ route('pustaka.home') }}">
                <span class="brand-icon" aria-hidden="true"><i></i><i></i><i></i></span>
                Pustaka<span>Jaringan</span>
            </a>
            <nav class="main-nav" aria-label="Navigasi utama">
                <a href="{{ route('pustaka.home') }}" @if(request()->routeIs('pustaka.home')) aria-current="page" @endif>Dashboard</a>
                <a href="{{ route('pustaka.catalog') }}" @if(request()->routeIs('pustaka.catalog', 'pustaka.open-library')) aria-current="page" @endif>Katalog</a>
                <a href="{{ route('pustaka.about') }}" @if(request()->routeIs('pustaka.about')) aria-current="page" @endif>Tentang</a>
            </nav>
        </div>
    </header>

    <main id="konten" tabindex="-1">
        @yield('content')
    </main>

    <footer class="site-footer shell">
        <span>Pustaka Jaringan</span>
        <span>Perpustakaan digital jaringan komputer</span>
        <a href="{{ route('pustaka.about') }}#lisensi">Sumber dan lisensi</a>
    </footer>
</body>
</html>
