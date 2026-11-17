<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('proposal_admin_reviewed_at')->nullable();
            $table->foreignId('proposal_admin_reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proposal_admin_reviewed_by');
            $table->dropColumn('proposal_admin_reviewed_at');
        });
    }
};
