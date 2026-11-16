<?php

namespace App\Console\Commands;

use App\Services\ProjectArchiveService;
use Illuminate\Console\Command;

class ArchiveCompletedProjects extends Command
{
    protected $signature = 'projects:archive-completed';
    protected $description = 'Archive completed or cancelled projects after seven days when no appeal is pending';

    public function handle(): int
    {
        $archived = ProjectArchiveService::archiveDue();
        $this->info("Archived {$archived} closed project(s).");
        return self::SUCCESS;
    }
}
