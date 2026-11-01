<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partnership_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('title');
            $table->string('status', 30)->default('Draft');
            $table->json('terms');
            $table->text('review_comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('signed_path')->nullable();
            $table->string('signed_name')->nullable();
            $table->date('signed_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('partnership_agreements'); }
};
