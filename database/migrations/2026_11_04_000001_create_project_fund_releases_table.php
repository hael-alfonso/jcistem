<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_fund_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->uuid('budget_item_id');
            $table->foreignId('chair_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('initiated_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('ledger_entry_id')->nullable()->unique()->constrained('ledger_entries')->restrictOnDelete();
            $table->string('reference', 100)->nullable()->unique();
            $table->date('release_date');
            $table->decimal('amount', 12, 2);
            $table->string('account', 150);
            $table->string('payment_method', 50);
            $table->string('transfer_reference', 150)->nullable();
            $table->text('purpose');
            $table->string('status', 30)->default('Pending');
            $table->text('chair_note')->nullable();
            $table->text('cancel_note')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'status']);
            $table->index(['chair_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_fund_releases');
    }
};
