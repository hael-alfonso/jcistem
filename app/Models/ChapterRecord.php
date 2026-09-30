<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
abstract class ChapterRecord extends Model
{
    protected $guarded = ['id'];
    public function project() { return $this->belongsTo(Project::class); }
    public function author() { return $this->belongsTo(User::class, 'created_by'); }
}
