<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('proposal_submitted_revision')->nullable();
            $table->timestamp('proposal_budget_reviewed_at')->nullable();
            $table->foreignId('proposal_budget_reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('proposal_reviewed_at')->nullable();
            $table->foreignId('proposal_reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
        });
        Schema::table('bod_decisions', fn (Blueprint $table) => $table->string('stage', 20)->default('concept'));
    }

    public function down(): void
    {
        Schema::table('bod_decisions', fn (Blueprint $table) => $table->dropColumn('stage'));
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proposal_reviewed_by');
            $table->dropConstrainedForeignId('proposal_budget_reviewed_by');
            $table->dropColumn(['proposal_submitted_revision', 'proposal_budget_reviewed_at', 'proposal_reviewed_at']);
        });
    }
};
