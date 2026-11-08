<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('inactive_since')->nullable()->index();
        });

        // Older inactive accounts have no reliable deactivation date. Their
        // one-year retention period starts when this rule is introduced.
        DB::table('users')->where('status', 'inactive')->whereNull('deleted_at')
            ->update(['inactive_since' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('inactive_since'));
    }
};
