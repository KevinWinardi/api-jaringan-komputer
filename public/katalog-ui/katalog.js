'use strict';

const byId = (id) => document.getElementById(id);
const sections = ['login', 'koleksi', 'jelajah', 'panduan'];
const apiBase = document.body.dataset.api;
const storageKey = 'pustaka-jaringan.session';
const number = new Intl.NumberFormat('id-ID');
const fieldLabels = {
    kode: 'Kode buku', judul: 'Judul', penulis: 'Penulis', kategori: 'Kategori',
    tahun: 'Tahun terbit', stok: 'Stok', cover_id: 'ID sampul', open_library_key: 'Kunci Open Library',
    tingkat_kesulitan: 'Tingkat kesulitan',
};
const state = {
    session: null, user: null, collection: null, external: null, detail: null,
    editing: null, deleting: null, localPage: 1, query: '', category: '', level: '',
    externalItems: [], externalCount: 0, externalOffset: 0,
};

function parseSession() {
    try {
        const session = JSON.parse(sessionStorage.getItem(storageKey));
        return session?.token && session.until > Date.now() && Array.isArray(session.abilities) ? session : null;
    } catch {
        return null;
    }
}

function setSession(session) {
    state.session = session;
    try {
        if (session) sessionStorage.setItem(storageKey, JSON.stringify(session));
        else sessionStorage.removeItem(storageKey);
    } catch {
        // A browser that blocks storage can still use the current page.
    }
}

function canWrite() {
    return state.user?.is_admin === true && state.session?.abilities.includes('book:write');
}

function message(id, value = '', kind = 'error') {
    const box = byId(id);
    box.textContent = value;
    box.dataset.kind = kind;
    box.hidden = !value;
}

function button(label, classes, action) {
    const control = document.createElement('button');
    control.className = classes;
    control.type = 'button';
    control.textContent = label;
    control.addEventListener('click', action);
    return control;
}

class ApiError extends Error {
    constructor(status, payload) {
        const defaults = {
            401: 'Email, kata sandi, atau sesi masuk tidak valid.',
            403: 'Akun atau mode akses ini tidak memiliki izin.',
            404: 'Data yang diminta sudah tidak tersedia.',
            429: 'Terlalu banyak permintaan. Coba lagi sebentar.',
        };
        const fields = Object.entries(payload?.errors ?? {}).map(([key, errors]) => {
            const label = fieldLabels[key.split('.')[0]] ?? key;
            return label + ' — ' + (Array.isArray(errors) ? errors.join(' ') : String(errors));
        });
        let detail = defaults[status] ?? 'Permintaan gagal. Coba lagi.';
        if (status >= 500) detail = 'Server sedang mengalami gangguan. Coba lagi.';
        else if (fields.length) detail = fields.join(' ');
        else if (status !== 403 && typeof payload?.message === 'string') detail = payload.message;
        super(detail);
        this.status = status;
    }
}

async function api(path, { method = 'GET', body, signal, authenticated = true } = {}) {
    const session = state.session;
    const headers = { Accept: 'application/json' };
    if (authenticated && session) headers.Authorization = 'Bearer ' + session.token;
    if (body !== undefined) headers['Content-Type'] = 'application/json';
    let response;
    try {
        response = await fetch(apiBase + path, {
            method, headers, body: body === undefined ? undefined : JSON.stringify(body),
            signal, credentials: 'omit', cache: 'no-store',
        });
    } catch (error) {
        if (signal?.aborted) throw error;
        throw new Error('Koneksi ke aplikasi terputus. Periksa server Laravel dan coba lagi.');
    }
    const payload = response.headers.get('content-type')?.includes('application/json') ? await response.json() : null;
    if (!response.ok) {
        const providerError = path.startsWith('/open-library?') && payload?.message === 'Gagal mengambil data dari Open Library';
        if (response.status === 401 && authenticated && session && state.session === session && !providerError) {
            endSession('Sesi berakhir. Silakan masuk kembali.');
        }
        if (providerError || (path.startsWith('/open-library?') && response.status >= 500)) {
            throw new Error('Open Library sedang tidak dapat diakses. Coba lagi sebentar.');
        }
        throw new ApiError(response.status, payload);
    }
    if (response.status === 204) return null;
    if (!payload) throw new Error('Server mengirim format data yang tidak dikenali.');
    return payload;
}

