<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('plan_locked_at')->nullable();
        });
        Schema::create('bod_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->date('meeting_date');
            $table->string('decision', 20);
            $table->string('resolution_number')->nullable()->unique();
            $table->string('evidence_path');
            $table->text('comments');
            $table->timestamps();
        });
        Schema::table('tasks', fn (Blueprint $table) => $table->json('assignment_responses')->nullable());
        Schema::table('project_reports', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn('reviewed_at');
        });
        Schema::table('tasks', fn (Blueprint $table) => $table->dropColumn('assignment_responses'));
        Schema::dropIfExists('bod_decisions');
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('plan_locked_at'));
    }
};
