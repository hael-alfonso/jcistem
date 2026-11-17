<?php

namespace Tests\Feature;

use App\Models\{BodDecision, Project, Task, User};
use App\Services\ProjectLaunchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectScheduledLaunchTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_project_launches_once_on_its_date_after_tasks_are_assigned(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $chair->id, 'chair_id' => $chair->id,
            'title' => 'Scheduled outreach', 'status' => 'Approved', 'starts_on' => today()->addDay()->toDateString(),
            'plan_locked_at' => now(), 'proposal_reviewed_at' => now(), 'proposal_reviewed_by' => $admin->id]);
        BodDecision::create(['project_id' => $project->id, 'recorded_by' => $admin->id,
            'stage' => 'proposal', 'decision' => 'approved', 'meeting_date' => today()->toDateString(),
            'evidence_path' => 'signed-board-decision.pdf', 'comments' => 'Approved at the Board meeting']);

        $this->assertSame(0, ProjectLaunchService::launchDue());
        $this->travel(2)->days();
        $this->assertSame(0, ProjectLaunchService::launchDue());
        Task::create(['project_id' => $project->id, 'title' => 'Prepare venue', 'status' => 'To Do', 'assignees' => [$chair->id]]);
        $this->artisan('pms:reminders')->assertExitCode(0);
        $this->assertSame('Ongoing', $project->fresh()->status);
        $this->assertSame(0, ProjectLaunchService::launchDue());
        $this->assertDatabaseHas('audit_logs', ['object_type' => 'Project', 'object_id' => $project->id,
            'action' => 'launch_scheduled', 'user_id' => null]);
        $this->assertDatabaseHas('notifications', ['user_id' => $chair->id, 'title' => 'Project launched: Scheduled outreach']);
        $this->travelBack();
    }
}
