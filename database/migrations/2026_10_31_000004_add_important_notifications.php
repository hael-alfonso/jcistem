<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->boolean('requires_open')->default(false)->after('read_at');
        });

        DB::table('notifications')->whereNull('read_at')->where(function ($query) {
            $query->whereIn('title', ['Report submitted', 'JCI LOI for review', 'New member registration', 'Partnership agreement for review'])
                ->orWhere('title', 'like', 'Document review:%')
                ->orWhere('body', 'like', 'Project status: Admin Review%')
                ->orWhere('body', 'like', 'Project status: Treasurer Review%')
                ->orWhere('body', 'like', 'Project status: BOD Agenda%')
                ->orWhere('body', 'like', 'Project status: Not Approved%')
                ->orWhere('body', 'like', 'Project status: Returned for Revision%')
                ->orWhere('body', 'like', 'Project status: Completion Review%');
        })->update(['requires_open' => true]);
    }

    public function down(): void
    {
        Schema::table('notifications', fn (Blueprint $table) => $table->dropColumn('requires_open'));
    }
};
