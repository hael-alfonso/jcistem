<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // MySQL uses the existing composite unique index to support the project
        // foreign key. Give that foreign key its own index before dropping it.
        Schema::table('budget_allocations', fn (Blueprint $table) => $table->index('project_id', 'budget_allocations_project_id_idx'));
        Schema::table('budget_allocations', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'category']);
            $table->uuid('budget_item_id')->nullable();
            $table->string('budget_item_description')->nullable();
            $table->unique(['project_id', 'budget_item_id']);
        });
        Schema::table('ledger_entries', fn (Blueprint $table) => $table->uuid('budget_item_id')->nullable());
        DB::table('projects')->whereNotNull('budget_items')->orderBy('id')->chunkById(100, function ($projects) {
            foreach ($projects as $project) {
                $items = json_decode($project->budget_items, true);
                if (!is_array($items) || !$items) continue;
                foreach ($items as &$item) if (is_array($item) && empty($item['id'])) $item['id'] = (string) Str::uuid();
                unset($item);
                DB::table('projects')->where('id', $project->id)->update(['budget_items' => json_encode($items)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', fn (Blueprint $table) => $table->dropColumn('budget_item_id'));
        Schema::table('budget_allocations', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'budget_item_id']);
            $table->dropColumn(['budget_item_id', 'budget_item_description']);
            $table->unique(['project_id', 'category']);
        });
        Schema::table('budget_allocations', fn (Blueprint $table) => $table->dropIndex('budget_allocations_project_id_idx'));
    }
};