async function during(form, action) {
    const submit = form.querySelector('[type=submit]');
    if (submit.disabled) return;
    const dialog = form.closest('dialog');
    const close = dialog ? Array.from(dialog.querySelectorAll('[data-close]')) : [];
    const preventCancel = (event) => event.preventDefault();
    submit.disabled = true;
    close.forEach((control) => { control.disabled = true; });
    dialog?.addEventListener('cancel', preventCancel);
    form.setAttribute('aria-busy', 'true');
    try {
        await action();
    } finally {
        submit.disabled = false;
        close.forEach((control) => { control.disabled = false; });
        dialog?.removeEventListener('cancel', preventCancel);
        form.setAttribute('aria-busy', 'false');
    }
}

function endSession(notice = '') {
    for (const key of ['collection', 'detail', 'external']) {
        state[key]?.abort();
        state[key] = null;
    }
    document.querySelectorAll('dialog[open]').forEach((dialog) => dialog.close());
    setSession(null);
    state.user = null;
    Object.assign(state, { localPage: 1, query: '', category: '', level: '', externalItems: [], externalCount: 0, externalOffset: 0 });
    ['books', 'external-results', 'detail-content'].forEach((id) => byId(id).replaceChildren());
    byId('more-external').hidden = true;
    byId('stats').hidden = true;
    byId('level-summary').hidden = true;
    byId('pagination').hidden = true;
    byId('external-query').value = '';
    byId('local-query').value = '';
    byId('local-category').value = '';
    byId('local-level').value = '';
    byId('external-status').textContent = 'Masukkan judul atau nama penulis untuk mencari.';
    display('koleksi');
    message('notice', notice);
}

function destination() {
    return sections.includes(location.hash.slice(1)) ? location.hash.slice(1) : 'koleksi';
}

