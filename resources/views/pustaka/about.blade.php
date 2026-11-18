@extends('pustaka.layout')

@section('title', 'Tentang Pustaka Jaringan')

@section('content')
    <div class="shell page-head about-head">
        <p class="eyebrow">Tentang perpustakaan</p>
        <h1>Pustaka Jaringan<span>.</span></h1>
        <p>Perpustakaan digital untuk mempelajari jaringan komputer, dari konsep dasar hingga penerapannya.</p>
    </div>

    <div class="shell about-content">
        <section class="about-panel">
            <h2>Untuk siapa?</h2>
            <p>Pustaka ini ditujukan untuk mahasiswa, pengajar, dan pembaca yang ingin memahami jaringan komputer. Koleksinya mencakup cara kerja internet, arsitektur dan protokol, pemrograman jaringan, serta jaringan nirkabel.</p>
        </section>
        <section class="about-panel">
            <h2>Cara membaca</h2>
            <ol>
                <li>Buka Katalog dan cari buku berdasarkan judul, penulis, atau topik.</li>
                <li>Buka detail buku untuk membaca ringkasan dan melihat sumbernya.</li>
                <li>Pilih Pratinjau PDF untuk membaca di peramban atau Unduh PDF untuk menyimpan dokumen.</li>
            </ol>
            <p>Katalog terbuka untuk semua pengunjung tanpa akun.</p>
        </section>
        <section class="about-panel" id="lisensi">
            <h2>Sumber dan lisensi</h2>
            <p>Setiap buku mencantumkan penulis, halaman sumber, dan lisensinya. Ringkasan berbahasa Indonesia dan ilustrasi sampul dibuat untuk katalog. File PDF berasal dari sumber yang tercantum dan disajikan sesuai edisi sumber.</p>
            <p>Ikuti ketentuan pada halaman lisensi buku saat membagikan atau menggunakan kembali dokumennya.</p>
        </section>
        <a class="button button-primary" href="{{ route('pustaka.catalog') }}">Buka katalog <span aria-hidden="true">→</span></a>
    </div>
@endsection
