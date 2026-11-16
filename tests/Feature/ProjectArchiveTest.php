<?php

namespace Tests\Feature;

use App\Models\{Project, ProjectReview, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_archive_only_moves_completed_projects_at_least_seven_days_old(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $due = Project::create(['created_by' => $owner->id, 'title' => 'Due for archive', 'status' => 'Completed', 'completed_at' => now()->subDays(7)]);
        $recent = Project::create(['created_by' => $owner->id, 'title' => 'Recently completed', 'status' => 'Completed', 'completed_at' => now()->subDays(6)]);
        $ongoing = Project::create(['created_by' => $owner->id, 'title' => 'Still ongoing', 'status' => 'Ongoing', 'completed_at' => now()->subDays(8)]);

        $this->artisan('projects:archive-completed')->assertSuccessful();

        $this->assertSame('Completed', $due->fresh()->status);
        $this->assertNotNull($due->fresh()->archived_at);
        $this->assertNull($recent->fresh()->archived_at);
        $this->assertNull($ongoing->fresh()->archived_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'project_auto_archived', 'object_id' => $due->id]);
        $this->artisan('projects:archive-completed')->assertSuccessful();
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_admin_can_archive_completed_project_early_then_move_it_to_deleted_folder(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Finished initiative', 'status' => 'Completed', 'completed_at' => now()]);

        $this->actingAs($owner)->post(route('projects.archive', $project))->assertForbidden();
        $this->actingAs($admin)->delete(route('projects.delete', $project), ['reason' => 'Not archived yet'])->assertForbidden();
        $this->post(route('projects.archive', $project))->assertRedirect(route('projects.archived', ['archive' => 'completed']));
        $this->assertSame('Completed', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->archived_at);
        $this->get(route('projects.completed'))->assertOk()->assertDontSee($project->title);
        $this->get(route('projects.archived', ['archive' => 'completed']))->assertOk()->assertSee($project->title);
        $this->post(route('projects.archive', $project))->assertForbidden();

        $this->delete(route('projects.delete', $project), ['reason' => 'Duplicate record'])
            ->assertRedirect(route('projects.removed'));
        $this->assertSoftDeleted($project);
        $this->post(route('projects.restore', $project->id))->assertRedirect(route('projects.archived', ['archive' => 'completed']));
        $this->assertSame('Completed', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->archived_at);
    }

    public function test_project_folder_applies_due_archive_if_background_scheduler_is_not_running(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Older finished work', 'status' => 'Completed', 'completed_at' => now()->subDays(8)]);

        $this->actingAs($owner)->get(route('projects.archived', ['archive' => 'completed']))->assertOk()->assertSee($project->title);
        $this->assertSame('Completed', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->archived_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'project_auto_archived', 'object_id' => $project->id, 'user_id' => null]);
    }

    public function test_newly_cancelled_project_can_be_archived_immediately_by_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Cancelled initiative', 'status' => 'Ongoing']);

        $this->actingAs($admin)->post(route('projects.transition', $project), [
            'action' => 'cancel', 'comments' => 'Event no longer feasible.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Cancelled', $project->fresh()->status);
        $this->assertNull($project->fresh()->archived_at);
        $this->actingAs($owner)->get(route('projects', ['filter' => 'mine']))->assertOk()->assertSee($project->title);
        $this->post(route('projects.archive', $project))->assertForbidden();
        $this->actingAs($admin)->get(route('projects.show', $project))->assertOk()->assertSee('Archive now');
        $this->post(route('projects.archive', $project))->assertRedirect(route('projects.archived', ['archive' => 'cancelled']));
        $this->assertNotNull($project->fresh()->archived_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'project_archived_by_admin', 'object_id' => $project->id]);
        $cancelledArchive = route('projects.archived', ['archive' => 'cancelled']);
        $this->get(route('projects.cancelled'))->assertRedirect($cancelledArchive);
        $this->get($cancelledArchive)->assertOk()
            ->assertSee('Project Archive')->assertSee('Completed')->assertSee('Cancelled &amp; Closed', false)
            ->assertSee($project->title);
        $this->get(route('projects.archived', ['archive' => 'completed']))->assertOk()->assertSee('0 projects');
        $this->actingAs($owner)->get(route('projects', ['filter' => 'mine']))->assertOk()->assertSee($project->title);
        $this->post(route('projects.appeal', $project), ['message' => 'A replacement venue is secured and the project can continue.'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('project_reviews', ['project_id' => $project->id, 'action' => 'appeal_submitted']);
    }

    public function test_pending_appeal_blocks_manual_and_automatic_archive_until_reviewed(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Appeal still open', 'status' => 'Ongoing']);
        $this->actingAs($admin)->post(route('projects.transition', $project),
            ['action' => 'cancel', 'comments' => 'The project venue is unavailable.'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post(route('projects.appeal', $project),
            ['message' => 'We have a new venue and request a review of this decision.'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('projects.archive', $project))->assertSessionHasErrors('project_archive');
        $this->assertNull($project->fresh()->archived_at);

        ProjectReview::where('project_id', $project->id)->where('action', 'cancel')
            ->update(['created_at' => now()->subDays(8)]);
        $this->artisan('projects:archive-completed')->assertSuccessful();
        $this->assertNull($project->fresh()->archived_at);

        $this->post(route('projects.appeal.decision', $project),
            ['decision' => 'decline', 'comments' => 'Replacement location does not meet the requirements.'])
            ->assertSessionHasNoErrors();
        $this->post(route('projects.archive', $project))->assertSessionHasNoErrors();
        $this->assertNotNull($project->fresh()->archived_at);
    }

    public function test_cancelled_project_moves_to_archive_after_seven_days_without_pending_appeal(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Older cancellation', 'status' => 'Ongoing']);
        $this->actingAs($admin)->post(route('projects.transition', $project),
            ['action' => 'cancel', 'comments' => 'The site is no longer available.'])->assertSessionHasNoErrors();
        ProjectReview::where('project_id', $project->id)->where('action', 'cancel')
            ->update(['created_at' => now()->subDays(8)]);

        $this->artisan('projects:archive-completed')->assertSuccessful();
        $this->assertSame('Cancelled', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->archived_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'project_auto_archived', 'object_id' => $project->id]);
    }
}
