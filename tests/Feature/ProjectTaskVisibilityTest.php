<?php

namespace Tests\Feature;

use App\Models\{Project, Task, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTaskVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_and_task_pages_show_complete_information_and_link_to_the_exact_task(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-03 12:00:00', config('app.timezone')));
        $member = User::factory()->create(['name' => 'Jordan Cruz', 'role' => 'member', 'status' => 'active']);
        $objective = 'Support the neighborhood through a coordinated volunteer program with clear roles and a final community report.';
        $description = 'Prepare the full volunteer roster, confirm every assignment, and publish the final schedule for the community event.';
        $project = Project::create([
            'created_by' => $member->id,
            'chair_id' => $member->id,
            'title' => 'Community volunteer program',
            'area' => 'Community Impact',
            'status' => 'Plan Locked', 'plan_locked_at' => now(),
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-20',
            'concept' => ['objective' => $objective],
        ]);
        \App\Models\BodDecision::create(['project_id' => $project->id, 'recorded_by' => $member->id, 'meeting_date' => '2026-10-01', 'decision' => 'approved', 'resolution_number' => 'VISIBLE-1', 'evidence_path' => 'evidence.pdf', 'comments' => 'Approved fixture']);
        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Prepare volunteer roster',
            'description' => $description,
            'assignees' => [$member->id],
            'status' => 'In Progress',
            'priority' => 'Urgent',
            'deadline' => '2026-10-02',
            'milestone' => 'Roster confirmed',
            'notes' => 'Local coordinators have reviewed the first draft.',
            'evidence' => 'Roster review document is on file.',
        ]);

        $this->actingAs($member)->get('/projects')->assertOk()
            ->assertSee($objective)->assertSee('Created by')->assertSee('Project chair')
            ->assertSee('Oct 20, 2026')->assertSee('0 of 1')
            ->assertSee('value="Completion Review"', false);

        $this->get('/tasks')->assertOk()
            ->assertSee('Prepare volunteer roster')->assertSee('data-label="Assigned to"', false)
            ->assertSee('data-label="Deadline"', false)->assertSee('priority-urgent')
            ->assertSee('Overdue')->assertSee('href="'.route('projects.show', $project).'#task-'.$task->id.'"', false)
            ->assertSee('id="task-preview-dialog"', false)
            ->assertSee('id="task-create-'.$project->id.'"', false)
            ->assertSee('data-task-preview-title', false);

        $this->get('/projects/'.$project->id)->assertOk()
            ->assertSee('id="task-'.$task->id.'"', false)
            ->assertSee($description)->assertSee('Jordan Cruz')
            ->assertSee('Local coordinators have reviewed the first draft.')
            ->assertSee('Roster review document is on file.')
            ->assertSee('Update task')
            ->assertSee('id="task-detail-'.$task->id.'"', false)
            ->assertSee('data-task-dialog-open="task-create-'.$project->id.'"', false);

        $this->travelBack();
    }
}
