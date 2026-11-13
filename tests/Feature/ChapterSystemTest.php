<?php
namespace Tests\Feature;

use App\Models\{User, Project, Task, Loi, ProjectReport, LedgerEntry, MemberDue, AuditLog, CalendarEvent};
use App\Support\ChapterForms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChapterSystemTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'member', array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'active', 'concept_reviewer' => false, 'proposal_reviewer' => false], $extra));
    }

    private function project(User $owner, array $extra = []): Project
    {
        $project = Project::create(array_merge(['created_by' => $owner->id, 'title' => 'Community literacy project', 'area' => 'Community Impact', 'status' => 'Draft Concept', 'starts_on' => '2026-11-15', 'ends_on' => '2026-11-16', 'venue' => 'Carmona', 'proposed_budget' => 10000, 'concept' => array_merge(array_fill_keys(array_keys(ChapterForms::CONCEPT), 'Community literacy support'), ['sdg_number' => '4']), 'proposal' => array_fill_keys(array_keys(ChapterForms::PROPOSAL), 'Complete project plan')], $extra));
        if ($project->chair_id && in_array($project->status, Project::APPROVED)) {
            $project->update(['plan_locked_at' => now()]);
            \App\Models\BodDecision::create(['project_id' => $project->id, 'recorded_by' => $owner->id, 'stage' => 'proposal', 'meeting_date' => '2026-10-01', 'decision' => 'approved', 'resolution_number' => 'TEST-'.$project->id, 'evidence_path' => 'resolution.pdf', 'comments' => 'Approved fixture']);
        }
        return $project;
    }

    public function test_navigation_exposes_monitoring_and_role_appropriate_tools(): void
    {
        $member = $this->user();
        $project = $this->project($member, ['status' => 'Ongoing', 'chair_id' => $member->id]);
        $dashboard = $this->actingAs($member)->get('/dashboard')->assertOk()
            ->assertSee('All Projects')->assertSee('Project Finances')->assertSee('My Profile')
            ->assertDontSee('Treasurer Ledger');
        $markup = $dashboard->getContent();
        $this->assertGreaterThan(strpos($markup, '</nav>'), strpos($markup, 'class="sidebar-account"'));
        $this->assertLessThan(strpos($markup, '</aside>'), strpos($markup, 'class="sidebar-account"'));
        $this->get('/projects')->assertOk()->assertSee('Quick view')->assertSee('project-preview-dialog');
        $this->get('/finance')->assertOk()->assertSee('Project Finances');

        $treasurer = $this->user('treasurer');
        $this->actingAs($treasurer)->get('/dashboard')->assertOk()
            ->assertSee('Treasurer Ledger')->assertSee('Liquidation Report Review');
        $this->actingAs($treasurer)->get('/projects/'.$project->id.'/liquidation')->assertOk();

        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Registration Requests');
    }

    public function test_all_roles_can_render_their_workspaces_and_authorized_forms(): void
    {
        foreach (['admin', 'bod', 'treasurer', 'member'] as $role) {
            $user = $this->user($role);
            $project = $this->project($user, ['status' => 'Ongoing', 'chair_id' => $user->id]);
            $this->actingAs($user);
            foreach (['dashboard', 'projects', 'projects/create', 'projects/'.$project->id, 'tasks', 'calendar', 'finance', 'dues', 'members', 'account', 'notifications', 'our-chapter', 'records/letters', 'records/reports', 'records/letters/create?project_id='.$project->id, 'records/reports/create?project_id='.$project->id] as $path) {
                $this->get('/'.$path)->assertOk();
            }
            if ($role === 'admin') foreach (['audit', 'members/create', 'members/'.$user->id.'/edit'] as $path) $this->get('/'.$path)->assertOk();
            if ($role === 'treasurer') foreach (['ledger', 'ledger?filter=liquidation', 'dues/manage', 'records/reports/create', 'ledger/export'] as $path) $this->get('/'.$path)->assertOk();
        }
    }

    public function test_calendar_shows_overlapping_events_without_loading_other_months(): void
    {
        $member = $this->user();
        $project = $this->project($member, ['status' => 'Ongoing']);
        CalendarEvent::create(['project_id' => $project->id, 'created_by' => $member->id,
            'title' => 'Community workshop', 'type' => 'Activity', 'starts_on' => '2026-10-31', 'ends_on' => '2026-11-02']);
        CalendarEvent::create(['project_id' => $project->id, 'created_by' => $member->id,
            'title' => 'December workshop', 'type' => 'Activity', 'starts_on' => '2026-12-10']);

        $this->actingAs($member)->get(route('calendar', ['month' => '2026-11']))->assertOk()
            ->assertSee('Community workshop')->assertSee('Community literacy project')
            ->assertDontSee('December workshop');
    }

    public function test_project_report_links_match_liquidation_review_access(): void
    {
        $chair = $this->user();
        $admin = $this->user('admin');
        $treasurer = $this->user('treasurer');
        $project = $this->project($chair, ['status' => 'Ongoing', 'chair_id' => $chair->id]);
        $report = ProjectReport::create([
            'project_id' => $project->id, 'created_by' => $chair->id,
            'title' => 'Project liquidation for review', 'type' => 'Liquidation',
            'status' => 'Submitted', 'data' => [],
        ]);
        $link = 'href="'.route('records.show', ['reports', $report->id]).'"';

        $this->actingAs($treasurer)->get(route('projects.show', $project))->assertOk()->assertSee($link, false);
        $this->get(route('records.show', ['reports', $report->id]))->assertOk();
        $this->actingAs($admin)->get(route('projects.show', $project))->assertOk()->assertDontSee($link, false);
        $this->get(route('records.show', ['reports', $report->id]))->assertOk()->assertDontSee('Approve Liquidation');
        $this->post(route('records.transition', ['reports', $report->id]), ['action' => 'approve', 'comments' => 'Treasurer-only decision'])->assertForbidden();
    }

    public function test_complete_concept_to_archive_flow_enforces_approvals_and_financial_boundaries(): void
    {
        Storage::fake('local');
        $member = $this->user();
        $assignee = $this->user();
        $admin = $this->user('admin', ['concept_reviewer' => true]);
        $bod = $this->user('bod', ['proposal_reviewer' => true]);
        $treasurer = $this->user('treasurer');
        $this->actingAs($member)->post('/projects', [
            'title' => 'Community literacy project', 'area' => 'Community Impact', 'starts_on' => '2026-11-15',
            'ends_on' => '2026-11-16', 'venue' => 'Carmona', 'proposed_budget' => 10000,
            'concept' => array_merge(array_fill_keys(array_keys(ChapterForms::CONCEPT), 'Community literacy support'), ['sdg_number' => '4']),
        ])->assertRedirect();
        $p = Project::firstOrFail();
        $transition = '/projects/'.$p->id.'/transition';
        $this->post($transition, ['action' => 'lock_plan'])->assertForbidden();
        $this->post($transition, ['action' => 'submit_concept'])->assertSessionHasNoErrors();
        $this->post($transition, ['action' => 'endorse', 'comments' => 'Approve'])->assertForbidden();
        $this->actingAs($admin)->post($transition, ['action' => 'endorse', 'comments' => 'Develop measurable reading outcomes.'])->assertSessionHasNoErrors();
        $this->assertSame('Treasurer Review', $p->fresh()->status);
        $this->actingAs($treasurer)->post($transition, ['action' => 'budget_review', 'comments' => 'Budget is feasible.'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($transition, ['action' => 'approve', 'comments' => 'Approved by the board.', 'meeting_date' => '2026-10-01', 'resolution_number' => 'BOD-TEST-1', 'evidence' => UploadedFile::fake()->create('resolution.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->actingAs($member)->put('/projects/'.$p->id, ['title' => $p->title, 'area' => $p->area, 'starts_on' => '2026-11-15', 'ends_on' => '2026-11-16', 'venue' => 'Carmona', 'proposed_budget' => 10000, 'proposal' => array_fill_keys(array_keys(ChapterForms::PROPOSAL), 'Complete project plan')])->assertSessionHasNoErrors();
        $p->update(['budget_items' => \App\Support\ProjectBudget::normalize([['category' => 'materials_supplies', 'description' => 'Learning materials', 'quantity' => 1, 'unit_cost' => 10000]])]);
        $budgetItemId = $p->fresh()->budget_items[0]['id'];
        $this->post($transition, ['action' => 'submit_full_proposal'])->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($transition, ['action' => 'review_full_budget', 'comments' => 'Full proposal budget verified'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($transition, ['action' => 'approve_full_proposal', 'comments' => 'Full proposal approved', 'meeting_date' => '2026-10-02', 'resolution_number' => 'BOD-TEST-2', 'evidence' => UploadedFile::fake()->create('full-proposal-approval.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->actingAs($member);
        $this->post($transition, ['action' => 'lock_plan'])->assertSessionHasNoErrors();
        $budget = ['budget_item_id' => $budgetItemId, 'amount' => 10000, 'approved_by' => 'Board', 'approved_on' => '2026-10-01', 'remarks' => 'Approved materials budget'];
        $this->actingAs($member)->post('/projects/'.$p->id.'/budget', $budget)->assertForbidden();
        $this->actingAs($treasurer)->post('/projects/'.$p->id.'/budget', $budget)->assertSessionHasNoErrors();
        $this->actingAs($member)->post($transition, ['action' => 'start'])->assertSessionHasNoErrors();
        $this->post($transition, ['action' => 'request_completion'])->assertSessionHasErrors('action');
        $this->assertSame('Ongoing', $p->fresh()->status);
        $this->post('/projects/'.$p->id.'/tasks', ['title' => 'Prepare learning kits', 'assignees' => [$assignee->id], 'priority' => 'High', 'status' => 'To Do', 'starts_on' => '2026-11-01', 'deadline' => '2026-11-10'])->assertSessionHasNoErrors();
        $task = Task::firstOrFail();
        $this->actingAs($assignee)->post('/projects/'.$p->id.'/tasks/'.$task->id.'/respond', ['response' => 'accepted'])->assertSessionHasNoErrors();
        $this->actingAs($assignee)->put('/projects/'.$p->id.'/tasks/'.$task->id, ['status' => 'Completed', 'notes' => 'Kits ready', 'evidence' => 'Inventory confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('Prepare learning kits', $task->fresh()->title);
        $expense = ['reference' => 'EXP-001', 'project_id' => $p->id, 'budget_item_id' => $budgetItemId, 'transaction_date' => '2026-11-02', 'type' => 'Expense', 'category' => 'Learning materials', 'account' => 'Chapter fund', 'counterparty' => 'Local supplier', 'payment_method' => 'Cash', 'description' => 'Learning kits', 'amount' => 2500, 'receipt' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')];
        $this->actingAs($treasurer)->post('/ledger', $expense)->assertSessionHasNoErrors();
        $this->assertSame(7500.0, $p->fresh()->remaining);
        $entry = LedgerEntry::firstOrFail();
        $this->post('/ledger/'.$entry->id, ['action' => 'liquidate', 'remarks' => 'Receipt verified.'])->assertForbidden();
        $this->get('/ledger')->assertOk()->assertDontSee('Confirm liquidation');
        $this->actingAs($member)->post($transition, ['action' => 'request_completion'])->assertSessionHasNoErrors();
        $this->actingAs($member)->post('/records/reports', ['project_id' => $p->id, 'title' => 'Literacy project completion', 'type' => 'Completion', 'data' => array_merge(array_fill_keys(array_keys(ChapterForms::REPORT), 'Completed and documented'), ['submission_method' => 'digital'])])->assertSessionHasNoErrors();
        $report = ProjectReport::firstOrFail();
        $this->get('/records/reports/'.$report->id)->assertOk();
        $this->post('/records/reports/'.$report->id.'/transition', ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/records/reports/'.$report->id.'/transition', ['action' => 'approve', 'comments' => 'Report reviewed.'])->assertSessionHasNoErrors();
        $this->assertSame('Completion Review', $p->fresh()->status);
        $this->actingAs($member)->post('/records/reports', ['project_id' => $p->id, 'title' => 'Liquidation', 'type' => 'Liquidation', 'data' => ['report_date' => '2026-11-16', 'voucher_reference' => 'CDV-001', 'cash_received' => 2500, 'reconciliation_notes' => 'Reconciled', 'submission_method' => 'digital']])->assertSessionHasNoErrors();
        $liquidation = ProjectReport::where('type', 'Liquidation')->firstOrFail();
        $this->post('/records/reports/'.$liquidation->id.'/transition', ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post('/records/reports/'.$liquidation->id.'/transition', ['action' => 'approve', 'comments' => 'Receipts verified'])->assertSessionHasNoErrors();
        $this->assertSame('Liquidated', $entry->fresh()->liquidation_status);
        $this->assertSame('Completed', $p->fresh()->status);
        $this->assertNotNull($p->fresh()->completed_at);
        $this->assertNull($p->fresh()->archived_at);
        $this->actingAs($member)->get('/records/reports/'.$liquidation->id)->assertOk()->assertSee('Print / save PDF');
        $this->assertGreaterThan(15, AuditLog::count());
        $this->actingAs($member)->get('/projects/'.$p->id)->assertOk();
    }

    public function test_dues_payment_creates_one_ledger_record_and_void_restores_balance(): void
    {
        $member = $this->user(); $other = $this->user(); $treasurer = $this->user('treasurer');
        $this->actingAs($treasurer)->post('/dues', ['period' => '2026-10', 'due_date' => '2026-10-15', 'amount' => 500, 'member_id' => $member->id, 'remarks' => 'Chapter approved rate'])->assertSessionHasNoErrors();
        $due = MemberDue::firstOrFail();
        $this->get(route('dues.manage'))->assertOk()
            ->assertSee('id="dues-assessment-dialog"', false)
            ->assertSee('href="'.route('dues.assessments').'"', false)
            ->assertDontSee('<article class="due-card">', false);
        $this->get(route('dues.assessments'))->assertOk()
            ->assertSee('id="due-payment-dialog-'.$due->id.'"', false)
            ->assertSee('<article class="due-card">', false)
            ->assertSee('type="radio" name="payment_method"', false)
            ->assertSee('Unpaid')
            ->assertSee('Paid')
            ->assertDontSee('<progress', false)
            ->assertDontSee('Record this payment and post it to the chapter ledger?')
            ->assertDontSee('type="select"', false);
        $this->get(route('ledger'))->assertOk()
            ->assertSee('id="ledger-transaction-dialog"', false)
            ->assertSee('Transaction type')
            ->assertSee('<select name="status">', false)
            ->assertSee('<select name="direction">', false)
            ->assertDontSee('<details class="ledger-row"', false);
        $payment = ['reference' => 'DUES-001', 'amount' => 200, 'transaction_date' => '2026-10-01', 'payment_method' => 'Cash'];
        $this->post('/dues/'.$due->id.'/payment', $payment)->assertSessionHasNoErrors();
        $this->get(route('ledger'))->assertOk()->assertSee('id="ledger-entry-dialog-'.LedgerEntry::firstOrFail()->id.'"', false);
        $this->post('/dues/'.$due->id.'/payment', $payment)->assertSessionHasErrors('reference');
        $this->assertSame(1, LedgerEntry::count());
        $this->assertSame(300.0, $due->fresh()->balance);
        $this->actingAs($other)->get('/dues')->assertDontSee('DUES-001');
        $this->actingAs($member)->get('/dues')->assertSee('DUES-001');
        $this->post('/dues/'.$due->id.'/payment', ['reference' => 'FAKE', 'amount' => 100])->assertForbidden();
        $this->actingAs($treasurer)->post('/ledger/'.LedgerEntry::first()->id, ['action' => 'void', 'remarks' => 'Payment entered against wrong period'])->assertSessionHasNoErrors();
        $this->assertSame(500.0, $due->fresh()->balance);
    }

    public function test_treasurer_dues_roster_lists_registered_accounts_even_without_assessments(): void
    {
        $treasurer = $this->user('treasurer', ['name' => 'Chapter Treasurer Example']);
        $active = $this->user('member', ['name' => 'Active Dues Example']);
        $inactive = $this->user('member', ['name' => 'Inactive Dues Example', 'status' => 'inactive']);

        $this->actingAs($treasurer)->get(route('dues.manage'))->assertOk()
            ->assertSee('Registered accounts')
            ->assertSee('Active Dues Example')
            ->assertSee('Inactive Dues Example')
            ->assertSee('data-dues-member-id="'.$active->id.'"', false)
            ->assertDontSee('data-dues-member-id="'.$inactive->id.'"', false)
            ->assertSee('id="dues-assessment-dialog"', false)
            ->assertSee('All active members')
            ->assertSee('Member Dues')
            ->assertSee('My Member Dues')
            ->assertDontSee('<article class="due-card">', false);
        $this->get(route('dues.assessments'))->assertOk()->assertSee('No dues assessments yet');

        $this->withCookie(config('session.cookie'), session()->getId())->followingRedirects()->from(route('dues.manage'))->post(route('dues.store'), [
            '_finance_dialog' => 'dues-assessment-dialog', 'period' => '2026-11', 'due_date' => '2026-11-30',
            'amount' => 300, 'member_id' => $inactive->id, 'remarks' => 'Test assessment',
        ])->assertOk()->assertSee('data-finance-dialog-reopen="dues-assessment-dialog"', false);
        $this->assertSame(0, MemberDue::count());

        $this->from(route('dues.manage'))
            ->post(route('dues.store'), ['period' => '2026-11', 'due_date' => '2026-11-30', 'amount' => 300, 'member_id' => $active->id, 'remarks' => 'Test assessment'])
            ->assertSessionHasNoErrors()->assertRedirect(route('dues.assessments', ['member' => $active->id]));
        $this->get(route('dues.assessments', ['member' => $active->id]))->assertOk()
            ->assertSee('Active Dues Example')
            ->assertSee('1 period');

        $this->actingAs($active)->get(route('dues.manage'))->assertForbidden();
        $this->get(route('dues.assessments'))->assertForbidden();
    }

    public function test_member_assessment_tabs_separate_paid_unpaid_and_waived_periods(): void
    {
        $member = $this->user();
        $treasurer = $this->user('treasurer');
        $this->actingAs($treasurer);

        foreach ([['2026-01', 100], ['2026-02', 150], ['2026-03', 0]] as [$period, $amount]) {
            $this->post(route('dues.store'), [
                'period' => $period, 'due_date' => $period.'-20', 'amount' => $amount,
                'member_id' => $member->id, 'remarks' => $amount ? 'Approved dues' : 'Approved waiver',
            ])->assertSessionHasNoErrors();
        }
        $paidDue = MemberDue::where('period', '2026-01')->firstOrFail();
        $this->post(route('dues.payment', $paidDue), [
            'reference' => 'DUES-PAID-001', 'amount' => 100, 'transaction_date' => '2026-01-20', 'payment_method' => 'Cash',
        ])->assertSessionHasNoErrors();

        $this->get(route('dues.assessments', ['status' => 'paid']))->assertOk()
            ->assertSee('January 2026')->assertDontSee('February 2026')->assertDontSee('March 2026');
        $this->get(route('dues.assessments', ['status' => 'unpaid', 'period' => '2026-02']))->assertOk()
            ->assertSee('February 2026')->assertDontSee('January 2026')->assertDontSee('March 2026');
        $this->get(route('dues.assessments', ['status' => 'waived']))->assertOk()
            ->assertSee('March 2026')->assertDontSee('January 2026')->assertDontSee('February 2026');
        $this->get(route('dues.assessments', ['status' => 'unknown']))->assertSessionHasErrors('status');
    }

    public function test_ledger_and_member_dues_print_reports_keep_filters_and_private_access(): void
    {
        $treasurer = $this->user('treasurer');
        $member = $this->user('member', ['name' => 'Print Report Member']);
        $other = $this->user('member', ['name' => 'Other Print Member']);
        $due = MemberDue::create(['member_id' => $member->id, 'period' => '2026-11', 'due_date' => '2026-11-30', 'amount' => 120]);
        MemberDue::create(['member_id' => $other->id, 'period' => '2026-11', 'due_date' => '2026-11-30', 'amount' => 90]);
        LedgerEntry::create(['reference' => 'PRINT-DUES-001', 'member_id' => $member->id, 'member_due_id' => $due->id, 'type' => 'Income', 'direction' => 'credit', 'status' => 'Posted', 'amount' => 120, 'transaction_date' => '2026-11-12']);
        LedgerEntry::create(['reference' => 'PRINT-VOID-001', 'type' => 'Income', 'direction' => 'credit', 'status' => 'Void', 'amount' => 50, 'transaction_date' => '2026-11-12']);

        $this->actingAs($treasurer)->get(route('ledger', ['q' => 'PRINT-', 'print' => 1]))->assertOk()
            ->assertSee('Print / save PDF')->assertSee('PRINT-DUES-001')->assertSee('PRINT-VOID-001')
            ->assertSee('Posted credits')->assertSee('PHP 120.00');
        $this->get(route('dues.assessments', ['member' => $member->id, 'print' => 1]))->assertOk()
            ->assertSee('Member dues report')->assertSee('Print Report Member')->assertDontSee('Other Print Member')
            ->assertSee('PRINT-DUES-001');
        $this->actingAs($member)->get(route('dues', ['print' => 1]))->assertOk()
            ->assertSee('My member dues report')->assertSee('PRINT-DUES-001')->assertDontSee('Other Print Member');
        $this->get(route('ledger', ['print' => 1]))->assertForbidden();
    }

    public function test_invalid_dues_payment_reopens_only_its_modal_without_copying_values_to_other_periods(): void
    {
        $member = $this->user();
        $treasurer = $this->user('treasurer');
        $this->actingAs($treasurer);
        foreach (['2026-08', '2026-09'] as $period) {
            $this->post(route('dues.store'), [
                'period' => $period, 'due_date' => $period.'-20', 'amount' => 100,
                'member_id' => $member->id, 'remarks' => 'Approved dues',
            ])->assertSessionHasNoErrors();
        }
        $due = MemberDue::where('period', '2026-08')->firstOrFail();
        $url = route('dues.assessments', ['member' => $member->id]);
        $page = $this->withCookie(config('session.cookie'), session()->getId())->followingRedirects()->from($url)->post(route('dues.payment', $due), [
            '_finance_dialog' => 'due-payment-dialog-'.$due->id,
            'reference' => 'TRY-REFERENCE', 'amount' => 200,
            'transaction_date' => '2026-08-20', 'payment_method' => 'Cash',
        ])->assertOk()
            ->assertSee('data-finance-dialog-reopen="due-payment-dialog-'.$due->id.'"', false)
            ->assertSee('Please review the payment:');
        $this->assertSame(1, substr_count($page->getContent(), 'value="TRY-REFERENCE"'));
        $this->assertSame(0, LedgerEntry::count());
    }

    public function test_project_liquidation_review_verifies_expenses_and_chapter_receipts_keep_a_separate_path(): void
    {
        Storage::fake('local');
        $chair = $this->user();
        $treasurer = $this->user('treasurer');
        $project = $this->project($chair, ['status' => 'Ongoing', 'chair_id' => $chair->id]);
        $expense = LedgerEntry::create([
            'reference' => 'REVIEW-EXP-001', 'project_id' => $project->id, 'type' => 'Expense',
            'direction' => 'debit', 'status' => 'Posted', 'liquidation_status' => 'Pending',
            'amount' => 100, 'transaction_date' => '2026-11-01', 'category' => 'Materials',
            'account' => 'Chapter fund', 'counterparty' => 'Supplier', 'description' => 'Materials',
        ]);
        $report = ProjectReport::create([
            'project_id' => $project->id, 'created_by' => $chair->id, 'title' => 'Project liquidation',
            'type' => 'Liquidation', 'status' => 'Submitted', 'data' => [],
        ]);
        $this->actingAs($treasurer)->post(route('records.transition', ['reports', $report->id]), [
            'action' => 'approve', 'comments' => 'Receipt verified',
        ])->assertSessionHasErrors('action');
        $this->assertSame('Submitted', $report->fresh()->status);
        $this->assertSame('Pending', $expense->fresh()->liquidation_status);

        $chapterExpense = LedgerEntry::create([
            'reference' => 'CHAPTER-EXP-001', 'type' => 'Expense', 'direction' => 'debit',
            'status' => 'Posted', 'liquidation_status' => 'Pending', 'amount' => 50,
            'transaction_date' => '2026-11-02', 'category' => 'Operations',
            'account' => 'Chapter fund', 'counterparty' => 'Vendor', 'description' => 'Chapter supplies',
        ]);
        $this->get(route('ledger'))->assertOk()->assertSee('Verify chapter expense receipt');
        $this->post(route('ledger.update', $chapterExpense), [
            'action' => 'liquidate', 'remarks' => 'Official receipt checked',
            'receipt' => UploadedFile::fake()->create('chapter-receipt.pdf', 10, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->assertSame('Liquidated', $chapterExpense->fresh()->liquidation_status);
    }

    public function test_letters_preserve_issued_project_details_and_require_admin_review(): void
    {
        $chair = $this->user(); $admin = $this->user('admin');
        $project = $this->project($chair, ['status' => 'Ongoing', 'chair_id' => $chair->id]);
        $letterData = array_fill_keys(array_keys(ChapterForms::LETTER), 'School partnership details');
        $letterData['letter_date'] = '2026-10-24';
        $data = ['project_id' => $project->id, 'title' => 'School partnership', 'type' => 'External Partner Letter', 'data' => $letterData];
        $this->actingAs($chair)->post('/records/letters', $data)->assertSessionHasNoErrors();
        $letter = Loi::firstOrFail();
        $this->post('/records/letters/'.$letter->id.'/transition', ['action' => 'sent'])->assertForbidden();
        $this->post('/records/letters/'.$letter->id.'/transition', ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/records/letters/'.$letter->id.'/transition', ['action' => 'approve', 'comments' => 'Approved for sending'])->assertSessionHasNoErrors();
        $project->update(['title' => 'Updated project title']);
        $this->assertSame('Community literacy project', $letter->fresh()->data['project_snapshot']['title']);
        $this->actingAs($chair)->post('/records/letters/'.$letter->id.'/transition', ['action' => 'sent', 'delivered_on' => '2026-10-25'])->assertSessionHasNoErrors();
        $this->post('/records/letters/'.$letter->id.'/transition', ['action' => 'revise'])->assertSessionHasNoErrors();
        $this->assertSame('Sent', $letter->fresh()->versions[0]['status']);
        $this->get('/records/letters/'.$letter->id)->assertOk();
    }

    public function test_project_documents_render_and_report_captures_posted_financials(): void
    {
        $chair = $this->user();
        $treasurer = $this->user('treasurer');
        $proposal = array_fill_keys(array_keys(ChapterForms::PROPOSAL), 'Project plan');
        $proposal['projected_income'] = '5000';
        $project = $this->project($chair, ['status' => 'Ongoing', 'chair_id' => $chair->id, 'proposal' => $proposal]);

        $draft = $this->project($chair, ['status' => 'Approved', 'chair_id' => $chair->id]);
        $draft->update(['plan_locked_at' => null]);
        $this->actingAs($chair)->get('/projects/'.$draft->id.'/edit')->assertOk()->assertSee('Project Proposal Form (PPF)');
        $this->actingAs($chair)->get('/projects/'.$project->id.'/proposal')
            ->assertOk()->assertSee('Internal Chapter Project Proposal Transmittal Letter');
        $this->get('/records/letters/create?project_id='.$project->id)->assertOk()->assertSee('External partnership LOI');
        $this->get('/records/reports/create?project_id='.$project->id)->assertOk()->assertSee('Purpose and results');
        $this->get('/records/reports/create?project_id='.$project->id.'&type=Liquidation')
            ->assertOk()->assertSee('Cash and voucher details')->assertDontSee('Purpose and results');
        $this->get('/projects/'.$project->id.'/liquidation')->assertForbidden();

        LedgerEntry::create([
            'reference' => 'PROJ-EXP-001', 'project_id' => $project->id, 'type' => 'Expense',
            'direction' => 'debit', 'status' => 'Posted', 'liquidation_status' => 'Pending',
            'amount' => 1200, 'transaction_date' => '2026-11-15', 'category' => 'Materials',
            'counterparty' => 'Supplier', 'description' => 'Materials',
        ]);
        $this->actingAs($treasurer)->get('/projects/'.$project->id.'/liquidation')
            ->assertOk()->assertSee('PROJ-EXP-001')->assertSee('1 pending liquidation')->assertSee('Print / save PDF');

        $project->update(['status' => 'Completion Review']);
        $this->actingAs($chair)->post('/records/reports', [
            'project_id' => $project->id, 'title' => 'Project completion', 'type' => 'Completion',
            'data' => array_merge(array_fill_keys(array_keys(ChapterForms::REPORT), 'Completed'), ['submission_method' => 'digital']),
        ])->assertSessionHasNoErrors();
        $report = ProjectReport::firstOrFail();
        $this->post('/records/reports/'.$report->id.'/transition', ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->assertEquals(1200, $report->fresh()->data['financial_snapshot']['actual_expenses']);
        $this->assertSame('Community literacy project', $report->fresh()->data['project_snapshot']['title']);
        $project->update(['title' => 'Renamed after submission']);
        $this->get('/records/reports/'.$report->id)->assertOk()->assertSee('Proposed')->assertSee('Actual posted')->assertSee('Print / save PDF');
        $this->get('/records/reports/'.$report->id)->assertOk()->assertSee('Community literacy project');
    }

    public function test_members_cannot_access_private_concepts_financial_records_or_admin_controls(): void
    {
        $owner = $this->user(); $member = $this->user();
        $project = $this->project($owner);
        $this->actingAs($member)->get('/projects/'.$project->id)->assertForbidden();
        foreach (['ledger', 'ledger/export', 'dues/manage', 'audit', 'members/create'] as $path) $this->get('/'.$path)->assertForbidden();
        $this->put('/members/'.$owner->id, ['role' => 'admin'])->assertForbidden();
        $this->post('/projects/'.$project->id.'/documents')->assertForbidden();
        $this->post('/projects/'.$project->id.'/tasks')->assertForbidden();
    }

    public function test_expenses_cannot_exceed_allocation_and_voided_entries_stop_counting(): void
    {
        Storage::fake('local');
        $treasurer = $this->user('treasurer'); $project = $this->project($treasurer, ['status' => 'Ongoing']);
        $project->budget_items = \App\Support\ProjectBudget::normalize([['category' => 'materials_supplies', 'description' => 'Supplies', 'quantity' => 1, 'unit_cost' => 1000], ['category' => 'other', 'description' => 'Other project needs', 'quantity' => 1, 'unit_cost' => 9000]]);
        $project->save();
        $budgetItemId = $project->budget_items[0]['id'];
        $project->allocations()->create(['category' => 'Supplies', 'budget_item_id' => $budgetItemId, 'amount' => 1000]);
        $payload = ['reference' => 'EXP-002', 'project_id' => $project->id, 'budget_item_id' => $budgetItemId, 'transaction_date' => '2026-10-01', 'type' => 'Expense', 'category' => 'Supplies', 'account' => 'Chapter fund', 'counterparty' => 'Supplier', 'payment_method' => 'Cash', 'description' => 'Supplies', 'amount' => 1001, 'receipt' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')];
        $this->actingAs($treasurer)->post('/ledger', $payload)->assertSessionHasErrors('amount');
        $this->assertSame(0, LedgerEntry::count());
        $payload['amount'] = 1000;
        $this->post('/ledger', $payload)->assertSessionHasNoErrors();
        $this->assertSame(0.0, $project->fresh()->remaining);
        $entry = LedgerEntry::firstOrFail();
        $this->post('/ledger/'.$entry->id, ['action' => 'void', 'remarks' => 'Duplicate invoice'])->assertSessionHasNoErrors();
        $this->assertSame(1000.0, $project->fresh()->remaining);
    }

    public function test_financial_report_snapshot_stays_fixed_after_later_transactions(): void
    {
        $treasurer = $this->user('treasurer'); $admin = $this->user('admin'); $member = $this->user();
        LedgerEntry::create(['reference' => 'INC-001', 'type' => 'Income', 'direction' => 'credit', 'status' => 'Posted', 'amount' => 2000, 'transaction_date' => '2026-10-05']);
        $data = array_fill_keys(array_keys(ChapterForms::FINANCIAL_REPORT), 'Reviewed financial information');
        $data['period_start'] = '2026-10-01'; $data['period_end'] = '2026-10-31';
        $this->actingAs($treasurer)->post('/records/reports', ['title' => 'October financial report', 'type' => 'Overall Financial', 'data' => $data])->assertSessionHasNoErrors();
        $report = ProjectReport::firstOrFail();
        $this->post('/records/reports/'.$report->id.'/transition', ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->get('/records/reports/'.$report->id)->assertOk()->assertSee('Print / save PDF');
        LedgerEntry::create(['reference' => 'INC-002', 'direction' => 'credit', 'status' => 'Posted', 'amount' => 5000, 'transaction_date' => '2026-10-10']);
        $this->assertEquals(2000, $report->fresh()->data['financial_snapshot']['income']);
        $this->actingAs($admin)->get('/records/reports/'.$report->id)->assertOk()->assertSee('Admin review and approval');
        $this->post('/records/reports/'.$report->id.'/transition', ['action' => 'approve', 'comments' => 'Financial report reviewed.'])->assertSessionHasNoErrors();
        $this->actingAs($member)->get('/records/reports/'.$report->id)->assertForbidden();
    }

    public function test_all_roles_can_monitor_posted_chapter_funds_without_ledger_access(): void
    {
        LedgerEntry::create(['reference' => 'CHAPTER-IN', 'type' => 'Income', 'direction' => 'credit', 'status' => 'Posted', 'amount' => '5000.00']);
        LedgerEntry::create(['reference' => 'CHAPTER-OUT', 'type' => 'Expense', 'direction' => 'debit', 'status' => 'Posted', 'amount' => '1250.00']);
        LedgerEntry::create(['reference' => 'VOID-OUT', 'type' => 'Expense', 'direction' => 'debit', 'status' => 'Void', 'amount' => '900.00']);

        foreach (['admin', 'bod', 'treasurer', 'member'] as $role) {
            $this->actingAs($this->user($role));
            $this->get('/dashboard')->assertOk()->assertSee('Chapter funds available')->assertSee('PHP 3,750.00');
            $this->get('/finance')->assertOk()->assertSee('Chapter funds available')->assertSee('PHP 3,750.00');
        }

        $this->get('/ledger')->assertForbidden();
    }

    public function test_inactive_accounts_are_blocked_and_last_admin_cannot_be_deactivated(): void
    {
        $inactive = $this->user('member', ['status' => 'inactive']);
        $this->actingAs($inactive)->get('/dashboard')->assertRedirect('/login');
        $admin = $this->user('admin');
        $this->actingAs($admin)->put('/members/'.$admin->id, ['name' => $admin->name, 'email' => $admin->email, 'username' => $admin->username, 'role' => 'member', 'status' => 'inactive'])->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_draft_documents_are_private_and_downloads_require_project_access(): void
    {
        Storage::fake('local');
        $owner = $this->user(); $other = $this->user();
        $p = $this->project($owner);
        $this->actingAs($owner)->post('/projects/'.$p->id.'/documents', ['title' => 'Concept evidence', 'category' => 'Supporting document', 'file' => UploadedFile::fake()->create('evidence.pdf', 12, 'application/pdf')])->assertSessionHasNoErrors();
        $doc = $p->documents()->firstOrFail();
        Storage::disk('local')->assertExists($doc->path);
        $this->get('/documents')->assertOk()->assertSee('Concept evidence');
        $this->get('/documents/'.$doc->id.'/download')->assertOk();
        $this->actingAs($other)->get('/documents')->assertOk()->assertDontSee('Concept evidence');
        $this->actingAs($other)->get('/documents/'.$doc->id.'/download')->assertForbidden();
        $p->update(['status' => 'Approved']);
        $this->get('/documents')->assertOk()->assertSee('Concept evidence');
        $this->get('/documents/'.$doc->id.'/download')->assertOk();
    }

    public function test_profile_photo_is_saved_privately_and_can_be_replaced_or_removed(): void
    {
        Storage::fake('local');
        $member = $this->user();
        $other = $this->user();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/qXcAAAAASUVORK5CYII=');

        $this->actingAs($member)->put('/account', [
            'name' => $member->name,
            'email' => $member->email, 'username' => $member->username,
            'photo' => UploadedFile::fake()->create('not-an-image.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors('photo');

        $this->actingAs($member)->put('/account', [
            'name' => $member->name,
            'email' => $member->email, 'username' => $member->username,
            'photo' => UploadedFile::fake()->createWithContent('portrait.png', $png),
        ])->assertSessionHasNoErrors();

        $firstPath = $member->fresh()->profile['photo_path'];
        $this->assertStringStartsWith('profile-photos/'.$member->id.'/', $firstPath);
        Storage::disk('local')->assertExists($firstPath);
        $this->get('/account/photo')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($other)->get('/account/photo')->assertNotFound();

        $this->actingAs($member)->put('/account', [
            'name' => $member->name,
            'email' => $member->email, 'username' => $member->username,
            'photo' => UploadedFile::fake()->createWithContent('replacement.png', $png),
        ])->assertSessionHasNoErrors();
        $secondPath = $member->fresh()->profile['photo_path'];
        Storage::disk('local')->assertExists($secondPath);
        Storage::disk('local')->assertMissing($firstPath);

        $this->put('/account', ['name' => $member->name, 'email' => $member->email, 'username' => $member->username, 'remove_photo' => 1])->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('photo_path', $member->fresh()->profile);
        Storage::disk('local')->assertMissing($secondPath);
    }

    public function test_profile_and_personal_settings_are_separate(): void
    {
        $member = $this->user();

        $this->actingAs($member)->get('/account')
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee('Profile photo')
            ->assertDontSee('Notification preferences')
            ->assertDontSee('Open another account');

        $this->get('/settings')
            ->assertOk()
            ->assertSee('Workspace Settings')
            ->assertSee('Starting workspace')
            ->assertSee('Layout and navigation')
            ->assertDontSee('Profile photo');

        $this->put('/settings', [
            'profile' => ['preferences' => ['start_page' => 'tasks', 'interface_density' => 'compact', 'navigation_mode' => 'expanded', 'reduce_motion' => 1]],
        ])->assertSessionHasNoErrors()->assertSessionHas('success', 'Settings saved.');

        $this->assertSame('tasks', data_get($member->fresh()->profile, 'preferences.start_page'));
        $this->assertTrue((bool) data_get($member->fresh()->profile, 'preferences.reduce_motion'));
    }
}

