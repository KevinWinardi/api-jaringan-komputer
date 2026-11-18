<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pustaka Jaringan — Katalog Buku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('katalog-ui/katalog.css') }}" rel="stylesheet">
    <link href="{{ asset('katalog-ui/tingkat-kesulitan.css') }}" rel="stylesheet">
    <script src="{{ asset('katalog-ui/katalog.js') }}" defer></script>
</head>
<body data-api="{{ url('/api/v1') }}">
    <a class="skip" href="#main">Lewati ke konten</a>
    <header class="site-header">
        <div class="shell header-inner">
            <a class="brand" href="#koleksi"><span class="brand-mark" aria-hidden="true">⌘</span> Pustaka<span class="brand-accent">Jaringan</span></a>
            <nav aria-label="Navigasi utama">
                <a href="#koleksi" data-tab="koleksi">Koleksi</a>
                <a href="#jelajah" data-tab="jelajah">Jelajah API</a>
                <a href="#panduan" data-tab="panduan">Panduan API</a>
            </nav>
            <div class="account">
                <span id="account-name" hidden></span>
                <button class="button secondary small" id="logout" hidden>Keluar</button>
                <button class="button secondary small" id="go-login">Masuk <span aria-hidden="true">↗</span></button>
            </div>
        </div>
    </header>

    <main id="main" class="shell">
        <div id="notice" class="notice" role="status" aria-live="polite" hidden></div>
        <section id="login" class="login-layout" aria-labelledby="login-title" hidden>
            <div class="hero login-hero">
                <div class="hero-content">
                    <p class="eyebrow">Katalog akademik</p>
                    <h1 id="login-title">Pustaka<br>Jaringan<span>.</span></h1>
                    <p>Temukan referensi jaringan komputer, jelajahi judul di Open Library, dan kelola koleksi dalam satu katalog.</p>
                </div>
                <div class="hero-art" aria-hidden="true"><i></i><i></i><i></i><b>⌘</b></div>
            </div>
            <div class="glass login-card">
                <h2>Masuk ke katalog</h2>
                <p>Gunakan akun yang terdaftar pada aplikasi.</p>
                <form id="login-form">
                    <label for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" autocomplete="username" placeholder="nama@contoh.com" required>
                    <label for="password">Kata sandi</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                    <label for="mode">Mode akses</label>
                    <select class="form-select" id="mode" name="mode" aria-describedby="mode-help">
                        <option value="baca">Baca katalog</option>
                        <option value="kelola">Kelola koleksi</option>
                    </select>
                    <p class="hint" id="mode-help">Mode kelola memberi akses tulis kepada akun admin.</p>
                    <p id="login-error" class="field-error" role="alert" hidden></p>
                    <button class="button primary" type="submit">Masuk <span aria-hidden="true">→</span></button>
                </form>
            </div>
        </section>

        <section id="koleksi" aria-labelledby="collection-title" hidden>
            <div class="hero compact-hero">
                <div class="hero-content">
                    <p class="eyebrow">Koleksi lokal</p>
                    <h1 id="collection-title" tabindex="-1">Koleksi buku<span>.</span></h1>
                    <p>Jelajahi referensi jaringan komputer yang tersimpan di katalog.</p>
                </div>
                <div id="stats" class="hero-stat" hidden><strong id="stats-total"></strong><span>Total buku</span></div>
                <div class="hero-art" aria-hidden="true"><i></i><i></i><i></i><b>⌘</b></div>
            </div>
            <section id="level-summary" class="glass level-summary" aria-label="Ringkasan tingkat kesulitan" hidden>
                <h2>Ringkasan tingkat kesulitan</h2>
                <div class="level-grid">
                    <div class="level-stat"><strong id="level-count-pemula">0</strong><span>Pemula</span></div>
                    <div class="level-stat"><strong id="level-count-menengah">0</strong><span>Menengah</span></div>
                    <div class="level-stat"><strong id="level-count-lanjutan">0</strong><span>Lanjutan</span></div>
                    <div class="level-stat"><strong id="level-count-belum">0</strong><span>Belum ditentukan</span></div>
                </div>
            </section>
            <div class="section-heading">
                <h2>Daftar buku</h2>
                <button class="button primary" id="add-book" hidden><span aria-hidden="true">＋</span> Tambah buku</button>
            </div>
            <form id="filter-form" class="glass filters filters-level" role="search" aria-label="Cari buku di koleksi">
                <div><label for="local-query">Judul, penulis, atau kode</label><input class="form-control" id="local-query" name="query" type="search" maxlength="250" placeholder="Contoh: Computer Networks"></div>
                <div><label for="local-category">Kategori</label><input class="form-control" id="local-category" name="category" type="search" maxlength="200" placeholder="Semua kategori"></div>
                <div>
                    <label for="local-level">Tingkat kesulitan</label>
                    <select class="form-select" id="local-level" name="tingkat_kesulitan">
                        <option value="">Semua tingkat</option>
                        <option value="pemula">Pemula</option>
                        <option value="menengah">Menengah</option>
                        <option value="lanjutan">Lanjutan</option>
                    </select>
                </div>
                <div class="actions"><button type="submit" class="button primary">Cari</button><button type="reset" class="button secondary">Reset</button></div>
            </form>
            <div id="collection-status" class="list-status" role="status" aria-live="polite"></div>
            <div id="books" class="book-grid" aria-busy="false"></div>
            <nav id="pagination" class="pagination-row" aria-label="Halaman koleksi" hidden>
                <button type="button" class="button secondary" id="prev-page">← Sebelumnya</button>
                <span id="page-label"></span>
                <button type="button" class="button secondary" id="next-page">Berikutnya →</button>
            </nav>
        </section>

        <section id="jelajah" aria-labelledby="explore-title" hidden>
            <div class="hero compact-hero explore-hero">
                <div class="hero-content"><p class="eyebrow">Open Library</p><h1 id="explore-title" tabindex="-1">Jelajah API<span>.</span></h1><p>Cari metadata buku di Open Library dan pilih judul untuk ditambahkan ke koleksi lokal.</p></div>
                <div class="hero-art" aria-hidden="true"><i></i><i></i><i></i><b>↗</b></div>
            </div>
            <form id="external-form" class="glass filters external-search" role="search" aria-label="Cari di Open Library">
                <div><label for="external-query">Judul atau penulis</label><input class="form-control" id="external-query" name="q" type="search" maxlength="250" placeholder="Contoh: Andrew S. Tanenbaum" required></div>
                <button type="submit" class="button primary">Cari di Open Library <span aria-hidden="true">↗</span></button>
            </form>
            <div id="external-status" class="list-status" role="status" aria-live="polite">Masukkan judul atau nama penulis untuk mencari.</div>
            <div id="external-results" class="book-grid" aria-busy="false"></div>
            <button id="more-external" class="button secondary more" hidden>Tampilkan hasil lainnya</button>
        </section>

        <section id="panduan" aria-labelledby="guide-title" hidden>
            <div class="hero compact-hero">
                <div class="hero-content"><p class="eyebrow">Web Service</p><h1 id="guide-title" tabindex="-1">Panduan API<span>.</span></h1><p>Endpoint yang digunakan UI untuk login, menjelajahi katalog, dan menyimpan perubahan.</p></div>
                <div class="hero-art" aria-hidden="true"><i></i><i></i><i></i><b>→</b></div>
            </div>
            <div class="glass guide-card">
                <h2>Endpoint <code>/api/v1</code></h2>
                <p>Setelah login, kirim <code>Authorization: Bearer &lt;token&gt;</code>. Untuk impor, cari metadata lewat Open Library, periksa formulir, lalu simpan ke koleksi lokal.</p>
                <div class="table-scroll" tabindex="0" role="region" aria-label="Tabel endpoint API">
                    <table class="table align-middle">
                        <thead><tr><th scope="col">Metode</th><th scope="col">Endpoint</th><th scope="col">Akses</th></tr></thead>
                        <tbody>
                            <tr><td>POST</td><td><code>/auth/login</code></td><td>Publik</td></tr>
                            <tr><td>GET</td><td><code>/auth/me</code></td><td>Akun masuk</td></tr>
                            <tr><td>POST</td><td><code>/auth/logout</code></td><td>Akun masuk</td></tr>
                            <tr><td>GET</td><td><code>/books</code> · <code>/books/{id}</code></td><td>Publik</td></tr>
                            <tr><td>GET</td><td><code>/open-library?q=...</code></td><td>Publik</td></tr>
                            <tr><td>GET</td><td><code>/stats</code></td><td>Publik</td></tr>
                            <tr><td>POST</td><td><code>/books</code></td><td>Admin · mode kelola</td></tr>
                            <tr><td>PATCH / DELETE</td><td><code>/books/{id}</code></td><td>Admin · mode kelola</td></tr>
                        </tbody>
                    </table>
                </div>
                <p>Filter backend: <code>/books?tingkat_kesulitan=pemula</code>. Ringkasan tingkat kesulitan tersedia pada <code>/stats</code>.</p>
            </div>
        </section>
        <noscript>Aktifkan JavaScript untuk menggunakan katalog dan terhubung ke API.</noscript>
    </main>
    <footer class="shell site-footer"><span>Pustaka Jaringan</span><span>Proyek Web Service</span></footer>

    <dialog id="detail-dialog" class="panel-dialog" aria-labelledby="detail-title">
        <div class="dialog-head"><h2 id="detail-title">Detail buku</h2><button class="button secondary small" type="button" data-close>Tutup</button></div>
        <div id="detail-content" role="status" aria-live="polite"></div>
        <div class="actions" id="detail-actions" hidden>
            <button class="button primary" type="button" id="edit-book">Ubah buku</button>
            <button class="button danger" type="button" id="delete-book">Hapus buku</button>
        </div>
    </dialog>

    <dialog id="editor-dialog" class="panel-dialog" aria-labelledby="editor-title">
        <div class="dialog-head"><h2 id="editor-title">Tambah buku</h2><button class="button secondary small" type="button" data-close>Tutup</button></div>
        <p id="import-context" class="hint" hidden>Periksa metadata Open Library sebelum menyimpannya ke koleksi lokal.</p>
        <form id="book-form" class="book-form">
            <div><label for="kode">Kode buku</label><input class="form-control" id="kode" name="kode" maxlength="24" required></div>
            <div><label for="judul">Judul</label><input class="form-control" id="judul" name="judul" maxlength="250" required></div>
            <div><label for="penulis">Penulis</label><textarea class="form-control" id="penulis" name="penulis" rows="2" placeholder="Satu nama per baris" required></textarea></div>
            <div><label for="kategori">Kategori</label><textarea class="form-control" id="kategori" name="kategori" rows="2" placeholder="Satu kategori per baris" required></textarea></div>
            <div><label for="tahun">Tahun terbit</label><input class="form-control" id="tahun" name="tahun" type="number" min="1"></div>
            <div><label for="stok">Stok</label><input class="form-control" id="stok" name="stok" type="number" min="0" max="2147483647" required></div>
            <div>
                <label for="tingkat_kesulitan">Tingkat kesulitan</label>
                <select class="form-select" id="tingkat_kesulitan" name="tingkat_kesulitan">
                    <option value="">Belum ditentukan</option>
                    <option value="pemula">Pemula</option>
                    <option value="menengah">Menengah</option>
                    <option value="lanjutan">Lanjutan</option>
                </select>
            </div>
            <div><label for="open_library_key">Kunci Open Library (opsional)</label><input class="form-control" id="open_library_key" name="open_library_key" maxlength="100" placeholder="/works/OL…W"></div>
            <div><label for="cover_id">ID sampul (opsional)</label><input class="form-control" id="cover_id" name="cover_id" type="number" min="1" max="2147483647"></div>
            <p id="editor-error" class="field-error wide" role="alert" hidden></p>
            <div class="actions wide"><button class="button primary" type="submit">Simpan buku</button><button class="button secondary" type="button" data-close>Batal</button></div>
        </form>
    </dialog>

    <dialog id="delete-dialog" class="panel-dialog narrow" aria-labelledby="delete-title" aria-describedby="delete-text">
        <div class="dialog-head"><h2 id="delete-title">Hapus buku?</h2></div>
        <p id="delete-text"></p>
        <p id="delete-error" class="field-error" role="alert" hidden></p>
        <form id="delete-form" class="actions">
            <button class="button secondary" type="button" data-close autofocus>Batal</button>
            <button class="button danger" type="submit">Hapus buku</button>
        </form>
    </dialog>

    <template id="book-template">
        <article class="glass book-card">
            <div class="cover" aria-hidden="true"><span>⌘</span></div>
            <div class="book-content">
                <div class="book-meta"><span class="chip"></span><span class="year"></span><span class="difficulty" hidden></span></div>
                <h3></h3>
                <p class="author"></p>
                <p class="stock"></p>
                <div class="actions"></div>
            </div>
        </article>
    </template>
</body>
</html>
