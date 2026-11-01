<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('contact_name')->nullable();
            $table->string('contact_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 60)->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('Prospective');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::table('lois', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::create('partner_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->date('occurred_on');
            $table->string('type', 50);
            $table->string('title');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_activities');
        Schema::table('lois', fn (Blueprint $table) => $table->dropConstrainedForeignId('partner_id'));
        Schema::dropIfExists('partners');
    }
};
