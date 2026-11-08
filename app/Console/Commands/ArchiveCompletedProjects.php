<?php

namespace App\Console\Commands;

use App\Services\ProjectArchiveService;
use Illuminate\Console\Command;

class ArchiveCompletedProjects extends Command
{
    protected $signature = 'projects:archive-completed';
    protected $description = 'Archive projects seven days after completion without changing their status';

    public function handle(): int
    {
        $archived = ProjectArchiveService::archiveDue();
        $this->info("Archived {$archived} completed project(s).");
        return self::SUCCESS;
    }
}
