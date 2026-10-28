<?php

namespace Tests\Feature;

use App\Models\{BodDecision, Project, ProjectReport, Task, User, LedgerEntry};
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
        return Project::create($extra + ['created_by' => $owner->id, 'title' => 'Community action', 'area' => 'Community Impact', 'status' => 'Draft Concept', 'starts_on' => '2026-11-01', 'venue' => 'Carmona', 'proposed_budget' => '5000.00', 'concept' => array_fill_keys(array_keys(ChapterForms::CONCEPT), 'Community need and response'), 'proposal' => array_fill_keys(array_keys(ChapterForms::PROPOSAL), 'Measurable plan and budget')]);
    }
    private function approved(User $chair, string $status = 'Ongoing'): Project
    {
        $project = $this->draft($chair, ['chair_id' => $chair->id, 'status' => $status, 'plan_locked_at' => now()]);
        BodDecision::create(['project_id' => $project->id, 'recorded_by' => $this->actor('admin')->id, 'meeting_date' => '2026-10-01', 'decision' => 'approved', 'resolution_number' => 'BOD-'.$project->id, 'evidence_path' => 'evidence.pdf', 'comments' => 'Signed board approval']);
        return $project;
    }

    public function test_all_four_roles_can_become_chair_only_after_ordered_reviews_and_recorded_resolution(): void
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
            $this->assertSame('BOD Agenda', $project->fresh()->status);
            $this->actingAs($admin)->post($url, ['action' => 'approve', 'comments' => 'Approved'])->assertSessionHasErrors(['meeting_date', 'resolution_number', 'evidence']);
            $decision = ['action' => 'approve', 'comments' => 'Approved at meeting', 'meeting_date' => '2026-10-01', 'resolution_number' => 'RES-'.$project->id, 'evidence' => UploadedFile::fake()->create('minutes.pdf', 10, 'application/pdf')];
            $this->actingAs($bod)->post($url, $decision)->assertForbidden();
            $this->actingAs($admin)->post($url, $decision)->assertSessionHasNoErrors();
            $this->assertSame($owner->id, $project->fresh()->chair_id);
            $this->assertFalse($project->fresh()->canManage($owner));
            $this->actingAs($owner)->post($url, ['action' => 'start'])->assertForbidden();
            $this->post($url, ['action' => 'lock_plan'])->assertSessionHasNoErrors();
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
            $this->actingAs($chair)->post('/records/reports', ['project_id' => $project->id, 'title' => $type.' report', 'type' => $type, 'data' => array_fill_keys(array_keys(ChapterForms::REPORT), 'Complete evidence and results')])->assertSessionHasNoErrors();
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
        $this->assertSame('Archived', $project->fresh()->status);
        $this->actingAs($chair)->post($pcr, ['action' => 'revise'])->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'project_archived', 'object_id' => $project->id]);
    }

    public function test_chair_transactions_are_project_scoped_and_details_are_private(): void
    {
        Storage::fake('local');
        $chair = $this->actor(); $other = $this->actor(); $treasurer = $this->actor('treasurer');
        $project = $this->approved($chair); $foreign = $this->approved($other);
        $project->allocations()->create(['category' => 'Materials', 'amount' => '100.10']);
        $payload = ['reference' => 'CHAIR-01', 'transaction_date' => '2026-10-20', 'type' => 'Expense', 'amount' => '100.11', 'category' => 'Materials', 'account' => 'Project fund', 'counterparty' => 'Private supplier', 'payment_method' => 'Cash', 'description' => 'Private transaction description', 'receipt' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')];
        $this->actingAs($chair)->post('/projects/'.$foreign->id.'/transactions', $payload)->assertForbidden();
        $this->post('/projects/'.$project->id.'/transactions', $payload)->assertSessionHasErrors('amount');
        $payload['amount'] = '100.10';
        $this->post('/projects/'.$project->id.'/transactions', $payload)->assertSessionHasNoErrors();
        $entry = LedgerEntry::firstOrFail();
        $this->assertSame('100.10', $entry->amount);
        $this->get('/ledger')->assertForbidden();
        $this->actingAs($other)->get('/projects/'.$project->id)->assertOk()->assertDontSee('Private transaction description');
        $this->get('/ledger/'.$entry->id.'/receipt')->assertForbidden();
        $this->actingAs($chair)->get('/ledger/'.$entry->id.'/receipt')->assertOk();
        $this->actingAs($treasurer)->get('/ledger/'.$entry->id.'/receipt')->assertOk();
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
        $this->actingAs($owner)->post($url, ['action' => 'submit_concept'])->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($url, ['action' => 'budget_review', 'comments' => 'Skip admin'])->assertForbidden();
        $this->actingAs($admin)->post($url, ['action' => 'endorse', 'comments' => 'Rechecked'])->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($url, ['action' => 'budget_review', 'comments' => 'Rechecked'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($url, ['action' => 'reject', 'comments' => 'Board rejected', 'meeting_date' => '2026-10-01', 'evidence' => UploadedFile::fake()->create('minutes.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->assertSame('Not Approved', $project->fresh()->status);
        $this->assertNull($project->fresh()->chair_id);
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
        $this->actingAs($chair)->put('/projects/'.$project->id, ['title' => $project->title, 'area' => $project->area, 'proposed_budget' => '6000.00'])->assertSessionHasErrors('proposed_budget');
        $this->actingAs($treasurer)->post('/projects/'.$project->id.'/budget', ['category' => 'Supplies', 'amount' => '5000.01', 'approved_by' => 'Board', 'approved_on' => '2026-10-01', 'remarks' => 'Over budget'])->assertSessionHasErrors('amount');
        $project->update(['status' => 'Archived']);
        $entry = LedgerEntry::create(['project_id' => $project->id, 'reference' => 'CLOSED', 'amount' => '10.00', 'status' => 'Posted', 'direction' => 'debit']);
        $this->post('/ledger/'.$entry->id, ['action' => 'void', 'remarks' => 'After closure'])->assertForbidden();
        $this->assertSame('Posted', $entry->fresh()->status);
    }
}
