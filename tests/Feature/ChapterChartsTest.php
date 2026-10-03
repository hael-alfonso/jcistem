<?php

namespace Tests\Feature;

use App\Models\{BudgetAllocation, LedgerEntry, Project, User};
use App\Support\ChapterCharts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_charts_count_visible_projects_and_only_posted_expenses_for_approved_projects(): void
    {
        $owner = User::factory()->create();
        $approved = Project::create([
            'created_by' => $owner->id,
            'title' => 'Approved project',
            'area' => 'Community Impact',
            'status' => 'Approved',
        ]);
        $draft = Project::create([
            'created_by' => $owner->id,
            'title' => 'Draft project',
            'area' => 'Community Impact',
            'status' => 'Draft Concept',
        ]);

        BudgetAllocation::create(['project_id' => $approved->id, 'category' => 'Supplies', 'amount' => 1000]);
        BudgetAllocation::create(['project_id' => $draft->id, 'category' => 'Supplies', 'amount' => 9000]);

        $previousMonth = today()->startOfMonth()->subMonth()->addDays(4)->toDateString();
        LedgerEntry::create(['reference' => 'CHART-1', 'project_id' => $approved->id, 'direction' => 'debit', 'status' => 'Posted', 'amount' => 250, 'transaction_date' => today()]);
        LedgerEntry::create(['reference' => 'CHART-2', 'project_id' => $approved->id, 'direction' => 'debit', 'status' => 'Posted', 'amount' => 100, 'transaction_date' => $previousMonth]);
        LedgerEntry::create(['reference' => 'CHART-3', 'project_id' => $approved->id, 'direction' => 'debit', 'status' => 'Void', 'amount' => 400, 'transaction_date' => today()]);
        LedgerEntry::create(['reference' => 'CHART-4', 'project_id' => $approved->id, 'direction' => 'credit', 'status' => 'Posted', 'amount' => 600, 'transaction_date' => today()]);
        LedgerEntry::create(['reference' => 'CHART-5', 'project_id' => $draft->id, 'direction' => 'debit', 'status' => 'Posted', 'amount' => 500, 'transaction_date' => today()]);

        $mix = ChapterCharts::projectMix(collect([$approved, $draft]));
        $this->assertSame(2, $mix['total']);
        $this->assertSame(1, $mix['statuses']['Approved']);
        $this->assertSame(1, $mix['statuses']['Draft Concept']);
        $this->assertSame(2, $mix['areas']['Community Impact']);

        $finance = ChapterCharts::finances(Project::whereIn('status', Project::APPROVED)->get());
        $this->assertSame(1000.0, $finance['allocated']);
        $this->assertSame(350.0, $finance['spent']);
        $this->assertSame(650.0, $finance['rows']->first()['remaining']);
        $this->assertSame(35.0, $finance['rows']->first()['utilization']);
        $this->assertCount(6, $finance['months']);
        $this->assertSame(250.0, $finance['months']->last()['amount']);
        $this->assertSame(100.0, $finance['months']->get(4)['amount']);
    }
    public function test_role_dashboards_render_with_compact_financial_and_task_details(): void
    {
        foreach (['admin', 'bod', 'treasurer', 'member'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $project = Project::create([
                'created_by' => $user->id, 'title' => 'Budget project '.$role,
                'area' => 'Community Impact', 'status' => 'Approved',
            ]);
            \App\Models\Task::create(['project_id' => $project->id, 'title' => 'My chart task', 'assignees' => [$user->id], 'status' => 'Completed']);
            BudgetAllocation::create(['project_id' => $project->id, 'category' => 'Supplies', 'amount' => 1000]);
            LedgerEntry::create([
                'reference' => 'BUDGET-'.$role, 'project_id' => $project->id,
                'direction' => 'debit', 'status' => 'Posted', 'amount' => 350, 'transaction_date' => today(),
            ]);

            $this->actingAs($user)->get('/dashboard')->assertOk()
                ->assertSee('Budget vs. expenses')->assertSee('More dashboard details')
                ->assertSee('Allocated budget')
                ->assertSee('My next steps')
                ->assertSee('Projects by status')->assertSee('Projects by focus area')
                ->assertDontSee('<h2>Project progress</h2>', false)->assertSee('My task status')
                ->assertDontSee('My dues payments')->assertDontSee('Member dues collection')
                ->assertViewHas('taskStatuses', fn ($statuses) => $statuses->sum() === 1 && $statuses['Completed'] === 1);

            $this->get('/finance')->assertOk()->assertSee('Budget vs. expenses')
                ->assertSee('Expenses over time')->assertSee('Budget project '.$role)
                ->assertSee('Budget project '.$role.' allocated budget: PHP 1,000.00')
                ->assertSee('Budget project '.$role.' posted expenses: PHP 350.00');
        }
    }

    public function test_member_financial_charts_exclude_other_users_private_drafts(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $owner = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $approved = Project::create(['created_by' => $owner->id, 'title' => 'Shared approved project', 'area' => 'Community Impact', 'status' => 'Approved']);
        $private = Project::create(['created_by' => $owner->id, 'title' => 'Private draft project', 'area' => 'Community Impact', 'status' => 'Draft Concept']);
        BudgetAllocation::create(['project_id' => $approved->id, 'category' => 'Supplies', 'amount' => 1000]);
        BudgetAllocation::create(['project_id' => $private->id, 'category' => 'Supplies', 'amount' => 9000]);

        $this->actingAs($member)->get('/dashboard')->assertOk()
            ->assertDontSee('Private draft project')
            ->assertViewHas('charts', fn ($charts) => $charts['total'] === 0)
            ->assertViewHas('financeCharts', fn ($charts) => $charts['allocated'] === 1000.0);
        $this->get('/finance')->assertOk()->assertSee('Shared approved project')->assertDontSee('Private draft project')
            ->assertViewHas('charts', fn ($charts) => $charts['allocated'] === 1000.0);
    }

    public function test_financial_comparison_shows_overspending_without_counting_voids_or_credits(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $user->id, 'title' => 'Over budget project', 'area' => 'Community Impact', 'status' => 'Approved']);
        BudgetAllocation::create(['project_id' => $project->id, 'category' => 'Supplies', 'amount' => 100]);
        foreach ([['Posted', 'debit', 150], ['Void', 'debit', 500], ['Posted', 'credit', 600]] as $index => [$status, $direction, $amount]) {
            LedgerEntry::create(['reference' => 'COMPARE-'.$index, 'project_id' => $project->id, 'direction' => $direction, 'status' => $status, 'amount' => $amount, 'transaction_date' => today()]);
        }
        $this->actingAs($user)->get('/finance')->assertOk()
            ->assertSee('Over budget by PHP 50.00')
            ->assertSee('Over budget project posted expenses: PHP 150.00')
            ->assertViewHas('charts', fn ($charts) => $charts['maxProjectAmount'] === 150.0 && $charts['spent'] === 150.0);
        $this->get('/dashboard')->assertOk()->assertSee('Over budget by PHP 50.00');
    }

    public function test_financial_sections_render_without_records_for_every_role(): void
    {
        foreach (['admin', 'bod', 'treasurer', 'member'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $this->actingAs($user)->get('/dashboard')->assertOk()
                ->assertSee('Budget vs. expenses')->assertSee('More dashboard details')
                ->assertSee('No project budgets yet')->assertSee('My next steps')
                ->assertSee('No project data yet')->assertSee('No assigned tasks yet');
            $this->get('/finance')->assertOk()->assertSee('Budget vs. expenses')
                ->assertSee('No project budgets yet')->assertSee('Expenses over time');
        }
    }
    public function test_board_review_queue_matches_reviewer_permissions(): void
    {
        $board = User::factory()->create(['role' => 'bod', 'status' => 'active', 'concept_reviewer' => true, 'proposal_reviewer' => false]);
        $concept = Project::create(['created_by' => $board->id, 'title' => 'Concept queue item', 'area' => 'Community Impact', 'status' => 'Submitted for President Review']);
        $proposal = Project::create(['created_by' => $board->id, 'title' => 'Proposal queue item', 'area' => 'Community Impact', 'status' => 'Submitted for Formal Approval']);
        $this->actingAs($board)->get('/dashboard')->assertOk()
            ->assertViewHas('pending', fn ($items) => $items->pluck('id')->all() === [$concept->id]);
        $board->update(['concept_reviewer' => false, 'proposal_reviewer' => true]);
        $this->get('/dashboard')->assertOk()
            ->assertViewHas('pending', fn ($items) => $items->pluck('id')->all() === [$proposal->id]);
        $board->update(['proposal_reviewer' => false]);
        $this->get('/dashboard')->assertOk()->assertViewHas('pending', fn ($items) => $items->isEmpty());
    }

    public function test_member_summary_counts_only_personal_projects_and_tasks(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $other = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $own = Project::create(['created_by' => $member->id, 'title' => 'Own project', 'area' => 'Community Impact', 'status' => 'Draft Concept']);
        $assigned = Project::create(['created_by' => $other->id, 'title' => 'Assigned project', 'area' => 'Community Impact', 'status' => 'Approved']);
        Project::create(['created_by' => $other->id, 'title' => 'Unassigned chapter project', 'area' => 'Community Impact', 'status' => 'Approved']);
        \App\Models\Task::create(['project_id' => $assigned->id, 'title' => 'Assigned task', 'assignees' => [$member->id], 'status' => 'To Do']);
        $due = \App\Models\MemberDue::create(['member_id' => $member->id, 'period' => '2026-10', 'amount' => 1000]);
        \App\Models\MemberDue::create(['member_id' => $other->id, 'period' => '2026-10', 'amount' => 9000]);
        foreach ([['Posted', 250], ['Void', 500]] as $index => [$status, $amount]) {
            LedgerEntry::create(['reference' => 'DUES-SUMMARY-'.$index, 'member_due_id' => $due->id, 'member_id' => $member->id, 'direction' => 'credit', 'status' => $status, 'amount' => $amount, 'transaction_date' => today()]);
        }
        $this->actingAs($member)->get('/dashboard')->assertOk()->assertSee('My dues balance')->assertSee('PHP 750.00')
            ->assertViewHas('myDuesBalance', fn ($balance) => $balance == 750)
            ->assertViewHas('dashboardProjects', fn ($items) => $items->count() === 2 && $items->contains('id', $own->id) && $items->contains('id', $assigned->id))
            ->assertViewHas('tasks', fn ($items) => $items->count() === 1)
            ->assertViewHas('pending', fn ($items) => $items->isEmpty());
    }
}
