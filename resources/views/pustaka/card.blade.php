<article class="book-card">
    <a class="book-art art-{{ $book['tone'] }}" href="{{ route('pustaka.detail', ['buku' => $slug]) }}" aria-label="Lihat detail {{ $book['title'] }}" tabindex="-1" aria-hidden="true">
        @include('pustaka.cover', ['book' => $book, 'topic' => $topics[$book['topic']]])
    </a>
    <div class="book-info">
        <p class="book-topic">{{ $topics[$book['topic']] }} <span aria-hidden="true">·</span> PDF</p>
        <h2 class="book-title"><a href="{{ route('pustaka.detail', ['buku' => $slug]) }}">{{ $book['title'] }}</a></h2>
        <p class="book-author">{{ $book['author'] }}</p>
        <p class="book-summary">{{ $book['summary'] }}</p>
        <a class="book-link" href="{{ route('pustaka.reader', ['buku' => $slug]) }}" aria-label="Pratinjau {{ $book['title'] }}">
            Pratinjau <span aria-hidden="true">↗</span>
        </a>
    </div>
</article>
