<?php

namespace Tests\Feature;

use App\Models\{AuditLog, BodDecision, BudgetAllocation, LedgerEntry, Project, ProjectDocument, Task, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dedicated_project_collections_keep_current_work_separate_and_open_the_library_to_active_users(): void
    {
        $viewer = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $current = Project::create(['created_by' => $viewer->id, 'title' => 'Current chapter work', 'status' => 'Draft Concept']);
        $completed = Project::create(['created_by' => $owner->id, 'title' => 'Reusable completed project', 'status' => 'Completed', 'concept_submission_method' => 'upload', 'concept' => ['objective' => 'Stale online objective']]);
        $archived = Project::create(['created_by' => $owner->id, 'title' => 'Reusable archived project', 'status' => 'Completed', 'completed_at' => now()->subDays(8), 'archived_at' => now()]);
        $cancelled = Project::create(['created_by' => $viewer->id, 'title' => 'Cancelled chapter project', 'status' => 'Cancelled', 'archived_at' => now()]);

        $this->actingAs($viewer)->get(route('projects'))->assertOk()
            ->assertSee($current->title)->assertDontSee($completed->title)->assertDontSee($archived->title)->assertDontSee($cancelled->title)
            ->assertSee(route('projects.completed'), false)->assertSee(route('projects.archived'), false)
            ->assertDontSee(route('projects.cancelled'), false)
            ->assertSee('Project status')->assertDontSee('Current project views');
        $this->get(route('projects', ['filter' => 'Draft Concept']))->assertOk()->assertSee($current->title);
        $this->get(route('projects', ['filter' => 'Ongoing']))->assertOk()->assertDontSee($current->title);
        $this->get(route('projects.completed'))->assertOk()
            ->assertSee($completed->title)->assertDontSee($archived->title)->assertDontSee($current->title)
            ->assertSee('Chapter project records')->assertDontSee('?filter=completed', false)
            ->assertDontSee('Stale online objective');
        $completedArchive = route('projects.archived', ['archive' => 'completed']);
        $cancelledArchive = route('projects.archived', ['archive' => 'cancelled']);
        $archivePage = $this->get($completedArchive)->assertOk()
            ->assertSee('Project Archive')->assertSee('Completed')->assertSee('Cancelled &amp; Closed', false)
            ->assertSee($completedArchive, false)->assertSee($cancelledArchive, false)
            ->assertSee($archived->title)->assertDontSee($completed->title)->assertDontSee($cancelled->title);
        $this->assertSame(1, substr_count($archivePage->getContent(), '<strong>Archive</strong>'));

        $filteredCancelledArchive = route('projects.archived', ['archive' => 'cancelled', 'q' => 'Cancelled']);
        $this->get($filteredCancelledArchive)->assertOk()
            ->assertSee('name="archive" value="cancelled"', false)
            ->assertSee(route('projects.archived', ['q' => 'Cancelled', 'archive' => 'completed']))
            ->assertSee(route('projects.archived', ['q' => 'Cancelled', 'archive' => 'cancelled']))
            ->assertSee($cancelled->title)->assertDontSee($completed->title)->assertDontSee($archived->title);
        $this->get(route('projects.cancelled', ['q' => 'Cancelled']))
            ->assertRedirect(route('projects.archived', ['q' => 'Cancelled', 'archive' => 'cancelled']));
        $this->get(route('projects.show', $completed))->assertOk()->assertSee($completed->title);
        $this->get(route('projects.show', $archived))->assertOk()->assertSee($archived->title);
        $this->get(route('projects.removed'))->assertForbidden();

        $cancelled->delete();
        $this->actingAs($admin)->get(route('projects.removed'))->assertOk()->assertSee($cancelled->title);
    }

    public function test_reusing_a_stored_project_creates_an_independent_clean_draft_owned_by_the_user(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $viewer = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $source = Project::create([
            'created_by' => $owner->id,
            'chair_id' => $owner->id,
            'title' => 'Annual learning caravan',
            'area' => 'Individual Development',
            'status' => 'Completed',
            'starts_on' => '2026-03-01',
            'ends_on' => '2026-03-15',
            'venue' => 'Carmona City',
            'proposed_budget' => '2500.00',
            'budget_items' => [['id' => 'line-1', 'category' => 'materials_supplies', 'description' => 'Learning kits', 'quantity' => '5.00', 'unit_cost' => '500.00', 'amount' => '2500.00']],
            'concept' => ['objective' => 'Build practical skills'],
            'proposal' => ['implementation_plan' => 'Run three workshops'],
            'budget_reviewed_at' => now(),
            'budget_reviewed_by' => $owner->id,
            'plan_locked_at' => now(),
        ]);
        Task::create(['project_id' => $source->id, 'title' => 'Finished task', 'status' => 'Completed']);
        ProjectDocument::create(['project_id' => $source->id, 'uploaded_by' => $owner->id, 'title' => 'Final report', 'path' => 'projects/final.pdf']);
        BodDecision::create(['project_id' => $source->id, 'recorded_by' => $owner->id, 'meeting_date' => today(), 'decision' => 'approved', 'evidence_path' => 'decisions/signed.pdf', 'comments' => 'Approved']);
        BudgetAllocation::create(['project_id' => $source->id, 'category' => 'Supplies', 'amount' => '2500.00']);
        LedgerEntry::create(['project_id' => $source->id, 'reference' => 'SOURCE-EXPENSE', 'direction' => 'debit', 'status' => 'Posted', 'amount' => '500.00']);

        $response = $this->actingAs($viewer)->post(route('projects.reuse', $source));
        $copy = Project::where('created_by', $viewer->id)->latest('id')->firstOrFail();

        $response->assertRedirect(route('projects.edit', $copy));
        $this->assertSame('Draft Concept', $copy->status);
        $this->assertSame('Annual learning caravan (New Cycle)', $copy->title);
        $this->assertSame($source->concept, $copy->concept);
        $this->assertSame($source->budget_items, $copy->budget_items);
        $this->assertNull($copy->chair_id);
        $this->assertNull($copy->starts_on);
        $this->assertNull($copy->ends_on);
        $this->assertNull($copy->proposal);
        $this->assertNull($copy->budget_reviewed_at);
        $this->assertNull($copy->plan_locked_at);
        $this->assertSame(0, $copy->tasks()->count());
        $this->assertSame(0, $copy->documents()->count());
        $this->assertSame(0, $copy->decisions()->count());
        $this->assertSame(0, $copy->allocations()->count());
        $this->assertSame(0, $copy->transactions()->count());
        $this->assertTrue(AuditLog::where('action', 'project_reused')->where('object_id', $copy->id)->exists());

        $ongoing = Project::create(['created_by' => $viewer->id, 'title' => 'Ongoing source', 'status' => 'Ongoing']);
        $this->post(route('projects.reuse', $ongoing))->assertForbidden();
        $this->assertSame(3, Project::count());
    }
}
