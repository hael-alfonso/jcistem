<div class="list-row project-document-row">
    <span>
        <strong>{{ $label ?? $document->title }}</strong>
        <small>{{ $meta ?? (($document->category ? $document->category.' · ' : '').$document->original_name) }}</small>
    </span>
    <span class="project-document-actions no-print">
        @if($document->canPreviewInline())
            <button class="text-link" type="button" data-document-preview data-preview-url="{{ route($document->extension() === 'docx' ? 'documents.original-preview' : 'documents.preview', $document) }}" data-download-url="{{ route('documents.download', $document) }}" data-preview-name="{{ $document->original_name }}" data-preview-type="{{ strtoupper($document->extension()) }}"><x-icon name="eye" /> View file</button>
        @endif
        <a class="text-link" href="{{ route('documents.download', $document) }}"><x-icon name="download" /> Download original</a>
    </span>
</div>
