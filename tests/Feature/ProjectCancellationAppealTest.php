<?php

namespace Tests\Feature;

use App\Models\{Project, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCancellationAppealTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_project_stays_in_owners_my_projects_with_a_reviewable_reason(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Community garden appeal', 'status' => 'Ongoing']);

        $this->actingAs($admin)->post(route('projects.transition', $project), ['action' => 'cancel', 'comments' => 'q'])->assertSessionHasErrors('comments');
        $this->assertSame('Ongoing', $project->fresh()->status);
        $this->actingAs($admin)->post(route('projects.transition', $project), ['action' => 'cancel', 'comments' => 'The site agreement fell through.'])->assertSessionHasNoErrors();

        $this->actingAs($owner)->get(route('projects', ['filter' => 'mine']))->assertOk()->assertSee($project->title)->assertSee('Cancelled');
        $this->get(route('projects'))->assertOk()->assertSee($project->title);
        $this->get(route('projects.show', $project))->assertOk()->assertSee('View reason')->assertSee('The site agreement fell through.');
        $this->assertDatabaseHas('notifications', ['user_id' => $owner->id, 'title' => $project->title]);
    }

    public function test_owner_can_appeal_once_and_admin_can_reopen_without_erasing_cancellation_history(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $outsider = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Appealed project', 'status' => 'Ongoing']);
        $this->actingAs($admin)->post(route('projects.transition', $project), ['action' => 'cancel', 'comments' => 'Venue unavailable.'])->assertSessionHasNoErrors();

        $message = 'We secured a replacement venue and can continue as planned.';
        $this->actingAs($outsider)->post(route('projects.appeal', $project), ['message' => $message])->assertForbidden();
        $this->actingAs($owner)->post(route('projects.appeal', $project), ['message' => $message])->assertRedirect(route('projects.show', $project));
        $this->assertSame('Cancelled', $project->fresh()->status);
        $this->post(route('projects.appeal', $project), ['message' => $message])->assertStatus(409);
        $this->get(route('projects.show', $project))->assertOk()->assertSee('Appeal awaiting Admin review');

        $this->actingAs($outsider)->post(route('projects.appeal.decision', $project), ['decision' => 'approve', 'comments' => 'Accept the revised plan.'])->assertForbidden();
        $this->actingAs($admin)->post(route('projects.appeal.decision', $project), ['decision' => 'approve', 'comments' => 'Replacement venue verified.'])->assertRedirect(route('projects.show', $project));
        $this->assertSame('Ongoing', $project->fresh()->status);
        $this->assertNull($project->fresh()->archived_at);
        $this->assertDatabaseHas('project_reviews', ['project_id' => $project->id, 'action' => 'cancel']);
        $this->assertDatabaseHas('project_reviews', ['project_id' => $project->id, 'action' => 'appeal_submitted']);
        $this->assertDatabaseHas('project_reviews', ['project_id' => $project->id, 'action' => 'appeal_approved']);
        $this->actingAs($owner)->get(route('projects.show', $project))->assertOk()->assertSee('Cancellation &amp; appeal history', false)->assertSee('Replacement venue verified.');
        $this->post(route('projects.appeal.decision', $project), ['decision' => 'approve', 'comments' => 'Try again now.'])->assertStatus(403);
    }

    public function test_declined_appeal_keeps_project_closed(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Closed proposal', 'status' => 'Admin Review']);
        $this->actingAs($admin)->post(route('projects.transition', $project), ['action' => 'cancel', 'comments' => 'Not viable this cycle.'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post(route('projects.appeal', $project), ['message' => 'We would like one more chance to complete the proposal.'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('projects.appeal.decision', $project), ['decision' => 'decline', 'comments' => 'No approved venue or revised schedule was supplied.'])->assertSessionHasNoErrors();

        $this->assertSame('Cancelled', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->archived_at);
        $this->actingAs($owner)->get(route('projects.show', $project))->assertOk()->assertSee('Appeal declined');
    }
}
