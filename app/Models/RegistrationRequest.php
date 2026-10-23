<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationRequest extends Model
{
    protected $fillable = [
        'reference', 'name', 'username', 'email', 'phone', 'reason', 'password', 'consent_at',
        'status', 'reviewed_by', 'reviewed_at', 'review_notes', 'user_id',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'consent_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
