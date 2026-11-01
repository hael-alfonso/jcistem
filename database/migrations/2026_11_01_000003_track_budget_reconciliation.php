<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('budget_allocations', function (Blueprint $table) {
            $table->decimal('legacy_original_amount', 12, 2)->nullable();
            $table->unsignedBigInteger('legacy_source_id')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reconciled_by');
            $table->dropColumn(['legacy_original_amount', 'legacy_source_id', 'reconciled_at']);
        });
    }
};
