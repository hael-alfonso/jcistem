<?php
namespace Tests\Feature;

use App\Models\{User, Project, Task, Loi, ProjectReport, LedgerEntry, MemberDue, AuditLog};
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
        return Project::create(array_merge(['created_by' => $owner->id, 'title' => 'Community literacy project', 'area' => 'Community Impact', 'status' => 'Draft Concept', 'starts_on' => '2026-11-15', 'ends_on' => '2026-11-16', 'venue' => 'Carmona', 'proposed_budget' => 10000, 'concept' => array_fill_keys(array_keys(ChapterForms::CONCEPT), 'Community literacy support'), 'proposal' => array_fill_keys(array_keys(ChapterForms::PROPOSAL), 'Complete project plan')], $extra));
    }

    public function test_all_roles_can_render_their_workspaces_and_authorized_forms(): void
    {
        foreach (['admin', 'bod', 'treasurer', 'member'] as $role) {
            $user = $this->user($role);
            $project = $this->project($user, ['status' => 'Ongoing', 'chair_id' => $user->id]);
            $this->actingAs($user);
            foreach (['dashboard', 'projects', 'projects/create', 'projects/'.$project->id, 'tasks', 'calendar', 'finance', 'dues', 'members', 'account', 'notifications', 'about', 'records/letters', 'records/reports', 'records/letters/create?project_id='.$project->id, 'records/reports/create?project_id='.$project->id] as $path) {
                $this->get('/'.$path)->assertOk();
            }
            if ($role === 'admin') foreach (['audit', 'members/create', 'members/'.$user->id.'/edit'] as $path) $this->get('/'.$path)->assertOk();
            if ($role === 'treasurer') foreach (['ledger', 'ledger?filter=liquidation', 'dues/manage', 'records/reports/create', 'ledger/export'] as $path) $this->get('/'.$path)->assertOk();
        }
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
            'concept' => array_fill_keys(array_keys(ChapterForms::CONCEPT), 'Community literacy support'),
        ])->assertRedirect();
        $p = Project::firstOrFail();
        $transition = '/projects/'.$p->id.'/transition';
        $this->post($transition, ['action' => 'start_proposal'])->assertForbidden();
        $this->post($transition, ['action' => 'submit_concept'])->assertSessionHasNoErrors();
        $this->post($transition, ['action' => 'endorse', 'comments' => 'Approve'])->assertForbidden();
        $this->actingAs($admin)->post($transition, ['action' => 'endorse', 'comments' => 'Develop measurable reading outcomes.'])->assertSessionHasNoErrors();
        $this->assertSame('Endorsed for Development', $p->fresh()->status);
        $this->actingAs($member)->post($transition, ['action' => 'start_proposal'])->assertSessionHasNoErrors();
        $this->assertSame('Community literacy support', $p->fresh()->proposal['rationale']);
        $this->put('/projects/'.$p->id, ['title' => $p->title, 'area' => $p->area, 'starts_on' => '2026-11-15', 'ends_on' => '2026-11-16', 'venue' => 'Carmona', 'proposed_budget' => 10000, 'proposal' => array_fill_keys(array_keys(ChapterForms::PROPOSAL), 'Complete project plan')])->assertSessionHasNoErrors();
        $this->post($transition, ['action' => 'submit_proposal'])->assertSessionHasNoErrors();
        $this->actingAs($bod)->post($transition, ['action' => 'approve', 'comments' => 'Approved'])->assertSessionHasErrors('action');
        $this->actingAs($treasurer)->post($transition, ['action' => 'budget_review', 'comments' => 'Budget is feasible.'])->assertSessionHasNoErrors();
        $this->actingAs($bod)->post($transition, ['action' => 'approve', 'comments' => 'Approved by the board.'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($transition, ['action' => 'assign_chair', 'chair_id' => $member->id])->assertSessionHasNoErrors();
        $budget = ['category' => 'Learning materials', 'amount' => 10000, 'approved_by' => 'Board', 'approved_on' => '2026-10-01', 'remarks' => 'Approved materials budget'];
        $this->actingAs($member)->post('/projects/'.$p->id.'/budget', $budget)->assertForbidden();
        $this->actingAs($treasurer)->post('/projects/'.$p->id.'/budget', $budget)->assertSessionHasNoErrors();
        $this->actingAs($member)->post($transition, ['action' => 'start'])->assertSessionHasNoErrors();
        $this->post('/projects/'.$p->id.'/tasks', ['title' => 'Prepare learning kits', 'assignees' => [$assignee->id], 'priority' => 'High', 'status' => 'To Do', 'starts_on' => '2026-11-01', 'deadline' => '2026-11-10'])->assertSessionHasNoErrors();
        $task = Task::firstOrFail();
        $this->actingAs($assignee)->put('/projects/'.$p->id.'/tasks/'.$task->id, ['status' => 'Completed', 'notes' => 'Kits ready', 'evidence' => 'Inventory confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('Prepare learning kits', $task->fresh()->title);
        $expense = ['reference' => 'EXP-001', 'project_id' => $p->id, 'transaction_date' => '2026-11-02', 'type' => 'Expense', 'category' => 'Learning materials', 'account' => 'Chapter fund', 'counterparty' => 'Local supplier', 'payment_method' => 'Cash', 'description' => 'Learning kits', 'amount' => 2500, 'receipt' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')];
        $this->actingAs($treasurer)->post('/ledger', $expense)->assertSessionHasNoErrors();
        $this->assertSame(7500.0, $p->fresh()->remaining);
        $entry = LedgerEntry::firstOrFail();
        $this->post('/ledger/'.$entry->id, ['action' => 'liquidate', 'remarks' => 'Receipt verified.'])->assertSessionHasNoErrors();
        $this->actingAs($member)->post('/records/reports', ['project_id' => $p->id, 'title' => 'Literacy project completion', 'type' => 'Completion', 'data' => array_fill_keys(array_keys(ChapterForms::REPORT), 'Completed and documented')])->assertSessionHasNoErrors();
        $report = ProjectReport::firstOrFail();
        $this->get('/records/reports/'.$report->id)->assertOk();
        $this->post('/records/reports/'.$report->id.'/transition', ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->post($transition, ['action' => 'request_completion'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/records/reports/'.$report->id.'/transition', ['action' => 'approve', 'comments' => 'Report reviewed.'])->assertSessionHasNoErrors();
        $this->post($transition, ['action' => 'complete'])->assertSessionHasNoErrors();
        $this->post($transition, ['action' => 'archive'])->assertSessionHasNoErrors();
        $this->assertSame('Archived', $p->fresh()->status);
        $this->assertGreaterThan(15, AuditLog::count());
        $this->actingAs($member)->get('/projects/'.$p->id)->assertOk();
    }

    public function test_dues_payment_creates_one_ledger_record_and_void_restores_balance(): void
    {
        $member = $this->user(); $other = $this->user(); $treasurer = $this->user('treasurer');
        $this->actingAs($treasurer)->post('/dues', ['period' => '2026-10', 'due_date' => '2026-10-15', 'amount' => 500, 'member_id' => $member->id, 'remarks' => 'Chapter approved rate'])->assertSessionHasNoErrors();
        $due = MemberDue::firstOrFail();
        $payment = ['reference' => 'DUES-001', 'amount' => 200, 'transaction_date' => '2026-10-01', 'payment_method' => 'Cash'];
        $this->post('/dues/'.$due->id.'/payment', $payment)->assertSessionHasNoErrors();
        $this->post('/dues/'.$due->id.'/payment', $payment)->assertSessionHasErrors('reference');
        $this->assertSame(1, LedgerEntry::count());
        $this->assertSame(300.0, $due->fresh()->balance);
        $this->actingAs($other)->get('/dues')->assertDontSee('DUES-001');
        $this->actingAs($member)->get('/dues')->assertSee('DUES-001');
        $this->post('/dues/'.$due->id.'/payment', ['reference' => 'FAKE', 'amount' => 100])->assertForbidden();
        $this->actingAs($treasurer)->post('/ledger/'.LedgerEntry::first()->id, ['action' => 'void', 'remarks' => 'Payment entered against wrong period'])->assertSessionHasNoErrors();
        $this->assertSame(500.0, $due->fresh()->balance);
    }

    public function test_letters_preserve_issued_project_details_and_require_admin_review(): void
    {
        $chair = $this->user(); $admin = $this->user('admin');
        $project = $this->project($chair, ['status' => 'Ongoing', 'chair_id' => $chair->id]);
        $data = ['project_id' => $project->id, 'title' => 'School partnership', 'type' => 'External Partner Letter', 'data' => array_fill_keys(array_keys(ChapterForms::LETTER), 'School partnership details')];
        $this->actingAs($chair)->post('/records/letters', $data)->assertSessionHasNoErrors();
        $letter = Loi::firstOrFail();
        $this->post('/records/letters/'.$letter->id.'/transition', ['action' => 'sent'])->assertForbidden();
        $this->post('/records/letters/'.$letter->id.'/transition', ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/records/letters/'.$letter->id.'/transition', ['action' => 'approve', 'comments' => 'Approved for sending'])->assertSessionHasNoErrors();
        $project->update(['title' => 'Updated project title']);
        $this->assertSame('Community literacy project', $letter->fresh()->data['project_snapshot']['title']);
        $this->actingAs($chair)->post('/records/letters/'.$letter->id.'/transition', ['action' => 'sent'])->assertSessionHasNoErrors();
        $this->post('/records/letters/'.$letter->id.'/transition', ['action' => 'revise'])->assertSessionHasNoErrors();
        $this->assertSame('Sent', $letter->fresh()->versions[0]['status']);
        $this->get('/records/letters/'.$letter->id)->assertOk();
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
        $treasurer = $this->user('treasurer'); $project = $this->project($treasurer, ['status' => 'Approved']);
        $project->allocations()->create(['category' => 'Supplies', 'amount' => 1000]);
        $payload = ['reference' => 'EXP-002', 'project_id' => $project->id, 'transaction_date' => '2026-10-01', 'type' => 'Expense', 'category' => 'Supplies', 'account' => 'Chapter fund', 'counterparty' => 'Supplier', 'payment_method' => 'Cash', 'description' => 'Supplies', 'amount' => 1001];
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
        LedgerEntry::create(['reference' => 'INC-002', 'direction' => 'credit', 'status' => 'Posted', 'amount' => 5000, 'transaction_date' => '2026-10-10']);
        $this->assertEquals(2000, $report->fresh()->data['financial_snapshot']['income']);
        $this->actingAs($admin)->get('/records/reports/'.$report->id)->assertOk();
        $this->actingAs($member)->get('/records/reports/'.$report->id)->assertForbidden();
    }

    public function test_inactive_accounts_are_blocked_and_last_admin_cannot_be_deactivated(): void
    {
        $inactive = $this->user('member', ['status' => 'inactive']);
        $this->actingAs($inactive)->get('/dashboard')->assertRedirect('/login');
        $admin = $this->user('admin');
        $this->actingAs($admin)->put('/members/'.$admin->id, ['name' => $admin->name, 'email' => $admin->email, 'role' => 'member', 'status' => 'inactive'])->assertSessionHasErrors('role');
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
            'email' => $member->email,
            'photo' => UploadedFile::fake()->create('not-an-image.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors('photo');

        $this->actingAs($member)->put('/account', [
            'name' => $member->name,
            'email' => $member->email,
            'photo' => UploadedFile::fake()->createWithContent('portrait.png', $png),
        ])->assertSessionHasNoErrors();

        $firstPath = $member->fresh()->profile['photo_path'];
        $this->assertStringStartsWith('profile-photos/'.$member->id.'/', $firstPath);
        Storage::disk('local')->assertExists($firstPath);
        $this->get('/account/photo')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($other)->get('/account/photo')->assertNotFound();

        $this->actingAs($member)->put('/account', [
            'name' => $member->name,
            'email' => $member->email,
            'photo' => UploadedFile::fake()->createWithContent('replacement.png', $png),
        ])->assertSessionHasNoErrors();
        $secondPath = $member->fresh()->profile['photo_path'];
        Storage::disk('local')->assertExists($secondPath);
        Storage::disk('local')->assertMissing($firstPath);

        $this->put('/account', ['name' => $member->name, 'email' => $member->email, 'remove_photo' => 1])->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('photo_path', $member->fresh()->profile);
        Storage::disk('local')->assertMissing($secondPath);
    }
}

