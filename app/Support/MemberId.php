<?php

namespace App\Support;

use App\Models\User;

class MemberId
{
    public static function assign(User $member): void
    {
        if (filled($member->member_no)) return;

        $base = 'JCI-CAR-'.now()->format('Y').'-'.str_pad((string) $member->id, 5, '0', STR_PAD_LEFT);
        $number = $base;
        $suffix = 1;
        while (User::where('member_no', $number)->where('id', '!=', $member->id)->exists()) {
            $number = $base.'-'.$suffix++;
        }

        $member->member_no = $number;
        $member->save();
    }
}
