<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->string('version');
            $table->string('path'); $table->string('original_name');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('chapter_settings', function (Blueprint $table) {
            $table->id(); $table->string('key')->unique(); $table->text('value'); $table->timestamps();
        });
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id(); $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->string('type'); $table->string('path'); $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('generated_reports'); Schema::dropIfExists('chapter_settings'); Schema::dropIfExists('document_templates');
    }
};
