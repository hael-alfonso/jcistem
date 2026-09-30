<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAllocation extends ChapterRecord
{
    protected $casts = ['amount' => 'decimal:2', 'approved_on' => 'date'];
}
