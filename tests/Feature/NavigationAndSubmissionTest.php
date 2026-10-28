<?php

namespace Tests\Feature;

use App\Models\{BodDecision, Project, ProjectDocument, User};
use App\Services\ProposalWorkflowService;
use App\Support\PmsNavigation;
use App\Support\ChapterForms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NavigationAndSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_navigation_is_concise_and_role_scoped(): void
    {
        foreach (['admin', 'bod', 'treasurer', 'member'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $navigation = PmsNavigation::for($user);
            $this->assertSame(['PROJECTS', 'TASKS', 'FINANCIAL', 'MEMBERS', 'REPORTS & DOCUMENTS', 'CALENDAR', 'NOTIFICATIONS', 'ACCOUNT'], array_keys($navigation));
            $labels = collect($navigation)->flatten(1)->pluck(1);
            $this->assertTrue($labels->contains('My Project Work'));
            $this->assertTrue($labels->contains('Submit New Project'));
            $this->assertFalse($labels->contains('My Chairmanships'));
            $this->assertFalse($labels->contains('Project Documents'));
            $this->assertFalse($labels->contains('User Accounts & Roles'));
            $this->assertSame($role === 'treasurer', $labels->contains('Master Ledger'));
            $this->assertSame(in_array($role, ['admin', 'bod'], true), $labels->contains('Manage Member Records'));
            $this->assertSame(in_array($role, ['admin', 'bod', 'treasurer'], true), $labels->contains('Review Queue'));
            $this->assertSame($role === 'admin', $labels->contains('System Configuration'));
        }
    }

    public function test_admin_can_review_own_submission_and_it_appears_in_the_review_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create([
            'created_by' => $admin->id, 'title' => 'Admin community project',
            'area' => 'Community Impact', 'status' => 'Draft Concept',
            'starts_on' => '2026-11-10', 'venue' => 'Carmona City',
            'proposed_budget' => '2500.00',
            'concept' => array_fill_keys(array_keys(ChapterForms::CONCEPT), 'Complete project concept'),
        ]);

        $this->actingAs($admin)->post('/projects/'.$project->id.'/transition', ['action' => 'submit_concept'])
            ->assertSessionHasNoErrors();
        $this->get('/dashboard')->assertOk()->assertSee('Admin community project');
        $this->get('/projects?filter=review')->assertOk()->assertSee('Admin community project')->assertSee('Review project');
        $this->get('/projects/'.$project->id)->assertOk()->assertSee('Pass to Treasurer Review')->assertSee('both project owner and Admin reviewer');
        $this->post('/projects/'.$project->id.'/transition', ['action' => 'endorse', 'comments' => 'Admin compliance complete'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Treasurer Review', $project->fresh()->status);
    }

    public function test_project_submission_files_can_be_previewed_privately(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $outsider = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $admin->id, 'title' => 'Preview project', 'area' => 'Community Impact', 'status' => 'Admin Review']);

        Storage::disk('local')->put('project-documents/proposal.pdf', '%PDF-1.4 preview');
        $pdf = ProjectDocument::create(['project_id' => $project->id, 'uploaded_by' => $admin->id, 'title' => 'Proposal', 'category' => 'Concept submission', 'path' => 'project-documents/proposal.pdf', 'original_name' => 'proposal.pdf']);
        $this->actingAs($admin)->get(route('documents.preview', $pdf))->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=proposal.pdf');
        $this->actingAs($outsider)->get(route('documents.preview', $pdf))->assertForbidden();

        $wordPath = 'project-documents/proposal.docx';
        $word = new \PhpOffice\PhpWord\PhpWord();
        $word->addSection()->addText('Previewed proposal content');
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save(Storage::disk('local')->path($wordPath));
        $docx = ProjectDocument::create(['project_id' => $project->id, 'uploaded_by' => $admin->id, 'title' => 'Word proposal', 'category' => 'Concept submission', 'path' => $wordPath, 'original_name' => 'proposal.docx']);
        $wordPreview = $this->actingAs($admin)->get(route('documents.preview', $docx))->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="proposal.pdf"');
        $this->assertStringStartsWith('%PDF', $wordPreview->getContent());
    }

    public function test_uploaded_concept_and_proposal_forms_use_the_normal_workflow_and_keep_versions(): void
    {
        Storage::fake('local');
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->actingAs($member)->post('/projects', [
            'title' => 'Uploaded proposal project',
            'area' => 'Community Impact',
            'starts_on' => '2026-11-10',
            'venue' => 'Carmona City',
            'proposed_budget' => '2500.00',
            'concept_submission_method' => 'upload',
            'submission_file' => UploadedFile::fake()->create('concept-proposal.pdf', 20, 'application/pdf'),
            'revision_note' => 'Initial signed concept.',
        ])->assertSessionHasNoErrors();

        $project = Project::firstOrFail();
        $first = ProjectDocument::where('submission_stage', 'concept')->firstOrFail();
        $this->assertSame('upload', $project->concept_submission_method);
        $this->assertSame(1, $first->version);
        Storage::disk('local')->assertExists($first->path);
        app(ProposalWorkflowService::class)->transition($project, $member, 'submit_concept', null);
        $this->assertSame('Admin Review', $project->fresh()->status);

        $project->update(['status' => 'Needs Revision']);
        $this->actingAs($member)->put('/projects/'.$project->id, [
            'title' => $project->title,
            'area' => $project->area,
            'starts_on' => '2026-11-10',
            'venue' => 'Carmona City',
            'proposed_budget' => '2500.00',
            'concept_submission_method' => 'upload',
            'submission_file' => UploadedFile::fake()->create('concept-proposal-v2.pdf', 20, 'application/pdf'),
            'revision_note' => 'Addressed the review comments.',
        ])->assertSessionHasNoErrors();
        $second = ProjectDocument::where('submission_stage', 'concept')->latest('version')->firstOrFail();
        $this->assertSame(2, $second->version);
        $this->assertSame($first->id, $second->replaces_document_id);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project->update(['status' => 'Approved', 'chair_id' => $member->id]);
        BodDecision::create(['project_id' => $project->id, 'recorded_by' => $admin->id, 'meeting_date' => '2026-11-01', 'decision' => 'approved', 'resolution_number' => 'RES-UPLOAD-1', 'evidence_path' => 'bod-evidence.pdf', 'comments' => 'Approved']);
        $this->actingAs($member)->put('/projects/'.$project->id, [
            'title' => $project->title,
            'area' => $project->area,
            'starts_on' => '2026-11-10',
            'venue' => 'Carmona City',
            'proposed_budget' => '2500.00',
            'proposal_submission_method' => 'upload',
            'submission_file' => UploadedFile::fake()->create('project-proposal-form.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ])->assertSessionHasNoErrors();
        $project = $project->fresh();
        $this->assertSame('upload', $project->proposal_submission_method);
        app(ProposalWorkflowService::class)->transition($project, $member, 'lock_plan', null);
        $this->assertSame('Plan Locked', $project->fresh()->status);
    }

    public function test_online_proposal_uses_official_sections_and_saves_itemized_finances(): void
    {
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create([
            'created_by' => $chair->id, 'chair_id' => $chair->id, 'status' => 'Approved',
            'title' => 'Community skills workshop', 'area' => 'Community Impact',
            'starts_on' => '2026-11-10', 'ends_on' => '2026-11-12',
            'venue' => 'Carmona City', 'proposed_budget' => '2500.00',
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        BodDecision::create([
            'project_id' => $project->id, 'recorded_by' => $admin->id,
            'meeting_date' => '2026-11-01', 'decision' => 'approved',
            'resolution_number' => 'RES-PPF-1', 'evidence_path' => 'bod-evidence.pdf',
            'comments' => 'Approved',
        ]);

        $this->actingAs($chair)->get('/projects/'.$project->id.'/edit')->assertOk()
            ->assertSee('Background and rationale')
            ->assertSee('Estimated JCI members and their functions')
            ->assertSee('Estimated income')
            ->assertSee(route('templates.reference.download', 'project-proposal-form'));

        $proposal = array_fill_keys(array_keys(ChapterForms::PROPOSAL_REQUIRED), 'Complete project plan');
        $proposal['income_lines'] = ['chapter_allocation' => '1000.00', 'sponsorships' => '1500.00'];
        $proposal['expense_lines'] = ['logistics_venue' => '1000.00', 'marketing_materials' => '500.00', 'tokens_refreshments' => '1000.00'];
        $this->actingAs($chair)->put('/projects/'.$project->id, [
            'title' => $project->title, 'area' => $project->area,
            'starts_on' => '2026-11-10', 'ends_on' => '2026-11-12',
            'venue' => $project->venue, 'proposed_budget' => '2500.00',
            'proposal_submission_method' => 'online', 'proposal' => $proposal,
        ])->assertSessionHasNoErrors();

        $saved = $project->fresh()->proposal;
        $this->assertSame('2500.00', $saved['projected_income']);
        $this->assertSame('1500.00', $saved['income_lines']['sponsorships']);
        $this->assertSame('1000.00', $saved['expense_lines']['tokens_refreshments']);
        $this->actingAs($chair)->get('/projects/'.$project->id.'/proposal')->assertOk()
            ->assertSee('Estimated expenditures')
            ->assertSee('Approval and signatures');
        $this->actingAs($chair)->post('/projects/'.$project->id.'/transition', ['action' => 'lock_plan'])->assertSessionHasNoErrors();
        $this->assertSame('Plan Locked', $project->fresh()->status);
    }

    public function test_submit_proposal_starts_with_the_internal_project_letter_format(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->actingAs($member)->get('/projects/create')->assertOk()
            ->assertSee('Internal project proposal letter')
            ->assertSee('Purpose and SDG alignment')
            ->assertSee('Project overview')
            ->assertSee('Resource requirements and request')
            ->assertSee(route('templates.reference.download', 'internal-project-letter'));
    }
}
