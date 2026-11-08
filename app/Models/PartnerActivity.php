<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerActivity extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['occurred_on' => 'date'];

    public function partner() { return $this->belongsTo(Partner::class); }
    public function project() { return $this->belongsTo(Project::class)->withTrashed(); }
    public function author() { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
}
