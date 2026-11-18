'use strict';

const reader = document.querySelector('[data-archive-reader]');

if (reader && typeof reader.showModal === 'function') {
    const frame = reader.querySelector('iframe');
    const heading = reader.querySelector('#archive-reader-title');
    const fallback = reader.querySelector('[data-archive-fallback]');

    document.querySelectorAll('[data-book-preview]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            heading.textContent = link.dataset.bookTitle;
            frame.title = 'Pratinjau ' + link.dataset.bookTitle;
            fallback.href = link.href;
            reader.showModal();
            frame.src = link.dataset.bookPreview;
        });
    });

    reader.addEventListener('close', () => {
        frame.removeAttribute('src');
    });
}

async function recoverOpenLibrarySearch(panel) {
    const terms = panel.dataset.openLibraryRecovery
        .normalize('NFD').replace(/\p{M}/gu, '').toLowerCase().match(/[a-z0-9]+/g) ?? [];
    const heading = panel.querySelector('h2');
    const description = panel.querySelector('p');
    if (!terms.length || !heading || !description) return;

    const subject = '(subject:"computer networks" OR subject:"computer networking" OR subject:"TCP/IP")';
    const general = ['jaringan komputer', 'computer networks', 'computer networking'].includes(terms.join(' '));
    const parts = general ? [] : terms.slice(0, 12).map(term => '"' + term + '"');
    const url = new URL('https://openlibrary.org/search.json');
    url.search = new URLSearchParams({
        q: [subject, ...parts].join(' AND '),
        fields: 'key,title,author_name,first_publish_year',
        limit: 12,
    });

    const originalHeading = heading.textContent;
    const originalDescription = description.textContent;
    heading.textContent = 'Mencoba koneksi dari peramban';
    description.textContent = 'Menunggu hasil pencarian dari Open Library.';

    try {
        const response = await fetch(url, {
            credentials: 'omit',
            referrerPolicy: 'no-referrer',
            signal: AbortSignal.timeout(12000),
        });
        if (!response.ok) throw new Error('Open Library HTTP ' + response.status);
        const data = await response.json();
        if (!Array.isArray(data.docs)) throw new Error('Unexpected response format');

        const books = new Map();
        for (const book of data.docs.slice(0, 12)) {
            if (typeof book?.key !== 'string' || typeof book.title !== 'string' || !book.title.trim()) continue;
            const match = book.key.match(/^(?:\/works\/)?(OL[0-9]+W)$/);
            if (match) books.set(match[1], book);
        }

        if (books.size === 0) {
            heading.textContent = 'Belum ada hasil untuk “' + panel.dataset.openLibraryRecovery + '”';
            description.textContent = 'Coba judul atau nama penulis lain di topik jaringan komputer.';
            return;
        }

        const results = document.createElement('div');
        results.className = 'book-grid';
        for (const [id, book] of books) {
            const card = document.createElement('article');
            card.className = 'book-card upstream-book';
            const info = document.createElement('div');
            info.className = 'book-info';
            const title = document.createElement('h2');
            title.className = 'book-title';
            title.textContent = book.title.slice(0, 220);
            const author = document.createElement('p');
            author.className = 'book-author';
            const names = Array.isArray(book.author_name) ? book.author_name.filter(name => typeof name === 'string') : [];
            author.textContent = names.slice(0, 4).join(', ').slice(0, 200);
            const link = document.createElement('a');
            link.className = 'book-link';
            link.href = 'https://openlibrary.org/works/' + id;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = 'Detail di Open Library ↗';
            info.append(title, author, link);
            card.append(info);
            results.append(card);
        }

        panel.className = 'results-head';
        panel.removeAttribute('data-open-library-recovery');
        panel.textContent = books.size + ' hasil Open Library melalui peramban. Status pratinjau tersedia di halaman detail sumber.';
        panel.after(results);
    } catch {
        heading.textContent = originalHeading;
        description.textContent = originalDescription;
    }
}

const recoveryPanel = document.querySelector('[data-open-library-recovery]');
if (recoveryPanel) void recoverOpenLibrarySearch(recoveryPanel);
