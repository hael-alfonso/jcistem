<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active');
            $table->boolean('concept_reviewer')->default(false);
            $table->boolean('proposal_reviewer')->default(false);
            $table->json('profile')->nullable();
            $table->timestamp('last_login_at')->nullable();
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique();
            $table->string('title')->default('Untitled concept');
            $table->string('area')->default('Community Impact');
            $table->string('status')->default('Draft Concept')->index();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('chair_id')->nullable()->constrained('users');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('venue')->nullable();
            $table->decimal('proposed_budget', 14, 2)->default(0);
            $table->json('concept')->nullable();
            $table->json('proposal')->nullable();
            $table->timestamp('budget_reviewed_at')->nullable();
            $table->foreignId('budget_reviewed_by')->nullable()->constrained('users');
            $table->unsignedInteger('revision')->default(1);
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained();
            $table->string('title')->default('Task');
            $table->text('description')->nullable();
            $table->json('assignees')->nullable();
            $table->string('priority')->default('Medium');
            $table->string('status')->default('To Do');
            $table->date('starts_on')->nullable();
            $table->date('deadline')->nullable();
            $table->string('milestone')->nullable();
            $table->json('dependencies')->nullable();
            $table->text('notes')->nullable();
            $table->text('evidence')->nullable();
        });
        Schema::table('budget_allocations', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained();
            $table->string('category')->default('General');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('approved_by')->nullable();
            $table->date('approved_on')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->unique(['project_id', 'category']);
        });
        Schema::table('member_dues', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->constrained('users');
            $table->string('period', 7)->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->unique(['member_id', 'period']);
        });
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('member_id')->nullable()->constrained('users');
            $table->foreignId('member_due_id')->nullable()->constrained('member_dues');
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->date('transaction_date')->nullable();
            $table->string('type')->default('Income');
            $table->string('direction')->default('credit');
            $table->string('category')->default('General');
            $table->string('account')->default('Chapter fund');
            $table->string('counterparty')->nullable();
            $table->string('payment_method')->default('Cash');
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status')->default('Posted');
            $table->string('liquidation_status')->default('Not required');
            $table->text('remarks')->nullable();
            $table->text('void_reason')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
        });
        Schema::table('project_documents', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->string('title')->default('Document');
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('category')->default('Supporting document');
        });
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->string('title')->default('Activity');
            $table->string('type')->default('Activity');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('venue')->nullable();
            $table->text('description')->nullable();
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('title')->default('Notification');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action')->nullable();
            $table->string('object_type')->nullable();
            $table->unsignedBigInteger('object_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('remarks')->nullable();
        });
        Schema::table('lois', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->string('title')->default('Untitled');
            $table->string('type')->default('Progress');
            $table->string('status')->default('Draft');
            $table->unsignedInteger('version')->default(1);
            $table->json('data')->nullable();
            $table->json('versions')->nullable();
            $table->text('review_comments')->nullable();
        });
        Schema::table('project_reports', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->string('title')->default('Untitled');
            $table->string('type')->default('Progress');
            $table->string('status')->default('Draft');
            $table->unsignedInteger('version')->default(1);
            $table->json('data')->nullable();
            $table->json('versions')->nullable();
            $table->text('review_comments')->nullable();
        });
        Schema::create('project_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('action');
            $table->string('from_status');
            $table->string('to_status');
            $table->text('comments')->nullable();
            $table->json('snapshot');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('project_reviews');
        Schema::table('project_reports', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['project_id', 'created_by', 'title', 'type', 'status', 'version', 'data', 'versions', 'review_comments']);
        });
        Schema::table('lois', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['project_id', 'created_by', 'title', 'type', 'status', 'version', 'data', 'versions', 'review_comments']);
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'action', 'object_type', 'object_id', 'before', 'after', 'remarks']);
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'title', 'body', 'url', 'read_at']);
        });
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['project_id', 'created_by', 'title', 'type', 'starts_on', 'ends_on', 'venue', 'description']);
        });
        Schema::table('project_documents', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn(['project_id', 'uploaded_by', 'title', 'path', 'original_name', 'category']);
        });
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['member_id']);
            $table->dropForeign(['member_due_id']);
            $table->dropForeign(['recorded_by']);
            $table->dropColumn(['reference', 'project_id', 'member_id', 'member_due_id', 'recorded_by', 'transaction_date', 'type', 'direction', 'category', 'account', 'counterparty', 'payment_method', 'description', 'amount', 'status', 'liquidation_status', 'remarks', 'void_reason', 'document_path', 'document_name']);
        });
        Schema::table('member_dues', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropForeign(['recorded_by']);
            $table->dropUnique(['member_id', 'period']);
            $table->dropColumn(['member_id', 'period', 'due_date', 'amount', 'remarks', 'recorded_by']);
        });
        Schema::table('budget_allocations', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['recorded_by']);
            $table->dropUnique(['project_id', 'category']);
            $table->dropColumn(['project_id', 'category', 'amount', 'approved_by', 'approved_on', 'remarks', 'recorded_by']);
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn(['project_id', 'title', 'description', 'assignees', 'priority', 'status', 'starts_on', 'deadline', 'milestone', 'dependencies', 'notes', 'evidence']);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['chair_id']);
            $table->dropForeign(['budget_reviewed_by']);
            $table->dropColumn(['reference', 'title', 'area', 'status', 'created_by', 'chair_id', 'starts_on', 'ends_on', 'venue', 'proposed_budget', 'concept', 'proposal', 'budget_reviewed_at', 'budget_reviewed_by', 'revision']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'concept_reviewer', 'proposal_reviewer', 'profile', 'last_login_at']);
        });
    }
};

