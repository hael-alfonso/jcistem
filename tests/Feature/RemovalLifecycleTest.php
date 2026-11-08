<?php

namespace Tests\Feature;

use App\Models\{LedgerEntry, Project, ProjectFundRelease, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemovalLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_remove_and_restore_closed_projects_without_losing_finance_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $completed = Project::create(['created_by' => $member->id, 'title' => 'Completed chapter project', 'status' => 'Completed']);
        $ongoing = Project::create(['created_by' => $member->id, 'title' => 'Live chapter project', 'status' => 'Ongoing']);
        $entry = LedgerEntry::create(['project_id' => $completed->id, 'reference' => 'REMOVAL-1', 'direction' => 'debit', 'status' => 'Posted', 'amount' => 120]);

        $this->actingAs($member)->delete(route('projects.delete', $completed), ['reason' => 'Member request'])->assertForbidden();
        $this->actingAs($admin)->delete(route('projects.delete', $ongoing), ['reason' => 'Still live'])->assertForbidden();
        $this->delete(route('projects.delete', $completed), ['reason' => 'Duplicate completed record'])
            ->assertRedirect(route('projects.removed'));

        $this->assertSoftDeleted($completed);
        $this->assertNotNull($entry->fresh());
        $this->get(route('projects.removed'))->assertOk()->assertSee('Completed chapter project');
        $this->post(route('projects.restore', $completed->id))->assertRedirect(route('projects.completed'));
        $this->assertNotNull($completed->fresh());
    }

    public function test_archived_and_cancelled_folder_projects_can_be_removed_and_restored(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $archived = Project::create(['created_by' => $owner->id, 'title' => 'Archived chapter project', 'status' => 'Archived']);
        $cancelled = Project::create(['created_by' => $owner->id, 'title' => 'Cancelled chapter project', 'status' => 'Cancelled']);
        $declined = Project::create(['created_by' => $owner->id, 'title' => 'Declined chapter project', 'status' => 'Declined']);
        $notApproved = Project::create(['created_by' => $owner->id, 'title' => 'Unapproved chapter project', 'status' => 'Not Approved']);

        $this->actingAs($owner)->get(route('projects.completed'))
            ->assertOk()->assertSee($archived->title)->assertDontSee('data-project-remove');
        $this->delete(route('projects.delete', $cancelled), ['reason' => 'No authority'])->assertForbidden();
        $this->actingAs($admin)->get(route('projects.completed'))
            ->assertOk()->assertSee($archived->title)->assertSee('data-project-remove')->assertSee('id="project-remove-dialog"', false);
        $this->get(route('projects.cancelled'))
            ->assertOk()->assertSee($cancelled->title)->assertSee('data-project-remove');

        foreach ([[$archived, 'projects.completed'], [$cancelled, 'projects.cancelled'], [$declined, 'projects.cancelled'], [$notApproved, 'projects.cancelled']] as [$project, $folder]) {
            $this->delete(route('projects.delete', $project), ['reason' => 'Duplicate chapter record'])
                ->assertRedirect(route('projects.removed'));
            $this->assertSoftDeleted($project);
            $this->get(route($folder))->assertOk()->assertDontSee($project->title);
            $this->get(route('projects.removed'))->assertOk()->assertSee($project->title);
            $this->assertDatabaseHas('audit_logs', [
                'action' => 'project_removed',
                'object_id' => $project->id,
                'remarks' => 'Duplicate chapter record',
            ]);

            $this->post(route('projects.restore', $project->id))->assertRedirect(route($folder));
            $this->assertNotNull($project->fresh());
            $this->get(route($folder))->assertOk()->assertSee($project->title);
        }
    }

    public function test_pending_fund_release_must_be_resolved_before_project_removal(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'status' => 'active']);
        $project = Project::create(['created_by' => $chair->id, 'chair_id' => $chair->id, 'title' => 'Cancelled with cash pending', 'status' => 'Cancelled']);
        $release = ProjectFundRelease::create([
            'project_id' => $project->id,
            'budget_item_id' => (string) \Illuminate\Support\Str::uuid(),
            'chair_id' => $chair->id,
            'initiated_by' => $treasurer->id,
            'release_date' => now()->toDateString(),
            'amount' => 100,
            'account' => 'Chapter cash',
            'payment_method' => 'Cash',
            'purpose' => 'Pending cash advance',
            'status' => 'Pending',
        ]);

        $this->actingAs($admin)->delete(route('projects.delete', $project), ['reason' => 'Close duplicate'])
            ->assertSessionHasErrors('reason');
        $this->assertNotNull($project->fresh());

        $release->update(['status' => 'Cancelled']);
        $this->delete(route('projects.delete', $project), ['reason' => 'Close duplicate'])
            ->assertRedirect(route('projects.removed'));
        $this->assertSoftDeleted($project);
        $this->assertNotNull($release->fresh());
    }

    public function test_admin_can_remove_and_restore_members_but_cannot_remove_an_active_chair(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        Project::create(['created_by' => $chair->id, 'chair_id' => $chair->id, 'title' => 'Live project', 'status' => 'Ongoing']);

        $this->actingAs($member)->delete(route('members.delete', $chair), ['reason' => 'Member request'])->assertForbidden();
        $this->actingAs($admin)->delete(route('members.delete', $chair), ['reason' => 'Try removal'])->assertSessionHasErrors('reason');
        $this->delete(route('members.delete', $member), ['reason' => 'Account closure'])->assertRedirect(route('accounts'));
        $this->assertSoftDeleted($member);
        $this->get(route('accounts', ['state' => 'archived']))->assertOk()->assertSee($member->name)->assertSee('Removed');
        $this->post(route('members.restore', $member->id))->assertRedirect(route('accounts', ['state' => 'inactive']));
        $restored = $member->fresh();
        $this->assertNotNull($restored);
        $this->assertSame('inactive', $restored->status);
        $this->assertTrue($restored->inactive_since->greaterThan(now()->subMinute()));
    }
}
