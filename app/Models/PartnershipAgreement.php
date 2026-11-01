<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnershipAgreement extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['terms' => 'array', 'approved_at' => 'datetime', 'signed_on' => 'date'];

    public function partner() { return $this->belongsTo(Partner::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function author() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
