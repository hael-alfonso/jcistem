<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique();
        });

        DB::table('users')->select('id', 'name')->orderBy('id')->get()->each(function ($user): void {
            $base = Str::slug($user->name);
            $username = trim(substr($base, 0, 38), '-');
            if ($username === '') $username = 'member';
            DB::table('users')->where('id', $user->id)->update(['username' => $username.'-'.$user->id]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
