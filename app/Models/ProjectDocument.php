<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDocument extends ChapterRecord
{
    // Files are stored privately and served through an authorized controller.
    protected $casts = ['submitted_at' => 'datetime'];

    public function extension(): string
    {
        return strtolower(pathinfo($this->original_name ?? $this->path ?? '', PATHINFO_EXTENSION));
    }

    public function previewMimeType(): ?string
    {
        return match ($this->extension()) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'txt' => 'text/plain; charset=UTF-8',
            'csv' => 'text/csv; charset=UTF-8',
            'docx' => 'application/pdf',
            default => null,
        };
    }

    public function canPreviewInline(): bool
    {
        return $this->previewMimeType() !== null;
    }

    public function previousVersion() { return $this->belongsTo(self::class, 'replaces_document_id'); }
}
