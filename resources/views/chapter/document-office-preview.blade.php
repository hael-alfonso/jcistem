<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->original_name }} · Private file preview</title>
    <link rel="stylesheet" href="{{ asset('assets/css/docx-original-viewer.css') }}?v={{ filemtime(public_path('assets/css/docx-original-viewer.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/office-viewer.css') }}?v={{ filemtime(public_path('assets/css/office-viewer.css')) }}">
    <script type="module" src="{{ asset('assets/vendor/office/private-office-viewer.js') }}?v={{ file_exists(public_path('assets/vendor/office/private-office-viewer.js')) ? filemtime(public_path('assets/vendor/office/private-office-viewer.js')) : 'dev' }}"></script>
</head>
<body class="{{ request()->boolean('embedded') ? 'is-embedded' : '' }}" data-document-url="{{ route('documents.download', $document) }}" data-document-name="{{ $document->original_name }}">
    <header class="viewer-header">
        <div><strong>{{ $document->original_name }}</strong><span>Private browser preview of the uploaded Office file</span></div>
        <nav aria-label="Document actions"><a href="{{ route('documents.download', $document) }}">Download original</a></nav>
    </header>
    <div class="viewer-status" id="viewer-status" role="status">Opening document…</div>
    <main id="document-pages" aria-label="Office document preview"></main>
    <footer>Spreadsheet cells and slide content are shown for review. The original file may have layout, formulas, transitions, or features that a browser preview cannot reproduce exactly.</footer>
</body>
</html>
