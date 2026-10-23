<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactInquiry extends ChapterRecord
{
    public const CATEGORIES = [
        'membership' => 'Membership inquiry',
        'partnership' => 'Partnership or sponsorship',
        'project' => 'Community project',
        'media' => 'Media or speaking request',
        'portal' => 'JCISTEM account support',
        'other' => 'Other concern',
    ];

    public const STATUSES = [
        'new' => 'New',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
        'archived' => 'Archived',
    ];

    protected $casts = [
        'consent_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