function display(requested, focus = false) {
    const section = requested === 'login' && state.user ? 'koleksi' : requested;
    for (const id of sections) byId(id).hidden = id !== section;
    document.querySelectorAll('[data-tab]').forEach((link) => {
        if (link.dataset.tab === section) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
    byId('account-name').textContent = state.user?.nama ?? '';
    byId('account-name').hidden = !state.user;
    byId('logout').hidden = !state.user;
    byId('go-login').hidden = Boolean(state.user) || section === 'login';
    byId('add-book').hidden = !canWrite();
    if (section === 'koleksi') {
        loadCollection(state.localPage);
        loadStats();
    }
    if (focus) {
        if (section === 'login') byId('email').focus();
        else byId(section).querySelector('h1').focus();
    }
}

function go(section) {
    if (location.hash !== '#' + section) {
        history.pushState(null, '', '#' + section);
        window.scrollTo(0, 0);
    }
    display(section, true);
}

async function loadStats() {
    const session = state.session;
    byId('stats').hidden = true;
    byId('level-summary').hidden = true;

    try {
        const stats = await api('/stats', { authenticated: false });
        if (state.session !== session || !Number.isFinite(stats.total_book)) return;

        byId('stats-total').textContent = number.format(stats.total_book);
        byId('stats').hidden = false;

        const counts = stats.tingkat_kesulitan;
        if (!counts || typeof counts !== 'object') return;

        const keys = {
            pemula: 'level-count-pemula',
            menengah: 'level-count-menengah',
            lanjutan: 'level-count-lanjutan',
            belum_ditentukan: 'level-count-belum',
        };
        for (const [key, elementId] of Object.entries(keys)) {
            byId(elementId).textContent = number.format(Number(counts[key]) || 0);
        }
        byId('level-summary').hidden = false;
    } catch {
        // Katalog tetap dapat digunakan ketika statistik gagal.
    }
}

function levelLabel(value) {
    return {
        pemula: 'Pemula',
        menengah: 'Menengah',
        lanjutan: 'Lanjutan',
    }[value] ?? 'Belum ditentukan';
}

function values(value) {
    return Array.isArray(value) ? value.filter((item) => typeof item === 'string' && item.trim()) : [];
}

function year(value) {
    return value > 0 ? String(value) : 'Tahun tidak diketahui';
}

function coverImage(cover, id) {
    if (!Number.isInteger(Number(id)) || Number(id) < 1) return;
    const image = document.createElement('img');
    image.alt = '';
    image.loading = 'lazy';
    image.referrerPolicy = 'no-referrer';
    image.addEventListener('error', () => image.remove(), { once: true });
    image.src = 'https://covers.openlibrary.org/b/id/' + Number(id) + '-M.jpg?default=false';
    cover.append(image);
}

function bookCard(book, external = false) {
    const item = byId('book-template').content.firstElementChild.cloneNode(true);
    item.querySelector('h3').textContent = book.judul;
    const categories = values(book.kategori);
    item.querySelector('.chip').textContent = external ? 'Open Library' : categories[0] || 'Tanpa kategori';
    item.querySelector('.year').textContent = year(book.tahun);
    const difficulty = item.querySelector('.difficulty');
    difficulty.hidden = external;
    if (!external) {
        difficulty.textContent = levelLabel(book.tingkat_kesulitan);
        difficulty.dataset.empty = String(!book.tingkat_kesulitan);
    }
    item.querySelector('.author').textContent = values(book.penulis).join(', ') || 'Penulis tidak diketahui';
    const stock = item.querySelector('.stock');
    stock.hidden = external;
    if (!external) {
        stock.textContent = book.stok > 0 ? 'Stok ' + number.format(book.stok) : 'Stok habis';
        stock.dataset.available = String(book.stok > 0);
    }
    coverImage(item.querySelector('.cover'), book.cover_id);
    const actions = item.querySelector('.actions');
    const action = !external
        ? button('Lihat detail', 'button secondary small', () => showDetail(book.id))
        : canWrite() && book.open_library_key
            ? button('Impor metadata', 'button secondary small', () => openEditor(book, 'import'))
            : null;
    if (action) {
        action.setAttribute('aria-label', action.textContent + ' — ' + book.judul);
        actions.append(action);
    }
    if (external && book.open_library_key) {
        const source = document.createElement('a');
        source.className = 'button secondary small';
        source.href = 'https://openlibrary.org' + book.open_library_key;
        source.target = '_blank';
        source.rel = 'noopener noreferrer';
        source.textContent = 'Lihat sumber ↗';
        source.setAttribute('aria-label', 'Lihat sumber di tab baru — ' + book.judul);
        actions.append(source);
    }
    return item;
}

async function loadCollection(page) {
    state.collection?.abort();
    const controller = new AbortController();
    state.collection = controller;
    const { signal } = controller;
    const grid = byId('books');
    const status = byId('collection-status');
    const filtered = Boolean(state.query || state.category || state.level);
    const query = new URLSearchParams({ page: String(Math.max(1, page)), per_page: '8' });
    if (state.query) query.set('q', state.query);
    if (state.category) query.set('kategori', state.category);
    if (state.level) query.set('tingkat_kesulitan', state.level);

    grid.setAttribute('aria-busy', 'true');
    byId('prev-page').disabled = true;
    byId('next-page').disabled = true;
    status.textContent = filtered ? 'Mencari di koleksi lokal…' : 'Memuat koleksi…';

    try {
        let result = await api('/books?' + query, { signal, authenticated: false });
        if (page > result.meta.last_page && result.meta.last_page >= 1) {
            query.set('page', String(result.meta.last_page));
            result = await api('/books?' + query, { signal, authenticated: false });
        }
        if (signal.aborted) return;

        const items = Array.isArray(result.data) ? result.data : [];
        const { total, current_page: current, last_page: last } = result.meta;
        state.localPage = current;
        grid.replaceChildren(...items.map((book) => bookCard(book)));
        status.textContent = total
            ? number.format(total) + (filtered ? ' buku cocok dengan pencarian.' : ' buku di koleksi.')
            : filtered ? 'Tidak ada buku yang cocok. Ubah kata kunci atau kategori.' : 'Koleksi masih kosong.';
        byId('pagination').hidden = last <= 1;
        byId('page-label').textContent = 'Halaman ' + current + ' dari ' + last;
        byId('prev-page').disabled = current <= 1;
        byId('next-page').disabled = current >= last;
    } catch (error) {
        if (signal.aborted) return;
        grid.replaceChildren();
        byId('pagination').hidden = true;
        status.replaceChildren(error.message, button('Coba lagi', 'button secondary small', () => loadCollection(page)));
    } finally {
        if (state.collection === controller) grid.setAttribute('aria-busy', 'false');
    }
}

function externalBook(doc) {
    const rawKey = typeof doc.key === 'string' ? doc.key : '';
    const id = rawKey.match(/^(?:\/works\/)?(OL\d+W)$/i)?.[1];
    const key = id ? '/works/' + id.toUpperCase() : '';
    return {
        kode: id ? 'OL-' + id.toUpperCase().slice(0, 21) : '',
        judul: typeof doc.title === 'string' && doc.title.trim() ? doc.title : 'Judul tidak diketahui',
        penulis: values(doc.author_name),
        kategori: values(doc.subject).slice(0, 3),
        tahun: Number(doc.first_publish_year) || null,
        cover_id: Number(doc.cover_i) || null,
        open_library_key: key,
        stok: 0,
        tingkat_kesulitan: null,
    };
}

function appendExternal() {
    const start = state.externalOffset;
    const items = state.externalItems.slice(start, start + 8);
    byId('external-results').append(...items.map((book) => bookCard(book, true)));
    state.externalOffset += items.length;
    byId('external-status').textContent = items.length || state.externalOffset
        ? 'Menampilkan ' + number.format(state.externalOffset) + ' dari ' + number.format(state.externalItems.length) + ' hasil yang diterima.' + (state.externalCount > state.externalItems.length ? ' Open Library menemukan ' + number.format(state.externalCount) + ' judul. Persempit kata kunci untuk melihat hasil yang lebih relevan.' : '')
        : 'Tidak ada hasil dari Open Library. Coba judul atau nama penulis lain.';
    byId('more-external').hidden = state.externalOffset >= state.externalItems.length;
}

async function loadExternal(query) {
    state.external?.abort();
    const controller = new AbortController();
    state.external = controller;
    const status = byId('external-status');
    const results = byId('external-results');
    status.textContent = 'Mencari di Open Library…';
    byId('more-external').hidden = true;
    results.replaceChildren();
    results.setAttribute('aria-busy', 'true');
    try {
        const response = await api('/open-library?' + new URLSearchParams({ q: query }), { signal: controller.signal, authenticated: false });
        if (controller.signal.aborted) return;
        state.externalItems = Array.isArray(response.data)
            ? response.data
            : Array.isArray(response.docs) ? response.docs.map(externalBook) : [];
        state.externalCount = Math.max(Number(response.meta?.total_found ?? response.numFound) || 0, state.externalItems.length);
        state.externalOffset = 0;
        appendExternal();
    } catch (error) {
        if (controller.signal.aborted) return;
        status.replaceChildren(error.message, button('Coba lagi', 'button secondary small', () => loadExternal(query)));
    } finally {
        if (state.external === controller) results.setAttribute('aria-busy', 'false');
    }
}

function detailRows(book) {
    const list = document.createElement('dl');
    list.className = 'details';
    const fields = {
        'Kode': book.kode,
        'Penulis': values(book.penulis).join(', '),
        'Tahun': year(book.tahun),
        'Kategori': values(book.kategori).join(', '),
        'Tingkat kesulitan': levelLabel(book.tingkat_kesulitan),
        'Stok': book.stok === 0 ? 'Habis' : number.format(book.stok),
    };
    for (const [label, value] of Object.entries(fields)) {
        const term = document.createElement('dt');
        const definition = document.createElement('dd');
        term.textContent = label;
        definition.textContent = value || '—';
        list.append(term, definition);
    }
    return list;
}

async function showDetail(id) {
    state.detail?.abort();
    const controller = new AbortController();
    state.detail = controller;
    state.editing = null;
    const dialog = byId('detail-dialog');
    const content = byId('detail-content');
    byId('detail-title').textContent = 'Detail buku';
    byId('detail-actions').hidden = true;
    content.textContent = 'Memuat detail…';
    if (!dialog.open) dialog.showModal();
    try {
        const { data } = await api('/books/' + id, { signal: controller.signal, authenticated: false });
        if (controller.signal.aborted || !dialog.open) return;
        state.editing = data;
        byId('detail-title').textContent = data.judul;
        content.replaceChildren(detailRows(data));
        byId('detail-actions').hidden = !canWrite();
    } catch (error) {
        if (!controller.signal.aborted && dialog.open) content.textContent = error.message;
    }
}

function openEditor(book, mode) {
    if (!canWrite()) return;
    const form = byId('book-form');
    const importMode = mode === 'import';
    form.reset();
    form.dataset.bookId = mode === 'edit' ? book.id : '';
    byId('editor-title').textContent = { add: 'Tambah buku', edit: 'Ubah buku', import: 'Impor buku' }[mode];
    byId('import-context').hidden = !importMode;
    byId('open_library_key').readOnly = importMode;
    byId('tahun').max = new Date().getFullYear() + 1;
    message('editor-error');
    form.elements.stok.value = book?.stok ?? 0;
    form.elements.tingkat_kesulitan.value = book?.tingkat_kesulitan ?? '';
    if (book) {
        form.elements.kode.value = book.kode ?? '';
        form.elements.judul.value = importMode ? book.judul.slice(0, 250) : book.judul;
        for (const field of ['penulis', 'kategori']) {
            const lines = values(book[field]);
            form.elements[field].value = (importMode ? lines.map((line) => line.slice(0, 200)) : lines).join('\n');
        }
        form.elements.tahun.value = book.tahun > 0 && book.tahun <= Number(byId('tahun').max) ? book.tahun : '';
        form.elements.open_library_key.value = book.open_library_key ?? '';
        form.elements.cover_id.value = book.cover_id ?? '';
    }
    byId('editor-dialog').showModal();
}

function refreshAfterWrite(messageText, resetPage = false) {
    if (resetPage) {
        Object.assign(state, { localPage: 1, query: '', category: '', level: '' });
        byId('local-query').value = '';
        byId('local-category').value = '';
        byId('local-level').value = '';
    }
    go('koleksi');
    message('notice', messageText, 'success');
}

byId('login-form').addEventListener('submit', (event) => {
    event.preventDefault();
    void during(event.currentTarget, async () => {
        message('login-error');
        message('notice');
        const fields = new FormData(byId('login-form'));
        try {
            const auth = await api('/auth/login', {
                method: 'POST', authenticated: false,
                body: { email: fields.get('email').trim(), password: fields.get('password'), mode: fields.get('mode'), nama_perangkat: 'Web Pustaka Jaringan' },
            });
            setSession({ token: auth.access_token, abilities: auth.abilities, until: Date.now() + auth.expires_in * 1000 });
            state.user = (await api('/auth/me')).data;
            byId('login-form').reset();
            go(destination() === 'jelajah' ? 'jelajah' : 'koleksi');
        } catch (error) {
            setSession(null);
            message('login-error', error.message);
        }
    });
});

byId('logout').addEventListener('click', async (event) => {
    const control = event.currentTarget;
    control.disabled = true;
    try {
        await api('/auth/logout', { method: 'POST' });
        endSession();
        go('koleksi');
    } catch (error) {
        const text = state.session ? 'Sesi ditutup pada tab ini. Server tidak mengonfirmasi pencabutan token.' : error.message;
        endSession(text);
        go('koleksi');
    } finally {
        control.disabled = false;
    }
});

byId('go-login').addEventListener('click', () => go('login'));
window.addEventListener('hashchange', () => {
    if (location.hash === '#main') return;
    message('notice');
    display(destination(), true);
});

byId('filter-form').addEventListener('submit', (event) => {
    event.preventDefault();
    state.query = byId('local-query').value.trim();
    state.category = byId('local-category').value.trim();
    state.level = byId('local-level').value;
    loadCollection(1);
});
byId('filter-form').addEventListener('reset', () => {
    Object.assign(state, { query: '', category: '', level: '' });
    loadCollection(1);
});
byId('prev-page').addEventListener('click', () => loadCollection(state.localPage - 1));
byId('next-page').addEventListener('click', () => loadCollection(state.localPage + 1));
byId('external-form').addEventListener('submit', (event) => {
    event.preventDefault();
    const query = byId('external-query').value.trim();
    if (query) loadExternal(query);
    else byId('external-status').textContent = 'Masukkan judul atau nama penulis untuk mencari.';
});
byId('more-external').addEventListener('click', appendExternal);
byId('add-book').addEventListener('click', () => openEditor(null, 'add'));
byId('edit-book').addEventListener('click', () => openEditor(state.editing, 'edit'));
byId('delete-book').addEventListener('click', () => {
    if (!canWrite() || !state.editing) return;
    state.deleting = state.editing.id;
    byId('delete-text').textContent = 'Buku “' + state.editing.judul + '” akan dikeluarkan dari koleksi lokal.';
    message('delete-error');
    byId('delete-dialog').showModal();
});

document.querySelectorAll('[data-close]').forEach((control) => {
    control.addEventListener('click', () => control.closest('dialog').close());
});

byId('book-form').addEventListener('submit', (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    if (!canWrite()) return;
    void during(form, async () => {
        message('editor-error');
        const fields = new FormData(form);
        const value = (key) => String(fields.get(key)).trim();
        const lines = (key) => value(key).split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
        const payload = {
            kode: value('kode'), judul: value('judul'), penulis: lines('penulis'), kategori: lines('kategori'),
            tahun: value('tahun') === '' ? null : Number(value('tahun')), stok: Number(value('stok')),
            open_library_key: value('open_library_key') || null, cover_id: value('cover_id') === '' ? null : Number(value('cover_id')),
            tingkat_kesulitan: value('tingkat_kesulitan') || null,
        };
        const id = form.dataset.bookId;
        try {
            if (!payload.kode || !payload.judul || !payload.penulis.length || !payload.kategori.length) {
                throw new Error('Isi kode, judul, minimal satu penulis, dan minimal satu kategori.');
            }
            await api(id ? '/books/' + id : '/books', { method: id ? 'PATCH' : 'POST', body: payload });
            byId('editor-dialog').close();
            if (byId('detail-dialog').open) byId('detail-dialog').close();
            refreshAfterWrite(id ? 'Perubahan buku tersimpan.' : 'Buku ditambahkan ke koleksi lokal.', !id);
        } catch (error) {
            if (byId('editor-dialog').open) message('editor-error', error.message);
        }
    });
});

byId('delete-form').addEventListener('submit', (event) => {
    event.preventDefault();
    if (!canWrite() || !state.deleting) return;
    void during(event.currentTarget, async () => {
        message('delete-error');
        try {
            await api('/books/' + state.deleting, { method: 'DELETE' });
            byId('delete-dialog').close();
            byId('detail-dialog').close();
            refreshAfterWrite('Buku dihapus dari koleksi lokal.');
        } catch (error) {
            if (byId('delete-dialog').open) message('delete-error', error.message);
        }
    });
});

async function start() {
    const session = parseSession();
    setSession(session);
    if (session) {
        try {
            state.user = (await api('/auth/me')).data;
        } catch (error) {
            endSession(error.message);
            return;
        }
    }
    display(destination());
}
void start();
