<?php

namespace App\Models;

use App\Notifications\JciPasswordReset;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new JciPasswordReset($token));
    }

    public function memberDues()
    {
        return $this->hasMany(MemberDue::class, 'member_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'member_no',
        'status',
        'concept_reviewer',
        'proposal_reviewer',
        'profile',
        'last_login_at',
        'inactive_since',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'concept_reviewer' => 'boolean',
            'proposal_reviewer' => 'boolean',
            'profile' => 'array',
            'last_login_at' => 'datetime',
            'active_login_seen_at' => 'datetime',
            'inactive_since' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->status !== 'inactive') {
                $user->inactive_since = null;
            } elseif (!$user->inactive_since || $user->isDirty('status')) {
                $user->inactive_since = now();
            }
        });
    }
}
