<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pratinjau PDF</title>
    <link rel="stylesheet" href="{{ asset('pustaka/pustaka.css') }}">
</head>
<body class="pdf-error">
    <main>
        <h1>Pratinjau belum bisa dimuat</h1>
        <p>Coba buka PDF di situs sumber.</p>
        <a class="button button-primary" href="{{ $book['pdf'] }}" target="_blank" rel="noopener noreferrer">Buka PDF sumber ↗</a>
        @if(isset($book['alternate_pdf']))
            <p class="alternative"><a href="{{ $book['alternate_pdf'] }}" target="_blank" rel="noopener noreferrer">Coba tautan PDF alternatif ↗</a></p>
        @endif
    </main>
</body>
</html>
