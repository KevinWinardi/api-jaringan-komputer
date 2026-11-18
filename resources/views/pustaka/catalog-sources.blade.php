<nav class="catalog-sources" aria-label="Sumber pencarian">
    <a href="{{ route('pustaka.catalog', $query !== '' ? ['q' => $query] : []) }}" @unless($external) aria-current="page" @endunless>Koleksi pustaka</a>
    <a href="{{ route('pustaka.open-library') }}" @if($external) aria-current="page" @endif>Open Library</a>
</nav>
