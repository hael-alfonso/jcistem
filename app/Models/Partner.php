<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $guarded = ['id'];

    public function letters() { return $this->hasMany(Loi::class); }
    public function activities() { return $this->hasMany(PartnerActivity::class); }
    public function agreements() { return $this->hasMany(PartnershipAgreement::class); }
}
