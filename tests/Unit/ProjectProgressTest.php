<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Support\ProjectProgress;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ProjectProgressTest extends TestCase
{
    public function test_overall_project_progress_uses_workflow_and_implementation_tasks(): void
    {
        $project = new Project([
            'status' => 'Ongoing',
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-11-10',
        ]);

        $onPace = ProjectProgress::summarize($project, 5, 10, CarbonImmutable::parse('2026-11-05'));
        $this->assertSame(68, $onPace['percent']);
        $this->assertSame(50, $onPace['elapsed']);
        $this->assertSame('On pace', $onPace['pace']);

        $behind = ProjectProgress::summarize($project, 2, 10, CarbonImmutable::parse('2026-11-08'));
        $this->assertSame(57, $behind['percent']);
        $this->assertSame(80, $behind['elapsed']);
        $this->assertSame('Behind pace', $behind['pace']);

        $finished = ProjectProgress::summarize($project, 10, 10, CarbonImmutable::parse('2026-11-08'));
        $this->assertSame(85, $finished['percent']);
        $this->assertSame('All tasks complete', $finished['pace']);
    }

    public function test_draft_or_unscheduled_projects_do_not_claim_to_be_behind(): void
    {
        $project = new Project(['status' => 'Draft Concept', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-10']);
        $draft = ProjectProgress::summarize($project, 0, 0, CarbonImmutable::parse('2026-11-11'));
        $this->assertNull($draft['elapsed']);
        $this->assertSame(5, $draft['percent']);
        $this->assertSame('Awaiting next stage', $draft['pace']);

        $project->status = 'Ongoing';
        $this->assertSame('Past target date', ProjectProgress::summarize($project, 1, 2, CarbonImmutable::parse('2026-11-11'))['pace']);
    }
}
