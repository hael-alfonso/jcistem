<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('concept_submission_method', 20)->default('online')->after('concept');
            $table->string('proposal_submission_method', 20)->nullable()->after('proposal');
        });
        Schema::table('project_documents', function (Blueprint $table) {
            $table->string('submission_stage', 20)->nullable()->after('category');
            $table->unsignedInteger('version')->default(1)->after('submission_stage');
            $table->foreignId('replaces_document_id')->nullable()->after('version')->constrained('project_documents')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('replaces_document_id');
            $table->index(['project_id', 'submission_stage']);
        });
    }

    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'submission_stage']);
            $table->dropForeign(['replaces_document_id']);
            $table->dropColumn(['submission_stage', 'version', 'replaces_document_id', 'submitted_at']);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['concept_submission_method', 'proposal_submission_method']);
        });
    }
};
