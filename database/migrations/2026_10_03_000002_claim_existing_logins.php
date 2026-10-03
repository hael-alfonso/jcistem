<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cutoff = now()->subMinutes(config('session.lifetime'))->timestamp;
        $claimedUsers = [];
        $sessions = DB::table('sessions')->whereNotNull('user_id')
            ->where('last_activity', '>=', $cutoff)
            ->orderByDesc('last_activity')->get(['id', 'user_id', 'last_activity']);

        foreach ($sessions as $session) {
            if (isset($claimedUsers[$session->user_id])) continue;
            $claimedUsers[$session->user_id] = true;
            DB::table('users')->where('id', $session->user_id)
                ->whereNull('active_login_token')
                ->update([
                    'active_login_token' => hash('sha256', $session->id),
                    'active_login_seen_at' => Carbon::createFromTimestamp($session->last_activity, config('app.timezone')),
                ]);
        }
    }

    public function down(): void
    {
        // A rollback must not clear login claims created after this migration ran.
    }
};
