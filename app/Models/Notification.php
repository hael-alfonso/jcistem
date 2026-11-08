<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends ChapterRecord
{
    protected $casts = ['read_at' => 'datetime', 'requires_open' => 'boolean'];

    public function isProjectCancellation(): bool
    {
        return trim((string) $this->body) === 'Project status: Cancelled';
    }

    public function hasDestination(): bool
    {
        return !$this->isProjectCancellation()
            && is_string($this->url)
            && str_starts_with($this->url, '/')
            && !str_starts_with($this->url, '//');
    }
}
