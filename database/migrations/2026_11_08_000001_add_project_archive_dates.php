<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->index(['status', 'archived_at', 'completed_at'], 'projects_archive_dates_index');
        });

        // Earlier releases stored the archive folder as a project status.
        DB::table('projects')->where('status', 'Archived')->update([
            'status' => 'Completed',
            'completed_at' => DB::raw('updated_at'),
            'archived_at' => DB::raw('updated_at'),
        ]);
        DB::table('projects')->where('status', 'Completed')->whereNull('completed_at')
            ->update(['completed_at' => DB::raw('updated_at')]);
        DB::table('projects')->whereIn('status', ['Cancelled', 'Declined', 'Not Approved'])->whereNull('archived_at')
            ->update(['archived_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_archive_dates_index');
            $table->dropColumn(['completed_at', 'archived_at']);
        });
    }
};
