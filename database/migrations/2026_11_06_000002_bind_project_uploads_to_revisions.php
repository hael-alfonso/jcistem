<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $table->unsignedInteger('project_revision')->nullable()->after('version');
        });

        // The newest existing upload is the best available record of its
        // project's current revision. Earlier versions stay historical.
        DB::table('projects')->select('id', 'revision')->orderBy('id')->chunkById(100, function ($projects): void {
            foreach ($projects as $project) {
                foreach (['concept', 'proposal'] as $stage) {
                    $latest = DB::table('project_documents')->where('project_id', $project->id)
                        ->where('submission_stage', $stage)->orderByDesc('version')->orderByDesc('id')->value('id');
                    if ($latest) DB::table('project_documents')->where('id', $latest)
                        ->update(['project_revision' => max(1, (int) $project->revision)]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_documents', fn (Blueprint $table) => $table->dropColumn('project_revision'));
    }
};
