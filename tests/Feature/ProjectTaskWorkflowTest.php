<?php

namespace Tests\Feature;

use App\Models\{BodDecision, Notification, Project, Task, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(string $status = 'Ongoing'): array
    {
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['title' => 'Community task workflow', 'area' => 'Community Impact', 'created_by' => $chair->id, 'chair_id' => $chair->id, 'status' => $status, 'plan_locked_at' => now()]);
        BodDecision::create(['project_id' => $project->id, 'recorded_by' => $chair->id, 'meeting_date' => today(), 'decision' => 'approved', 'evidence_path' => 'signed.pdf', 'comments' => 'Board decision']);
        return [$project, $chair, $member];
    }

    private function payload(User $member, array $extra = []): array
    {
        return $extra + ['title' => 'Prepare kits', 'assignees' => [$member->id], 'priority' => 'High', 'status' => 'To Do', 'deadline' => '2026-11-10'];
    }

    public function test_task_board_only_offers_completion_when_prerequisites_and_plan_are_ready(): void
    {
        [$project, $chair, $member] = $this->workspace();
        $url = '/projects/'.$project->id.'/tasks';
        $this->actingAs($chair)->post($url, $this->payload($member))->assertSessionHasNoErrors();
        $first = $project->tasks()->firstOrFail();
        $this->post($url, $this->payload($member, ['title' => 'Distribute kits', 'dependencies' => [$first->id]]))->assertSessionHasNoErrors();
        $second = $project->tasks()->latest('id')->firstOrFail();
        $this->actingAs($member)->post($url.'/'.$second->id.'/respond', ['response' => 'accepted'])->assertSessionHasNoErrors();

        $this->get('/tasks')->assertOk()
            ->assertDontSee('aria-label="Open completion form for Distribute kits"', false);

        $this->actingAs($chair)->put($url.'/'.$first->id, $this->payload($member, ['status' => 'Completed', 'evidence' => 'Signed kit inventory']))
            ->assertSessionHasNoErrors();
        $this->actingAs($member)->get('/tasks')->assertOk()
            ->assertSee('aria-label="Open completion form for Distribute kits"', false);

        $project->update(['plan_locked_at' => null]);
        $this->get('/tasks')->assertOk()
            ->assertDontSee('aria-label="Open completion form for Distribute kits"', false);
    }

    public function test_planning_acceptance_launch_and_completion_stay_connected(): void
    {
        [$project, $chair, $member] = $this->workspace('Plan Locked');
        $url = '/projects/'.$project->id.'/tasks';
        $this->actingAs($chair)->post($url, $this->payload($member, ['starts_on' => null]))->assertSessionHasNoErrors();
        $task = $project->tasks()->firstOrFail();
        $this->put($url.'/'.$task->id, $this->payload($member, ['status' => 'In Progress']))->assertSessionHasErrors('status');
        $this->actingAs($member)->post($url.'/'.$task->id.'/respond', ['response' => 'accepted'])->assertSessionHasNoErrors();
        $this->get('/tasks')->assertOk()->assertSee('Waiting for project launch');
        $this->put($url.'/'.$task->id, ['status' => 'In Progress'])->assertForbidden();
        $this->actingAs($chair)->post('/projects/'.$project->id.'/transition', ['action' => 'start'])->assertSessionHasNoErrors();
        $this->post('/projects/'.$project->id.'/transition', ['action' => 'request_completion'])->assertSessionHasErrors('action');
        $this->actingAs($member)->put($url.'/'.$task->id, ['status' => 'In Progress'])->assertSessionHasNoErrors();
        $this->travel(1)->minutes();
        $this->put($url.'/'.$task->id, ['status' => 'Completed', 'evidence' => 'Signed kit inventory'])->assertSessionHasNoErrors();
        $this->assertSame(85, $project->fresh()->progress);
        $this->assertTrue($project->fresh()->updated_at->equalTo($task->fresh()->updated_at));
        $this->assertStringEndsWith('#task-'.$task->id, Notification::latest('id')->firstOrFail()->url);
        $this->actingAs($chair)->post('/projects/'.$project->id.'/transition', ['action' => 'request_completion'])->assertSessionHasNoErrors();
        $this->assertSame('Completion Review', $project->fresh()->status);
        $this->actingAs($member)->put($url.'/'.$task->id, ['status' => 'To Do'])->assertForbidden();
    }

    public function test_dependencies_block_start_completion_cycles_and_unsafe_reopening(): void
    {
        [$project, $chair, $member] = $this->workspace();
        $url = '/projects/'.$project->id.'/tasks';
        $this->actingAs($chair)->post($url, $this->payload($member))->assertSessionHasNoErrors();
        $first = $project->tasks()->firstOrFail();
        $this->post($url, $this->payload($member, ['title' => 'Distribute kits', 'dependencies' => [$first->id]]))->assertSessionHasNoErrors();
        $second = $project->tasks()->latest('id')->firstOrFail();
        $this->put($url.'/'.$first->id, $this->payload($member, ['dependencies' => [$second->id]]))->assertSessionHasErrors('dependencies');
        $this->actingAs($member)->post($url.'/'.$second->id.'/respond', ['response' => 'accepted'])->assertSessionHasNoErrors();
        $this->get('/tasks')->assertOk()->assertSee('Waiting for prerequisite tasks');
        foreach (['In Progress', 'Completed'] as $status) {
            $this->put($url.'/'.$second->id, ['status' => $status, 'evidence' => 'Distribution list'])->assertSessionHasErrors('status');
        }
        $this->actingAs($chair)->put($url.'/'.$first->id, $this->payload($member, ['status' => 'Completed', 'evidence' => 'Kit inventory']))->assertSessionHasNoErrors();
        $this->actingAs($member)->put($url.'/'.$second->id, ['status' => 'In Progress'])->assertSessionHasNoErrors();
        $this->actingAs($chair)->put($url.'/'.$first->id, $this->payload($member))->assertSessionHasErrors('status');
        $this->assertSame('Completed', $first->fresh()->status);
        $this->actingAs($member)->put($url.'/'.$second->id, ['status' => 'Blocked', 'notes' => 'Inventory needs correction'])->assertSessionHasNoErrors();
        $this->actingAs($chair)->put($url.'/'.$first->id, $this->payload($member))->assertSessionHasNoErrors();
    }

    public function test_dependencies_and_dates_are_validated_and_reassignment_resets_access(): void
    {
        [$project, $chair, $member] = $this->workspace();
        [$other] = $this->workspace();
        $foreign = $other->tasks()->create(['title' => 'Other project task', 'status' => 'Completed']);
        $url = '/projects/'.$project->id.'/tasks';
        $this->actingAs($chair)->post($url, $this->payload($member, ['dependencies' => [$foreign->id]]))->assertSessionHasErrors('dependencies.0');
        $this->post($url, $this->payload($member, ['starts_on' => '2026-11-11']))->assertSessionHasErrors('deadline');
        $this->post($url, $this->payload($member))->assertSessionHasNoErrors();
        $task = $project->tasks()->firstOrFail();
        $this->actingAs($member)->post($url.'/'.$task->id.'/respond', ['response' => 'accepted'])->assertSessionHasNoErrors();
        $replacement = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->actingAs($chair)->put($url.'/'.$task->id, $this->payload($replacement))->assertSessionHasNoErrors();
        $this->assertEmpty($task->fresh()->assignment_responses);
        $this->actingAs($member)->put($url.'/'.$task->id, ['status' => 'In Progress'])->assertForbidden();
        $this->actingAs($replacement)->put($url.'/'.$task->id, ['status' => 'In Progress'])->assertForbidden();
    }

    public function test_task_board_keeps_selected_project_context_and_closed_projects_do_not_offer_setup(): void
    {
        [$project, $chair, $member] = $this->workspace();
        $this->actingAs($chair)->get('/tasks?project='.$project->id.'&mine=0')->assertOk()
            ->assertSee('Open project workspace')->assertSee('data-task-dialog-open="task-create-'.$project->id.'"', false);
        $project->update(['status' => 'Completed', 'completed_at' => now()->subDays(8), 'archived_at' => now()]);
        $this->get('/tasks?mine=0')->assertOk()->assertDontSee('Task creation is not open yet');
        $this->get('/projects/'.$project->id)->assertOk()->assertDontSee('resolution_number', false)->assertSee('Open task board');
    }

    public function test_assigned_member_and_assigned_chair_can_use_the_completion_action(): void
    {
        [$project, $chair, $member] = $this->workspace();
        $url = '/projects/'.$project->id.'/tasks';
        $this->actingAs($chair)->post($url, $this->payload($member))->assertSessionHasNoErrors();
        $task = $project->tasks()->firstOrFail();
        $complete = $url.'/'.$task->id.'/complete';
        $this->actingAs($member)->post($complete, ['evidence' => 'Signed delivery record'])->assertForbidden();
        $this->post($url.'/'.$task->id.'/respond', ['response' => 'accepted'])->assertSessionHasNoErrors();
        $this->get('/projects/'.$project->id)->assertOk()->assertSee('Mark this task complete')->assertSee($complete);
        $this->post($complete, ['evidence' => '  '])->assertSessionHasErrors('evidence');
        $this->post($complete, ['evidence' => 'Signed delivery record'])->assertSessionHasNoErrors();
        $this->assertSame('Completed', $task->fresh()->status);
        $this->get('/projects/'.$project->id)->assertOk()->assertDontSee('Mark this task complete');

        $this->actingAs($chair)->post($url, $this->payload($chair, ['title' => 'Close project checklist']))->assertSessionHasNoErrors();
        $chairTask = $project->tasks()->latest('id')->firstOrFail();
        $this->post($url.'/'.$chairTask->id.'/respond', ['response' => 'accepted'])->assertSessionHasNoErrors();
        $this->post($url.'/'.$chairTask->id.'/complete', ['evidence' => 'Checklist signed'])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('Completed', $chairTask->fresh()->status);
    }

    public function test_chair_can_cancel_an_ongoing_project_and_admin_can_remove_and_restore_it(): void
    {
        [$project, $chair, $member] = $this->workspace('Ongoing');
        $this->actingAs($chair)->post('/projects/'.$project->id.'/transition', [
            'action' => 'cancel', 'comments' => 'Partner withdrew after closeout.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Cancelled', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->archived_at);
        $this->actingAs($member)->delete('/projects/'.$project->id, ['reason' => 'No access'])->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $report = \App\Models\ProjectReport::create(['project_id' => $project->id, 'created_by' => $chair->id, 'title' => 'Retained completion report', 'type' => 'Completion', 'status' => 'Reviewed', 'version' => 1, 'data' => []]);
        $this->actingAs($admin)->delete('/projects/'.$project->id, ['reason' => 'Duplicate closed record'])->assertSessionHasNoErrors();
        $this->assertNull(Project::find($project->id));
        $this->assertNotNull(Project::withTrashed()->findOrFail($project->id)->deleted_at);
        $this->get(route('projects.removed'))->assertOk()->assertSee('Community task workflow')->assertSee('Restore project');
        $this->get('/records/reports/'.$report->id)->assertOk()->assertSee('Retained completion report');
        $this->post('/projects/'.$project->id.'/restore')->assertSessionHasNoErrors();
        $this->assertSame('Cancelled', $project->fresh()->status);
    }
}
