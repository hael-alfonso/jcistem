<?php

namespace Tests\Feature;

use App\Models\{BudgetAllocation, LedgerEntry, Project, User};
use App\Support\ChapterCharts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_greeting_uses_the_chapter_time_of_day(): void
    {
        $user = User::factory()->create(['name' => 'Jordan Cruz', 'role' => 'member', 'status' => 'active']);

        foreach (['09:00:00' => 'Good morning', '15:00:00' => 'Good afternoon', '20:00:00' => 'Good evening'] as $time => $greeting) {
            $this->travelTo(\Carbon\Carbon::parse('2026-10-03 '.$time, config('app.timezone')));
            $this->actingAs($user)->get('/dashboard')->assertOk()->assertSeeText($greeting.', Jordan.');
        }

        $this->travelBack();
    }

    public function test_charts_count_visible_projects_and_only_posted_expenses_for_approved_projects(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
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
        $this->actingAs($owner)->get(route('finance'))->assertOk()
            ->assertViewHas('charts', fn ($charts) => $charts['allocated'] === 1000.0
                && $charts['spent'] === 350.0 && $charts['rows']->count() === 1);
    }
    public function test_role_dashboards_render_only_their_essential_sections(): void
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

            $dashboard = $this->actingAs($user)->get('/dashboard')->assertOk();
            $markup = $dashboard->getContent();
            foreach (['More dashboard details', 'dashboard-more', 'Projects by focus area'] as $absent) {
                $this->assertFalse(str_contains($markup, $absent), "$role dashboard unexpectedly shows $absent");
            }
            foreach (['stat-content', '<svg class="pie-svg"'] as $present) {
                $this->assertTrue(str_contains($markup, $present), "$role dashboard is missing $present");
            }
            $dashboard->assertViewHas('taskStatuses', fn ($statuses) => $statuses->sum() === 1 && $statuses['Completed'] === 1);
            if ($role === 'treasurer') {
                foreach (['Budget utilization', 'Expenses over time', 'Projects over budget'] as $present) $this->assertTrue(str_contains($markup, $present), "$role dashboard is missing $present");
                foreach (['My task status', 'Projects by status'] as $absent) $this->assertFalse(str_contains($markup, $absent), "$role dashboard unexpectedly shows $absent");
            } elseif ($role === 'member') {
                foreach (['My next steps', 'My task status'] as $present) $this->assertTrue(str_contains($markup, $present), "$role dashboard is missing $present");
                foreach (['Budget utilization', 'Budget vs. expenses', 'Projects by status'] as $absent) $this->assertFalse(str_contains($markup, $absent), "$role dashboard unexpectedly shows $absent");
            } else {
                $this->assertTrue(str_contains($markup, 'Projects by status'), "$role dashboard is missing Projects by status");
                $this->assertFalse(str_contains($markup, 'My task status'), "$role dashboard unexpectedly shows My task status");
                if ($role === 'admin') $this->assertTrue(str_contains($markup, 'Budget vs. expenses'), 'Admin dashboard is missing Budget vs. expenses');
                else {
                    $this->assertTrue(str_contains($markup, 'My review queue'), 'BOD dashboard is missing My review queue');
                    $this->assertFalse(str_contains($markup, 'Budget vs. expenses'), 'BOD dashboard unexpectedly shows Budget vs. expenses');
                }
            }
            $projectsMarkup = $this->get('/projects')->assertOk()->getContent();
            foreach (['modern-project-card', 'Budget project '.$role] as $present) $this->assertTrue(str_contains($projectsMarkup, $present), "$role projects page is missing $present");
            $this->assertFalse(str_contains($projectsMarkup, 'Status colors'), "$role projects page unexpectedly shows Status colors");
            $tasksMarkup = $this->get('/tasks')->assertOk()->getContent();
            foreach (['Task summary', 'aria-label="View My chart task"'] as $present) $this->assertTrue(str_contains($tasksMarkup, $present), "$role tasks page is missing $present");
            $this->assertFalse(str_contains($tasksMarkup, 'tasks-card-grid'), "$role tasks page unexpectedly shows tasks-card-grid");
            $financeMarkup = $this->get('/finance')->assertOk()->getContent();
            foreach (['Budget vs. expenses', 'Expenses over time', 'Budget project '.$role, 'Budget project '.$role.' allocated budget: PHP 1,000.00', 'Budget project '.$role.' posted expenses: PHP 350.00'] as $present) {
                $this->assertTrue(str_contains($financeMarkup, $present), "$role finance page is missing $present");
            }
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
            $dashboard = $this->actingAs($user)->get('/dashboard')->assertOk()
                ->assertDontSee('More dashboard details')->assertDontSee('Projects by focus area');
            if ($role === 'treasurer') {
                $dashboard->assertSee('No project budgets yet')->assertSee('No posted expenses in this period');
            } elseif ($role === 'member') {
                $dashboard->assertSee('My next steps')->assertSee('No assigned tasks yet')
                    ->assertSee("You're all caught up.", false)->assertDontSee('Budget vs. expenses');
            } else {
                $dashboard->assertSee('No project data yet')->assertSee('No projects awaiting review');
                if ($role === 'admin') $dashboard->assertSee('No project budgets yet');
                else $dashboard->assertDontSee('Budget vs. expenses');
            }
            $this->get('/projects')->assertOk()->assertSee('No projects yet');
            $this->get('/tasks')->assertOk()->assertSee($role === 'member' ? 'No tasks assigned to you' : 'No chapter tasks yet')->assertDontSee('tasks-card-grid');
            $this->get('/finance')->assertOk()->assertSee('Budget vs. expenses')
                ->assertSee('No project budgets yet')->assertSee('Expenses over time');
        }
    }
    public function test_board_dashboard_shows_the_official_agenda_without_review_permissions(): void
    {
        $board = User::factory()->create(['role' => 'bod', 'status' => 'active']);
        $concept = Project::create(['created_by' => $board->id, 'title' => 'Admin queue item', 'area' => 'Community Impact', 'status' => 'Admin Review']);
        $agenda = Project::create(['created_by' => $board->id, 'title' => 'Board agenda item', 'area' => 'Community Impact', 'status' => 'BOD Agenda']);
        $this->actingAs($board)->get('/dashboard')->assertOk()
            ->assertViewHas('pending', fn ($items) => $items->pluck('id')->all() === [$agenda->id]);
        $this->post('/projects/'.$concept->id.'/transition', ['action' => 'endorse', 'comments' => 'Not authorized'])->assertForbidden();
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

    public function test_finance_displays_exact_cents_and_does_not_report_zero_utilization_without_an_allocation(): void
    {
        $user = User::factory()->create(['role' => 'treasurer', 'status' => 'active']);
        $project = Project::create([
            'created_by' => $user->id, 'title' => 'Unallocated project',
            'area' => 'Community Impact', 'status' => 'Approved',
        ]);
        LedgerEntry::create([
            'reference' => 'EXACT-CENTS', 'project_id' => $project->id,
            'direction' => 'debit', 'status' => 'Posted', 'amount' => 123.45,
            'transaction_date' => today(),
        ]);

        $this->actingAs($user)->get('/finance')->assertOk()
            ->assertSee('Over budget by PHP 123.45')
            ->assertSee('No allocation recorded')->assertSee('No allocation')
            ->assertSee('PHP -123.45')->assertSee('PHP 123.45')
            ->assertSee(today()->format('M Y').': PHP 123.45')
            ->assertDontSee('0.0%')->assertDontSee('PHP 123.00');
        $this->get('/dashboard')->assertOk()
            ->assertSee('PHP -123.45')->assertSee('PHP 123.45')
            ->assertSee('stat-expenses')->assertSee('stat-icon');
    }
}
