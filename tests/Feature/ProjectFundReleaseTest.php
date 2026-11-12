<?php

namespace Tests\Feature;

use App\Models\{BodDecision, BudgetAllocation, LedgerEntry, Project, ProjectFundRelease, Task, User};
use App\Support\ProjectBudget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectFundReleaseTest extends TestCase
{
    use RefreshDatabase;

    private function workspace(): array
    {
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $items = ProjectBudget::normalize([['category' => 'materials_supplies', 'description' => 'Workshop materials', 'quantity' => 1, 'unit_cost' => 1000]]);
        $project = Project::create([
            'title' => 'Chair fund confirmation', 'area' => 'Community Impact', 'created_by' => $chair->id,
            'chair_id' => $chair->id, 'status' => 'Plan Locked', 'plan_locked_at' => now(),
            'proposed_budget' => 1000, 'budget_items' => $items,
        ]);
        BodDecision::create(['project_id' => $project->id, 'recorded_by' => $treasurer->id, 'meeting_date' => today(), 'decision' => 'approved', 'evidence_path' => 'decision.pdf', 'comments' => 'Approved']);
        BudgetAllocation::create([
            'project_id' => $project->id, 'budget_item_id' => $items[0]['id'], 'budget_item_description' => $items[0]['description'],
            'category' => 'Materials and supplies', 'amount' => 1000, 'approved_by' => 'Board', 'approved_on' => today(),
            'remarks' => 'Approved project allocation', 'recorded_by' => $treasurer->id,
        ]);
        return [$project, $chair, $treasurer, $member, $items[0]['id']];
    }

    private function releasePayload(string $budgetItemId, float $amount = 400): array
    {
        return [
            'budget_item_id' => $budgetItemId, 'release_date' => today()->format('Y-m-d'), 'amount' => $amount,
            'account' => 'Chapter operating fund', 'payment_method' => 'Cash', 'transfer_reference' => 'CV-1001',
            'purpose' => 'Purchase the approved workshop materials.',
        ];
    }

    public function test_chair_confirmation_posts_exactly_one_debit_and_reduces_available_funds(): void
    {
        [$project, $chair, $treasurer, $member, $budgetItemId] = $this->workspace();
        $this->actingAs($treasurer)->get(route('projects.show', $project))->assertOk()
            ->assertSee('id="project-allocation-dialog"', false)
            ->assertSee('id="fund-release-dialog"', false);
        $this->actingAs($treasurer)->post('/projects/'.$project->id.'/fund-releases', $this->releasePayload($budgetItemId))->assertSessionHasNoErrors();
        $release = ProjectFundRelease::firstOrFail();
        $this->assertSame('Pending', $release->status);
        $this->assertSame(0, LedgerEntry::count());
        $this->assertSame(1000.0, $project->fresh()->remaining);

        $this->post('/projects/'.$project->id.'/fund-releases', $this->releasePayload($budgetItemId, 700))->assertSessionHasErrors('amount');
        $this->actingAs($member)->post('/fund-releases/'.$release->id.'/response', ['action' => 'confirm'])->assertForbidden();

        $this->actingAs($chair)->get('/projects/'.$project->id.'#budget')->assertOk()->assertSee('Confirm money received');
        $this->post('/fund-releases/'.$release->id.'/response', ['action' => 'confirm', 'chair_note' => 'Cash counted and received in full.'])->assertSessionHasNoErrors();

        $entry = LedgerEntry::firstOrFail();
        $this->assertSame($release->reference, $entry->reference);
        $this->assertSame('Disbursement', $entry->type);
        $this->assertSame('debit', $entry->direction);
        $this->assertSame('Posted', $entry->status);
        $this->assertSame('Pending', $entry->liquidation_status);
        $this->assertSame(600.0, $project->fresh()->remaining);
        $this->assertSame($entry->id, $release->fresh()->ledger_entry_id);
        $this->assertSame('Confirmed', $release->fresh()->status);

        $this->post('/fund-releases/'.$release->id.'/response', ['action' => 'confirm'])->assertStatus(422);
        $this->assertSame(1, LedgerEntry::count());
    }

    public function test_decline_cancel_and_project_cancellation_never_create_a_ledger_entry(): void
    {
        [$project, $chair, $treasurer, $member, $budgetItemId] = $this->workspace();
        $this->actingAs($treasurer)->post('/projects/'.$project->id.'/fund-releases', $this->releasePayload($budgetItemId))->assertSessionHasNoErrors();
        $first = ProjectFundRelease::firstOrFail();
        $this->actingAs($chair)->post('/fund-releases/'.$first->id.'/response', ['action' => 'decline', 'chair_note' => 'The amount handed over was different.'])->assertSessionHasNoErrors();
        $this->assertSame('Declined', $first->fresh()->status);

        $this->actingAs($treasurer)->post('/projects/'.$project->id.'/fund-releases', $this->releasePayload($budgetItemId, 300))->assertSessionHasNoErrors();
        $second = ProjectFundRelease::latest('id')->firstOrFail();
        $this->post('/fund-releases/'.$second->id.'/cancel', ['cancel_note' => 'Payment schedule changed.'])->assertSessionHasNoErrors();
        $this->assertSame('Cancelled', $second->fresh()->status);

        $this->post('/projects/'.$project->id.'/fund-releases', $this->releasePayload($budgetItemId, 250))->assertSessionHasNoErrors();
        $third = ProjectFundRelease::latest('id')->firstOrFail();
        $this->actingAs($chair)->post('/projects/'.$project->id.'/transition', ['action' => 'cancel', 'comments' => 'Partner withdrew before implementation.'])->assertSessionHasNoErrors();
        $this->assertSame('Cancelled', $third->fresh()->status);
        $this->assertSame(0, LedgerEntry::count());
    }

    public function test_project_and_task_workspaces_use_clear_grouped_sections(): void
    {
        [$project, $chair] = $this->workspace();
        Task::create(['project_id' => $project->id, 'title' => 'Prepare materials', 'assignees' => [$chair->id], 'status' => 'To Do', 'priority' => 'High', 'deadline' => today()->addDay()]);
        $this->actingAs($chair)->get('/projects/'.$project->id)->assertOk()
            ->assertSee('Project at a glance', false)
            ->assertSee('href="#delivery"', false)
            ->assertSee('href="#files"', false)
            ->assertSee('Complete concept letter')
            ->assertSee('Project Process')
            ->assertDontSee('project-journey-details', false)
            ->assertDontSee('<details class="project-record-details"', false);
        $this->get('/tasks')->assertOk()->assertSee('Task status')->assertDontSee('More filters')->assertSee('task-list-table', false)
            ->assertSee('task-assignee-avatar', false)->assertSee('id="task-preview-dialog"', false);
    }
}
