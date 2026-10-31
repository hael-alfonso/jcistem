<?php

namespace Tests\Feature;

use App\Models\{BodDecision, Notification, Project, ProjectDocument, User};
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
            $this->assertSame(['PROJECTS', 'TASKS', 'FINANCIAL', 'MEMBERS', 'REPORTS & DOCUMENTS', 'FILES', 'CALENDAR', 'PROFILE', 'SETTINGS'], array_keys($navigation));
            $routes = collect($navigation)->flatten(1)->pluck(0);
            $this->assertTrue($routes->contains('account'));
            $this->assertTrue($routes->contains('documents'));
            $this->assertFalse($routes->contains('notifications'));
            $this->assertSame(1, $routes->filter(fn ($route) => $route === 'settings')->count());
            $labels = collect($navigation)->flatten(1)->pluck(1);
            $this->assertTrue($labels->contains('My Project Work'));
            $this->assertTrue($labels->contains('Submit New Project'));
            $this->assertFalse($labels->contains('My Chairmanships'));
            $this->assertFalse($labels->contains('Project Documents'));
            $this->assertFalse($labels->contains('User Accounts & Roles'));
            $this->assertSame($role === 'treasurer', $labels->contains('Master Ledger'));
            $this->assertSame(in_array($role, ['admin', 'bod'], true), $labels->contains('Manage Member Records'));
            $this->assertSame(in_array($role, ['admin', 'bod', 'treasurer'], true), $labels->contains('Review Queue'));
            $this->assertSame($role === 'admin', $labels->contains('System Controls'));
            $page = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
            $this->assertSame(2, substr_count($page, 'href="'.route('account').'"'));
            $this->assertSame(1, substr_count($page, 'href="'.route('settings').'"'));
            $this->assertSame(1, substr_count($page, 'href="'.route('notifications').'"'));
        }
    }

    public function test_topbar_notification_menu_only_shows_the_signed_in_users_updates(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $other = User::factory()->create(['role' => 'member', 'status' => 'active']);
        Notification::create(['user_id' => $member->id, 'title' => 'Your new task', 'body' => 'Please review the project plan.']);
        Notification::create(['user_id' => $other->id, 'title' => 'Private board update', 'body' => 'Only another member should see this.']);

        $this->actingAs($member)->get(route('dashboard'))->assertOk()
            ->assertSee('id="notification-dropdown"', false)
            ->assertSee('Your new task')
            ->assertDontSee('Private board update');
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

        Storage::disk('local')->put('project-documents/activity.mp4', "\0\0\0\x18ftypmp42video");
        $video = ProjectDocument::create(['project_id' => $project->id, 'uploaded_by' => $admin->id, 'title' => 'Activity clip', 'category' => 'Supporting document', 'path' => 'project-documents/activity.mp4', 'original_name' => 'activity.mp4']);
        $this->assertTrue($video->canPreviewInline());
        $this->actingAs($admin)->get(route('documents.preview', $video))->assertOk()->assertHeader('content-type', 'video/mp4');
        $this->call('GET', route('documents.preview', $video), server: ['HTTP_RANGE' => 'bytes=0-3'])->assertStatus(206);
        $this->actingAs($outsider)->get(route('documents.preview', $video))->assertForbidden();

        $wordPath = 'project-documents/proposal.docx';
        $word = new \PhpOffice\PhpWord\PhpWord();
        $word->addSection()->addText('Previewed proposal content');
        \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save(Storage::disk('local')->path($wordPath));
        $docx = ProjectDocument::create(['project_id' => $project->id, 'uploaded_by' => $admin->id, 'title' => 'Word proposal', 'category' => 'Concept submission', 'path' => $wordPath, 'original_name' => 'proposal.docx']);
        $wordPreview = $this->actingAs($admin)->get(route('documents.preview', $docx))->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="proposal.pdf"');
        $this->assertStringStartsWith('%PDF', file_get_contents($wordPreview->baseResponse->getFile()->getPathname()));
        $this->get(route('documents.original-preview', $docx))->assertOk()
            ->assertSee('docx-preview.min.js')
            ->assertSee(route('documents.download', $docx))
            ->assertHeader('content-security-policy');
        $this->actingAs($outsider)->get(route('documents.original-preview', $docx))->assertForbidden();
    }

    public function test_project_supporting_documents_accept_short_mp4_for_private_preview(): void
    {
        Storage::fake('local');
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $chair->id, 'chair_id' => $chair->id, 'title' => 'Video evidence project', 'area' => 'Community Impact', 'status' => 'Draft Concept']);

        $this->actingAs($chair)->post(route('documents.store', $project), [
            'title' => 'Workshop recap',
            'category' => 'Supporting document',
            'file' => UploadedFile::fake()->create('workshop.mp4', 100, 'video/mp4'),
        ])->assertSessionHasNoErrors();

        $video = ProjectDocument::where('project_id', $project->id)->firstOrFail();
        $this->assertSame('workshop.mp4', $video->original_name);
        Storage::disk('local')->assertExists($video->path);
        $this->get(route('projects.show', $project))->assertOk()->assertSee('data-preview-type="MP4"', false);
    }

    public function test_online_project_documents_download_as_filled_official_word_files(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $concept = array_fill_keys(array_keys(ChapterForms::CONCEPT), 'Completed concept value');
        $concept['letter_date'] = '2026-10-28';
        $concept['president_name'] = 'Juan Dela Cruz';
        $concept['sdg_number'] = '4';
        $concept['sdg_name'] = 'Quality Education';
        $project = Project::create([
            'created_by' => $member->id,
            'reference' => 'JCI-2026-00001',
            'title' => 'Carmona Learning Project',
            'area' => 'Community Impact',
            'status' => 'Draft Concept',
            'starts_on' => '2026-11-10',
            'ends_on' => '2026-11-11',
            'venue' => 'Carmona City',
            'proposed_budget' => '2500.00',
            'concept' => $concept,
            'concept_submission_method' => 'online',
        ]);

        $conceptResponse = $this->actingAs($member)->get(route('projects.word', [$project, 'concept']))
            ->assertOk()->assertDownload('jci-2026-00001-concept.docx');
        $conceptXml = $this->wordXml($conceptResponse->baseResponse->getFile()->getRealPath());
        $this->assertStringContainsString('Carmona Learning Project', $conceptXml);
        $this->assertStringContainsString('Quality Education', $conceptXml);
        $this->assertStringNotContainsString('[Project Name]', $conceptXml);

        $project->update([
            'status' => 'Approved',
            'chair_id' => $member->id,
            'proposal_submission_method' => 'online',
            'proposal' => array_fill_keys(array_keys(ChapterForms::PROPOSAL), 'Completed proposal value') + [
                'income_lines' => ['chapter_allocation' => 1000, 'sponsorships' => 1000, 'other_sources' => 500],
                'expense_lines' => ['logistics_venue' => 1000, 'marketing_materials' => 500, 'tokens_refreshments' => 500, 'other_expenses' => 500],
            ],
        ]);
        $proposalResponse = $this->get(route('projects.word', [$project, 'proposal']))
            ->assertOk()->assertDownload('jci-2026-00001-proposal.docx');
        $proposalXml = $this->wordXml($proposalResponse->baseResponse->getFile()->getRealPath());
        $this->assertStringContainsString('Carmona Learning Project', $proposalXml);
        $this->assertStringContainsString('2,500.00', $proposalXml);
        $this->assertStringNotContainsString('[Enter project title]', $proposalXml);
    }

    public function test_project_partnership_letters_are_uploaded_as_versioned_private_documents(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Partner project', 'area' => 'Community Impact', 'status' => 'Draft Concept']);
        $url = route('projects.partnerships.store', $project);

        foreach (['first.docx', 'revised.pdf'] as $index => $filename) {
            $this->actingAs($owner)->post($url, [
                'title' => 'ABC Foundation partnership letter',
                'file' => UploadedFile::fake()->create($filename, 20, str_ends_with($filename, '.pdf') ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])->assertSessionHasNoErrors();
            $document = ProjectDocument::where('submission_stage', 'partnership')->latest('version')->firstOrFail();
            $this->assertSame($index + 1, $document->version);
            $this->assertSame('External Partnership Letter', $document->category);
            Storage::disk('local')->assertExists($document->path);
        }

        $versions = ProjectDocument::where('submission_stage', 'partnership')->orderBy('version')->get();
        $this->assertSame($versions[0]->id, $versions[1]->replaces_document_id);
        $this->get(route('projects.show', $project))->assertOk()->assertSee('External partnership letters')->assertSee('Version 2');
    }

    private function wordXml(string $path): string
    {
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertIsString($xml);
        return $xml;
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

    public function test_chair_can_add_private_attendance_and_typed_evaluation(): void
    {
        Storage::fake('local');
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $outsider = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $chair->id, 'chair_id' => $chair->id, 'status' => 'Approved', 'title' => 'Evidence project', 'area' => 'Community Impact']);
        BodDecision::create(['project_id' => $project->id, 'recorded_by' => $admin->id, 'meeting_date' => '2026-11-01', 'decision' => 'approved', 'resolution_number' => 'RES-EVIDENCE-1', 'evidence_path' => 'bod-evidence.pdf', 'comments' => 'Approved']);

        $this->actingAs($chair)->post(route('projects.evidence.store', $project), [
            'type' => 'attendance', 'title' => 'Workshop attendance', 'activity_on' => '2026-11-02',
            'photos' => [UploadedFile::fake()->image('attendance.jpg')],
        ])->assertSessionHasNoErrors();
        $photo = ProjectDocument::where('submission_stage', 'attendance')->firstOrFail();
        Storage::disk('local')->assertExists($photo->path);
        $this->get(route('documents.preview', $photo))->assertOk();
        $this->get(route('projects.show', $project))->assertOk()->assertSee('Workshop attendance');

        $this->post(route('projects.evidence.store', $project), [
            'type' => 'evaluation', 'title' => 'Day one review', 'activity_on' => '2026-11-02',
            'notes' => 'Participants completed the activity.',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('project_documents', ['project_id' => $project->id, 'submission_stage' => 'evaluation', 'notes' => 'Participants completed the activity.']);
        $this->get(route('documents', ['type' => 'evidence']))->assertOk()->assertSee('Workshop attendance')->assertSee('Day one review');

        $project->update(['status' => 'Admin Review']);
        $this->actingAs($outsider)->get(route('documents.preview', $photo))->assertForbidden();
        $this->get(route('documents', ['type' => 'evidence']))->assertDontSee('Workshop attendance');
        $this->post(route('projects.evidence.store', $project), ['type' => 'evaluation', 'title' => 'Unauthorized', 'activity_on' => '2026-11-02', 'notes' => 'No'])->assertForbidden();
    }

    public function test_only_admin_can_change_system_controls(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($member)->get(route('pms.settings'))->assertForbidden();
        $this->put(route('pms.settings.update'), ['report_chapter_name' => 'JCI Carmona', 'workspace_notice' => 'Chapter update', 'evidence_photo_limit' => 3])->assertForbidden();

        $this->actingAs($admin)->put(route('pms.settings.update'), [
            'report_chapter_name' => 'JCI Carmona', 'workspace_notice' => 'Chapter update', 'evidence_photo_limit' => 3,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('chapter_settings', ['key' => 'workspace_notice', 'value' => 'Chapter update']);
        $this->get(route('dashboard'))->assertOk()->assertSee('Chapter update');
    }

    public function test_workspace_theme_can_follow_system_override_and_reset(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);

        $this->actingAs($admin)->put(route('pms.settings.update'), [
            'report_chapter_name' => 'JCI Carmona', 'workspace_notice' => '',
            'evidence_photo_limit' => 8, 'workspace_theme' => 'navy',
        ])->assertSessionHasNoErrors();

        $this->actingAs($member)->get(route('settings'))->assertOk()->assertSee('workspace-theme-navy');
        $this->put(route('settings.update'), [
            'profile' => ['preferences' => [
                'start_page' => 'dashboard', 'interface_density' => 'comfortable',
                'navigation_mode' => 'accordion', 'reduce_motion' => '0', 'color_theme' => 'teal',
            ]],
        ])->assertSessionHasNoErrors();
        $this->get(route('settings'))->assertOk()->assertSee('workspace-theme-teal');

        $this->put(route('settings.update'), ['reset_preferences' => 1])->assertSessionHasNoErrors();
        $this->get(route('settings'))->assertOk()->assertSee('workspace-theme-navy');
        $this->assertSame('system', $member->fresh()->profile['preferences']['color_theme']);
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
        $proposal['president_name'] = 'Chapter President';
        $proposal['income_lines'] = ['chapter_allocation' => '1000.00', 'sponsorships' => '1500.00'];
        $proposal['expense_lines'] = ['logistics_venue' => '1000.00', 'marketing_materials' => '500.00', 'tokens_refreshments' => '1000.00'];
        $this->actingAs($chair)->put('/projects/'.$project->id, [
            'title' => $project->title, 'area' => $project->area,
            'starts_on' => '2026-11-10', 'ends_on' => '2026-11-12',
            'venue' => $project->venue, 'proposed_budget' => '2500.00',
            'proposal_submission_method' => 'online', 'proposal' => $proposal,
        ])->assertSessionHasNoErrors();

        $saved = $project->fresh()->proposal;
        $this->assertSame('Chapter President', $saved['president_name']);
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
            ->assertSee('Recipient and SDG alignment')
            ->assertSee('Project overview')
            ->assertSee('Resource requirements and signatory')
            ->assertSee(route('templates.reference.download', 'internal-project-letter'));
    }

    public function test_itemized_project_expenses_calculate_and_store_the_proposed_budget(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->actingAs($member)->post('/projects', [
            'title' => 'Itemized learning project',
            'area' => 'Community Impact',
            'starts_on' => '2026-11-10',
            'venue' => 'Carmona City',
            'proposed_budget' => '999999.00',
            'concept_submission_method' => 'online',
            'budget_items' => [
                ['category' => 'materials_supplies', 'description' => 'Learning kits', 'quantity' => '10', 'unit_cost' => '100.00'],
                ['category' => 'food_refreshments', 'description' => 'Volunteer meals', 'quantity' => '5', 'unit_cost' => '50.00'],
            ],
        ])->assertSessionHasNoErrors();

        $project = Project::firstOrFail();
        $this->assertSame('1250.00', $project->proposed_budget);
        $this->assertCount(2, $project->budget_items);
        $this->assertSame('1000.00', $project->budget_items[0]['amount']);
        $this->actingAs($member)->get(route('projects.show', $project))->assertOk()
            ->assertSee('Itemized proposed expenses')->assertSee('Learning kits')->assertSee('1,250.00');
    }

    public function test_project_pages_keep_one_progress_indicator_and_the_project_menu_active(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Community project', 'area' => 'Community Impact', 'status' => 'Draft Concept']);
        $this->actingAs($owner);
        $page = $this->get(route('projects.show', $project))->assertOk();
        $this->assertSame(1, substr_count($page->getContent(), '<progress '));
        $this->assertTrue(PmsNavigation::active(['projects', 'All Chapter Projects', 'briefcase', []]));
        $page->assertSee('Account menu')->assertSee('account-dropdown')->assertDontSee('tracker-steps');

        $this->get('/projects?filter=Archived')->assertOk()
            ->assertSee('Project Archive')->assertSee('value="Archived" selected', false);
    }
}
