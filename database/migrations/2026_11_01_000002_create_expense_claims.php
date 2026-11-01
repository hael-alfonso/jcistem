<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->uuid('budget_item_id');
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->date('spent_on');
            $table->string('payee', 200);
            $table->string('receipt_reference', 100)->nullable();
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->string('receipt_path');
            $table->string('receipt_name');
            $table->string('status', 20)->default('Pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('ledger_entry_id')->nullable()->unique()->constrained('ledger_entries')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_claims');
    }
};
