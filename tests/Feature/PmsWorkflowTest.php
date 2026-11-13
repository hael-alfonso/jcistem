<?php

namespace Tests\Feature;

use App\Models\{BodDecision, ExpenseClaim, Project, ProjectReport, Task, User, LedgerEntry};
use App\Services\ProposalWorkflowService;
use App\Support\{ChapterForms, PmsNavigation};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PmsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'member'): User { return User::factory()->create(['role' => $role, 'status' => 'active']); }
    private function draft(User $owner, array $extra = []): Project
    {
        return Project::create($extra + ['created_by' => $owner->id, 'title' => 'Community action', 'area' => 'Community Impact', 'status' => 'Draft Concept', 'starts_on' => '2026-11-01', 'venue' => 'Carmona', 'proposed_budget' => '5000.00', 'concept' => array_merge(array_fill_keys(array_keys(ChapterForms::CONCEPT), 'Community need and response'), ['sdg_number' => '4']), 'proposal' => array_fill_keys(array_keys(ChapterForms::PROPOSAL), 'Measurable plan and budget')]);
    }
    private function approved(User $chair, string $status = 'Ongoing'): Project
    {
        $project = $this->draft($chair, ['chair_id' => $chair->id, 'status' => $status, 'plan_locked_at' => now(), 'proposal_reviewed_at' => now()]);
        BodDecision::create(['project_id' => $project->id, 'recorded_by' => $this->actor('admin')->id, 'stage' => 'proposal', 'meeting_date' => '2026-10-01', 'decision' => 'approved', 'resolution_number' => 'BOD-'.$project->id, 'evidence_path' => 'evidence.pdf', 'comments' => 'Signed board approval']);
        return $project;
    }

    public function test_all_four_roles_can_become_chair_only_after_ordered_reviews_and_signed_decisions_without_resolution_numbers(): void
    {
        Storage::fake('local');
        $admin = $this->actor('admin'); $treasurer = $this->actor('treasurer'); $bod = $this->actor('bod');
        foreach (['admin', 'treasurer', 'bod', 'member'] as $role) {
            $owner = $this->actor($role); $project = $this->draft($owner); $url = '/projects/'.$project->id.'/transition';
            $this->actingAs($owner)->post($url, ['action' => 'submit_concept'])->assertSessionHasNoErrors();
            $this->assertSame('Admin Review', $project->fresh()->status);
            $this->assertFalse($project->fresh()->canManage($owner));
            $this->actingAs($treasurer)->post($url, ['action' => 'budget_review', 'comments' => 'Out of order'])->assertForbidden();
            if ($role === 'admin') {
                $this->actingAs($owner)->post($url, ['action' => 'endorse', 'comments' => 'Admin compliance self-review'])->assertSessionHasNoErrors();
            } else {
                $this->actingAs($owner)->post($url, ['action' => 'endorse', 'comments' => 'Self review'])->assertForbidden();
            }
            $this->actingAs($bod)->post($url, ['action' => 'endorse', 'comments' => 'Board reviewer'])->assertForbidden();
            if ($role !== 'admin') $this->actingAs($admin)->post($url, ['action' => 'endorse', 'comments' => 'Compliant'])->assertSessionHasNoErrors();
            $this->assertSame('Treasurer Review', $project->fresh()->status);
            $this->actingAs($treasurer)->post($url, ['action' => 'budget_review', 'comments' => 'Feasible'])->assertSessionHasNoErrors();
            $this->assertSame('Endorsed for Development', $project->fresh()->status);
            $this->assertSame($owner->id, $project->fresh()->chair_id);
            $this->assertFalse($project->fresh()->canManage($owner));
            $this->actingAs($owner)->post($url, ['action' => 'start'])->assertForbidden();
            $this->post($url, ['action' => 'lock_plan'])->assertForbidden();
            $project->budget_items = [['id' => (string) \Illuminate\Support\Str::uuid(), 'category' => 'materials_supplies', 'description' => 'Community kits', 'quantity' => '1.00', 'unit_cost' => '5000.00', 'amount' => '5000.00']];
            $project->save();
            $this->actingAs($treasurer)->post('/projects/'.$project->id.'/budget', ['budget_item_id' => $project->budget_items[0]['id'], 'amount' => '5000.00', 'approved_by' => 'Board', 'approved_on' => '2026-10-02', 'remarks' => 'Premature allocation'])->assertStatus(422);
            $this->actingAs($owner);
            $this->post($url, ['action' => 'submit_full_proposal'])->assertRedirect()->assertSessionHasNoErrors();
            $this->actingAs($treasurer)->post($url, ['action' => 'review_full_budget', 'comments' => 'Full budget checked'])->assertRedirect()->assertSessionHasNoErrors();
            $this->actingAs($admin)->post($url, ['action' => 'approve_full_proposal', 'comments' => 'Approved'])->assertSessionHasErrors(['meeting_date', 'evidence']);
            $formalDecision = ['action' => 'approve_full_proposal', 'comments' => 'Full proposal approved', 'meeting_date' => '2026-10-02', 'evidence' => UploadedFile::fake()->create('signed-full.pdf', 10, 'application/pdf')];
            $this->actingAs($bod)->post($url, $formalDecision)->assertForbidden();
            $this->actingAs($admin)->post($url, $formalDecision)->assertRedirect()->assertSessionHasNoErrors();
            $this->actingAs($owner);
            $this->post($url, ['action' => 'lock_plan'])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertTrue($project->fresh()->canManage($owner));
            $this->post($url, ['action' => 'start'])->assertSessionHasNoErrors();
            $this->get('/projects/'.$project->id.'/edit')->assertForbidden();
            $this->assertSame('Ongoing', $project->fresh()->status);
        }
    }

    public function test_task_acceptance_and_chair_scope_are_enforced(): void
    {
        $chair = $this->actor('admin'); $assignee = $this->actor(); $outsider = $this->actor();
        $project = $this->approved($chair); $url = '/projects/'.$project->id.'/tasks';
        $payload = ['title' => 'Prepare kits', 'assignees' => [$assignee->id], 'priority' => 'High', 'status' => 'To Do', 'deadline' => '2026-11-01'];
        $this->actingAs($outsider)->post($url, $payload)->assertForbidden();
        $this->actingAs($chair)->post($url, $payload)->assertSessionHasNoErrors();
        $task = Task::firstOrFail();
        $this->actingAs($assignee)->put($url.'/'.$task->id, ['status' => 'Completed', 'evidence' => 'Signed checklist'])->assertForbidden();
        $this->post($url.'/'.$task->id.'/respond', ['response' => 'declined'])->assertSessionHasErrors('note');
        $this->post($url.'/'.$task->id.'/respond', ['response' => 'accepted'])->assertSessionHasNoErrors();
        $this->put($url.'/'.$task->id, ['status' => 'Completed'])->assertSessionHasErrors('evidence');
        $this->put($url.'/'.$task->id, ['status' => 'Completed', 'evidence' => 'Signed checklist'])->assertSessionHasNoErrors();
        $this->assertSame('Completed', $task->fresh()->status);
        $this->actingAs($outsider)->get('/tasks?mine=0')->assertOk()->assertSee('Prepare kits');
        $this->get('/tasks?mine=1')->assertOk()->assertDontSee('Prepare kits');
    }

    public function test_two_independent_report_approvals_are_required_and_return_preserves_the_other_approval(): void
    {
        $chair = $this->actor('admin'); $admin = $this->actor('admin'); $treasurer = $this->actor('treasurer');
        $project = $this->approved($chair, 'Completion Review'); $reports = [];
        foreach (['Completion', 'Liquidation'] as $type) {
            $reportData = $type === 'Liquidation'
                ? ['report_date' => '2026-10-31', 'voucher_reference' => 'CDV-001', 'cash_received' => '0.00', 'receipt_notes' => 'No posted expenses', 'reconciliation_notes' => 'No cash advanced', 'treasurer_name' => 'LO Treasurer', 'president_name' => 'LO President']
                : array_fill_keys(array_keys(ChapterForms::REPORT), 'Complete evidence and results');
            $this->actingAs($chair)->post('/records/reports', ['project_id' => $project->id, 'title' => $type.' report', 'type' => $type, 'data' => $reportData + ['submission_method' => 'digital']])->assertRedirect()->assertSessionHasNoErrors();
            $report = ProjectReport::where('type', $type)->firstOrFail(); $reports[$type] = $report;
            $this->post('/records/reports/'.$report->id.'/transition', ['action' => 'submit'])->assertSessionHasNoErrors();
        }
        $pcr = '/records/reports/'.$reports['Completion']->id.'/transition';
        $liq = '/records/reports/'.$reports['Liquidation']->id.'/transition';
        $this->post($pcr, ['action' => 'approve', 'comments' => 'Own report'])->assertForbidden();
        $this->actingAs($treasurer)->post($pcr, ['action' => 'approve', 'comments' => 'Wrong role'])->assertForbidden();
        $this->actingAs($admin)->post($pcr, ['action' => 'approve', 'comments' => 'Verified outcomes'])->assertSessionHasNoErrors();
        $this->assertSame('Completion Review', $project->fresh()->status);
        $this->actingAs($treasurer)->post($liq, ['action' => 'return', 'comments' => 'Clarify reconciliation'])->assertSessionHasNoErrors();
        $this->assertSame('Reviewed', $reports['Completion']->fresh()->status);
        $this->actingAs($chair)->post($liq, ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($liq, ['action' => 'approve', 'comments' => 'Reconciled'])->assertSessionHasNoErrors();
        $this->assertSame('Completed', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->completed_at);
        $this->assertNull($project->fresh()->archived_at);
        $this->actingAs($chair)->post($pcr, ['action' => 'revise'])->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'project_completed', 'object_id' => $project->id]);
    }

    public function test_chair_transactions_are_project_scoped_and_details_are_private(): void
    {
        Storage::fake('local');
        $chair = $this->actor(); $other = $this->actor(); $treasurer = $this->actor('treasurer');
        $project = $this->approved($chair); $foreign = $this->approved($other);
        $item = (string) \Illuminate\Support\Str::uuid();
        $project->budget_items = [['id' => $item, 'category' => 'materials_supplies', 'description' => 'Materials', 'quantity' => '1.00', 'unit_cost' => '100.10', 'amount' => '100.10']];
        $project->save();
        $project->allocations()->create(['category' => 'Materials', 'budget_item_id' => $item, 'amount' => '100.10']);
        $payload = ['spent_on' => '2026-10-20', 'budget_item_id' => $item, 'amount' => '100.11', 'payee' => 'Private supplier', 'description' => 'Private transaction description', 'receipt' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')];
        $this->actingAs($chair)->post('/projects/'.$foreign->id.'/expense-claims', $payload)->assertForbidden();
        $this->post('/projects/'.$project->id.'/expense-claims', $payload)->assertSessionHasErrors('amount');
        $payload['amount'] = '100.10';
        $this->post('/projects/'.$project->id.'/expense-claims', $payload)->assertSessionHasNoErrors();
        $claim = ExpenseClaim::firstOrFail();
        $this->assertSame('Pending', $claim->status);
        $this->assertSame(0, LedgerEntry::count());
        $project->tasks()->create(['title' => 'Prepare materials', 'status' => 'Completed']);
        $this->post('/projects/'.$project->id.'/transition', ['action' => 'request_completion'])->assertSessionHasErrors('action');
        $this->actingAs($treasurer)->post('/expense-claims/'.$claim->id.'/decision', ['action' => 'approve', 'review_note' => 'Receipt checked', 'account' => 'Project fund', 'payment_method' => 'Cash'])->assertSessionHasNoErrors();
        $entry = LedgerEntry::firstOrFail();
        $this->assertSame('100.10', $entry->amount);
        $this->assertSame($entry->id, $claim->fresh()->ledger_entry_id);
        $this->actingAs($chair);
        $this->get('/ledger')->assertForbidden();
        $this->actingAs($other)->get('/projects/'.$project->id)->assertOk()->assertDontSee('Private transaction description');
        $this->get('/ledger/'.$entry->id.'/receipt')->assertForbidden();
        $this->actingAs($chair)->get('/ledger/'.$entry->id.'/receipt')->assertOk();
        $this->actingAs($treasurer)->get('/ledger/'.$entry->id.'/receipt')->assertOk();
        $this->post('/ledger/'.$entry->id, ['action' => 'void', 'remarks' => 'Duplicate receipt'])->assertSessionHasNoErrors();
        $this->assertSame('Void', $claim->fresh()->status);
    }

    public function test_allocations_and_expenses_are_limited_to_specific_budget_lines(): void
    {
        Storage::fake('local');
        $chair = $this->actor(); $treasurer = $this->actor('treasurer');
        $project = $this->approved($chair);
        $first = (string) \Illuminate\Support\Str::uuid();
        $second = (string) \Illuminate\Support\Str::uuid();
        $project->budget_items = [
            ['id' => $first, 'category' => 'materials_supplies', 'description' => 'Learning kits', 'quantity' => '10.00', 'unit_cost' => '10.00', 'amount' => '100.00'],
            ['id' => $second, 'category' => 'food_refreshments', 'description' => 'Volunteer meals', 'quantity' => '10.00', 'unit_cost' => '10.00', 'amount' => '100.00'],
        ];
        $project->save();
        $approval = ['amount' => '100.00', 'approved_by' => 'BOD', 'approved_on' => '2026-10-31', 'remarks' => 'Approved line'];
        $this->actingAs($treasurer)->post('/projects/'.$project->id.'/budget', $approval + ['budget_item_id' => $first])->assertSessionHasNoErrors();
        $this->post('/projects/'.$project->id.'/budget', $approval + ['budget_item_id' => $second])->assertSessionHasNoErrors();
        $this->assertCount(2, $project->allocations()->get());
        $expense = ['spent_on' => '2026-10-31', 'amount' => '101.00', 'payee' => 'Supplier', 'description' => 'Learning kits', 'budget_item_id' => $first, 'receipt' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')];
        $this->actingAs($chair)->post('/projects/'.$project->id.'/expense-claims', $expense)->assertSessionHasErrors('amount');
        $expense['amount'] = '80.00';
        $this->post('/projects/'.$project->id.'/expense-claims', $expense)->assertSessionHasNoErrors();
        $claim = ExpenseClaim::firstOrFail();
        $this->assertDatabaseMissing('ledger_entries', ['budget_item_id' => $first, 'amount' => '80.00']);
        $this->actingAs($treasurer)->post('/expense-claims/'.$claim->id.'/decision', ['action' => 'approve', 'review_note' => 'Matched receipt', 'account' => 'Project fund', 'payment_method' => 'Cash'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ledger_entries', ['reference' => 'CLAIM-'.str_pad((string) $claim->id, 6, '0', STR_PAD_LEFT), 'budget_item_id' => $first, 'category' => 'Materials and supplies']);
        $this->get('/projects/'.$project->id)->assertOk()->assertSee('Learning kits')->assertSee('PHP 80.00');
    }

    public function test_returned_claim_can_be_corrected_by_its_chair_and_verified_by_treasurer(): void
    {
        Storage::fake('local');
        $chair = $this->actor(); $other = $this->actor(); $treasurer = $this->actor('treasurer');
        $project = $this->approved($chair);
        $item = (string) \Illuminate\Support\Str::uuid();
        $project->update(['budget_items' => [[
            'id' => $item, 'category' => 'materials_supplies', 'description' => 'Project materials',
            'quantity' => '1.00', 'unit_cost' => '200.00', 'amount' => '200.00',
        ]]]);
        $project->allocations()->create(['category' => 'Materials', 'budget_item_id' => $item, 'amount' => '200.00']);
        $this->actingAs($chair)->post(route('expense-claims.store', $project), [
            'budget_item_id' => $item, 'spent_on' => today()->toDateString(),
            'payee' => 'Local supplier', 'description' => 'Original claim', 'amount' => '80.00',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $claim = ExpenseClaim::firstOrFail();
        $this->actingAs($treasurer)->post(route('expense-claims.review', $claim), [
            'action' => 'return', 'review_note' => 'Correct the quantity and amount.',
        ])->assertSessionHasNoErrors();
        $this->actingAs($chair)->get(route('expense-claims.index'))->assertOk()
            ->assertSee('Correct and resubmit')->assertSee('Correct the quantity and amount.');
        $correction = [
            'budget_item_id' => $item, 'spent_on' => today()->toDateString(),
            'payee' => 'Local supplier', 'description' => 'Corrected quantity', 'amount' => '90.00',
        ];
        $this->actingAs($other)->put(route('expense-claims.update', $claim), $correction)->assertForbidden();
        $this->actingAs($chair)->put(route('expense-claims.update', $claim), $correction)->assertSessionHasNoErrors();
        $this->assertSame('Pending', $claim->fresh()->status);
        $this->assertSame('90.00', $claim->fresh()->amount);
        $this->assertNull($claim->fresh()->review_note);
        $this->actingAs($treasurer)->post(route('expense-claims.review', $claim), [
            'action' => 'approve', 'review_note' => 'Receipt verified',
            'account' => 'Project fund', 'payment_method' => 'Cash',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Posted', $claim->fresh()->status);
        $this->assertSame('90.00', LedgerEntry::firstOrFail()->amount);
    }

    public function test_treasurer_personal_claims_stay_personal_when_filters_are_applied(): void
    {
        $treasurer = $this->actor('treasurer');
        $chair = $this->actor();
        $project = $this->approved($chair);
        $claim = [
            'project_id' => $project->id,
            'budget_item_id' => (string) \Illuminate\Support\Str::uuid(),
            'spent_on' => today()->toDateString(),
            'payee' => 'Local supplier',
            'amount' => '100.00',
            'receipt_path' => 'expense-claims/example.pdf',
            'receipt_name' => 'example.pdf',
            'status' => 'Pending',
        ];
        ExpenseClaim::create($claim + ['submitted_by' => $chair->id, 'description' => 'Chair claim']);
        ExpenseClaim::create($claim + ['submitted_by' => $treasurer->id, 'description' => 'Treasurer own claim']);

        $this->actingAs($treasurer)->get(route('expense-claims.index', ['status' => 'all']))
            ->assertOk()->assertSee('Chair claim')->assertSee('Treasurer own claim');
        $this->get(route('expense-claims.index', ['status' => 'all', 'mine' => 1, 'q' => 'claim']))
            ->assertOk()->assertSee('Treasurer own claim')->assertDontSee('Chair claim')
            ->assertSee('name="mine" value="1"', false);
    }

    public function test_active_admin_can_open_user_accounts_while_other_roles_cannot(): void
    {
        $this->actingAs($this->actor('admin'))->get(route('accounts'))->assertOk();
        $this->actingAs($this->actor('treasurer'))->get(route('accounts'))->assertForbidden();
        $this->actingAs($this->actor())->get(route('accounts'))->assertForbidden();
    }

    public function test_legacy_allocation_and_expense_can_be_reconciled_without_changing_totals(): void
    {
        Storage::fake('local');
        $chair = $this->actor(); $treasurer = $this->actor('treasurer');
        $project = $this->approved($chair);
        $allocation = $project->allocations()->create(['category' => 'Materials', 'amount' => '100.00']);
        $entry = LedgerEntry::create(['project_id' => $project->id, 'reference' => 'OLD-EXPENSE-1', 'description' => 'Historical kits', 'amount' => '40.00', 'status' => 'Posted', 'direction' => 'debit']);
        $plan = ['budget_items' => [
            ['category' => 'materials_supplies', 'description' => 'Learning kits', 'quantity' => '1', 'unit_cost' => '100.00'],
            ['category' => 'other', 'description' => 'Other approved needs', 'quantity' => '1', 'unit_cost' => '4900.00'],
        ]];
        $this->actingAs($treasurer)->post('/projects/'.$project->id.'/budget/reconcile-plan', $plan)->assertSessionHasNoErrors();
        $itemId = $project->fresh()->budget_items[0]['id'];
        $this->post('/projects/'.$project->id.'/budget/reconcile-allocation/'.$allocation->id, ['lines' => [$itemId => '100.00']])->assertSessionHasNoErrors();
        $this->post('/projects/'.$project->id.'/budget/reconcile-entry/'.$entry->id, ['budget_item_id' => $itemId])->assertSessionHasNoErrors();
        $this->assertSame('100.00', number_format((float) $project->allocations()->sum('amount'), 2, '.', ''));
        $this->assertSame($itemId, $entry->fresh()->budget_item_id);
        $this->actingAs($chair)->post('/projects/'.$project->id.'/expense-claims', ['spent_on' => '2026-10-31', 'budget_item_id' => $itemId, 'amount' => '60.00', 'payee' => 'Local supplier', 'description' => 'Remaining materials', 'receipt' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    }

    public function test_every_visible_menu_destination_renders_for_each_role(): void
    {
        foreach (['admin', 'bod', 'treasurer', 'member'] as $role) {
            $user = $this->actor($role); $this->actingAs($user);
            foreach (PmsNavigation::for($user) as $items) foreach ($items as [$route, $label, $icon, $params]) if ($route) $this->get(route($route, $params))->assertOk();
        }
    }

    public function test_exports_are_real_files_and_do_not_include_sensitive_transactions(): void
    {
        Storage::fake('local'); $chair = $this->actor(); $member = $this->actor(); $project = $this->approved($chair);
        LedgerEntry::create(['project_id' => $project->id, 'reference' => 'SECRET-REF', 'description' => 'Private receipt information', 'amount' => '15.25', 'status' => 'Posted', 'direction' => 'debit']);
        $this->actingAs($member)->get('/reports?project_id='.$project->id)
            ->assertOk()->assertSee('Print / save PDF')->assertSee('PHP 15.25')
            ->assertDontSee('SECRET-REF')->assertDontSee('Private receipt information');
        foreach (['pdf', 'docx'] as $format) {
            $response = $this->actingAs($member)->post('/reports/export', ['format' => $format, 'type' => 'budgets', 'project_id' => $project->id])->assertOk();
            $record = \App\Models\GeneratedReport::latest('id')->firstOrFail();
            $bytes = Storage::disk('local')->get($record->path);
            $this->assertStringStartsWith($format === 'pdf' ? '%PDF' : 'PK', $bytes);
            if ($format === 'docx') {
                $zip = new \ZipArchive; $zip->open(Storage::disk('local')->path($record->path));
                $xml = $zip->getFromName('word/document.xml'); $zip->close();
                $this->assertStringContainsString('15.25', $xml);
                $this->assertStringNotContainsString('SECRET-REF', $xml);
                $this->assertStringNotContainsString('Private receipt information', $xml);
            }
        }
        $draft = $this->draft($chair);
        $this->post('/reports/export', ['format' => 'pdf', 'type' => 'budgets', 'project_id' => $draft->id])->assertForbidden();
    }

    public function test_revision_and_rejection_require_review_again_without_granting_chair_access(): void
    {
        Storage::fake('local'); $owner = $this->actor(); $admin = $this->actor('admin'); $treasurer = $this->actor('treasurer');
        $project = $this->draft($owner); $url = '/projects/'.$project->id.'/transition';
        $this->actingAs($owner)->post($url, ['action' => 'submit_concept'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($url, ['action' => 'endorse', 'comments' => 'Pass'])->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($url, ['action' => 'revise_budget', 'comments' => 'Clarify costs'])->assertSessionHasNoErrors();
        $this->assertSame('Needs Revision', $project->fresh()->status);
        $this->actingAs($owner)->post($url, ['action' => 'submit_concept'])->assertSessionHasErrors('action');
        $concept = $project->fresh()->concept;
        $concept[array_key_first($concept)] = 'Clarified community costs and scope';
        $this->put('/projects/'.$project->id, [
            'title' => $project->title, 'area' => $project->area,
            'starts_on' => '2026-11-01', 'venue' => 'Carmona',
            'proposed_budget' => '5000.00', 'concept' => $concept,
        ])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post($url, ['action' => 'submit_concept'])->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($url, ['action' => 'budget_review', 'comments' => 'Skip admin'])->assertForbidden();
        $this->actingAs($admin)->post($url, ['action' => 'endorse', 'comments' => 'Rechecked'])->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($url, ['action' => 'budget_review', 'comments' => 'Rechecked'])->assertSessionHasNoErrors();
        $project->update(['budget_items' => \App\Support\ProjectBudget::normalize([['category' => 'materials_supplies', 'description' => 'Community kits', 'quantity' => 1, 'unit_cost' => 5000]])]);
        $this->actingAs($owner)->post($url, ['action' => 'submit_full_proposal'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($url, ['action' => 'review_full_budget', 'comments' => 'Full budget checked'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($url, ['action' => 'reject_full_proposal', 'comments' => 'Board rejected', 'meeting_date' => '2026-10-01', 'evidence' => UploadedFile::fake()->create('minutes.pdf', 10, 'application/pdf')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Not Approved', $project->fresh()->status);
        $this->assertFalse($project->fresh()->isChair($owner));
        $this->actingAs($owner)->post($url, ['action' => 'submit_concept'])->assertForbidden();
    }

    public function test_templates_settings_and_liquidation_cannot_be_self_authorized(): void
    {
        Storage::fake('local'); $member = $this->actor(); $admin = $this->actor('admin'); $treasurer = $this->actor('treasurer');
        $this->actingAs($member)->post('/documents/templates', [])->assertForbidden();
        $this->put('/admin/settings', ['report_chapter_name' => 'Changed'])->assertForbidden();
        $this->actingAs($admin)->post('/documents/templates', ['title' => 'Approved PCR', 'version' => '2026.1', 'file' => UploadedFile::fake()->create('pcr.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $template = \App\Models\DocumentTemplate::firstOrFail();
        $this->actingAs($member)->get('/documents/templates/'.$template->id)->assertOk();
        $project = $this->approved($treasurer, 'Completion Review');
        $report = ProjectReport::create(['project_id' => $project->id, 'created_by' => $treasurer->id, 'title' => 'Own liquidation', 'type' => 'Liquidation', 'status' => 'Submitted']);
        $this->actingAs($treasurer)->post('/records/reports/'.$report->id.'/transition', ['action' => 'approve', 'comments' => 'Self'])->assertForbidden();
        $this->actingAs($member)->get('/records/reports/'.$report->id)->assertForbidden();
        $this->get('/records/reports')->assertDontSee('Own liquidation');
    }

    public function test_approved_budget_and_closed_project_transactions_are_locked(): void
    {
        $chair = $this->actor(); $treasurer = $this->actor('treasurer'); $project = $this->approved($chair, 'Approved');
        $project->update(['plan_locked_at' => null]);
        $this->actingAs($chair)->put('/projects/'.$project->id, ['title' => $project->title, 'area' => $project->area, 'proposed_budget' => '6000.00'])->assertForbidden();
        $lineId = (string) \Illuminate\Support\Str::uuid();
        $project->update(['budget_items' => [['id' => $lineId, 'category' => 'materials_supplies', 'description' => 'Supplies', 'quantity' => '1.00', 'unit_cost' => '5000.00', 'amount' => '5000.00']]]);
        $project->update(['proposal_reviewed_at' => null]);
        $this->actingAs($treasurer)->post('/projects/'.$project->id.'/budget', ['budget_item_id' => $lineId, 'amount' => '5000.01', 'approved_by' => 'Board', 'approved_on' => '2026-10-01', 'remarks' => 'Before full-proposal approval'])->assertStatus(422);
        $project->update(['proposal_reviewed_at' => now()]);
        $this->actingAs($treasurer)->post('/projects/'.$project->id.'/budget', ['budget_item_id' => $lineId, 'amount' => '5000.01', 'approved_by' => 'Board', 'approved_on' => '2026-10-01', 'remarks' => 'Over budget'])->assertSessionHasErrors('amount');
        $project->update(['status' => 'Completed', 'completed_at' => now()->subDays(8), 'archived_at' => now()]);
        $entry = LedgerEntry::create(['project_id' => $project->id, 'reference' => 'CLOSED', 'amount' => '10.00', 'status' => 'Posted', 'direction' => 'debit']);
        $this->post('/ledger/'.$entry->id, ['action' => 'void', 'remarks' => 'After closure'])->assertForbidden();
        $this->assertSame('Posted', $entry->fresh()->status);
    }
}
