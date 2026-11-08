<?php

namespace App\Console\Commands;

use App\Models\{Project, User};
use App\Services\ChapterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveInactiveMembers extends Command
{
    protected $signature = 'members:remove-inactive';
    protected $description = 'Move general-member accounts inactive for a full year to Removed Accounts';

    public function handle(): int
    {
        $cutoff = now('Asia/Manila')->subYear();
        $removed = 0;

        User::where('role', 'member')->where('status', 'inactive')
            ->whereNotNull('inactive_since')->where('inactive_since', '<=', $cutoff)
            ->chunkById(100, function ($members) use ($cutoff, &$removed): void {
                foreach ($members as $candidate) {
                    DB::transaction(function () use ($candidate, $cutoff, &$removed): void {
                        $member = User::lockForUpdate()->find($candidate->id);
                        if (!$member || $member->role !== 'member' || $member->status !== 'inactive'
                            || !$member->inactive_since || $member->inactive_since->greaterThan($cutoff)) return;

                        // A chair assignment must be resolved before the account leaves the directory.
                        if (Project::where('chair_id', $member->id)
                            ->whereIn('status', ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review'])->exists()) return;

                        $before = $member->toArray();
                        $member->active_login_token = null;
                        $member->active_login_seen_at = null;
                        $member->save();
                        $member->delete();
                        ChapterService::audit('member_account_auto_removed', $member, $before, 'Inactive for one year. Account can be restored by an administrator.');
                        $removed++;
                    });
                }
            });

        $this->info("Moved {$removed} inactive general-member account(s) to Removed Accounts.");
        return self::SUCCESS;
    }
}
