<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->original_name }} · Document preview</title>
    <link rel="stylesheet" href="{{ asset('assets/css/docx-original-viewer.css') }}?v={{ filemtime(public_path('assets/css/docx-original-viewer.css')) }}">
    <script defer src="{{ asset('assets/vendor/docx-preview/jszip.min.js') }}"></script>
    <script defer src="{{ asset('assets/vendor/docx-preview/docx-preview.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/docx-original-viewer.js') }}?v={{ filemtime(public_path('assets/js/docx-original-viewer.js')) }}"></script>
</head>
<body class="{{ request()->boolean('embedded') ? 'is-embedded' : '' }}" data-document-url="{{ route('documents.download', $document) }}">
    <header class="viewer-header">
        <div><strong>{{ $document->original_name }}</strong><span>Private browser preview of the uploaded DOCX</span></div>
        <nav aria-label="Document actions"><a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener">View PDF rendering</a><a href="{{ route('documents.download', $document) }}">Download original</a></nav>
    </header>
    <div class="viewer-status" id="viewer-status" role="status">Opening document…</div>
    <main id="document-pages" aria-label="Document pages"></main>
    <footer>Browser layout may differ slightly from Microsoft Word. Download the original DOCX to verify exact formatting.</footer>
</body>
</html>
