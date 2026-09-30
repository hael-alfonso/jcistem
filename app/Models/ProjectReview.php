<?php
namespace App\Models;
class ProjectReview extends ChapterRecord
{
    protected $casts = ['snapshot' => 'array'];
    public function user() { return $this->belongsTo(User::class); }
}
