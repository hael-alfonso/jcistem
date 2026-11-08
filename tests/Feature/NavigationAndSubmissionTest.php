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
            $this->assertSame(['PROJECTS', 'TASKS', 'FINANCE', 'MEMBERS', 'REPORTS & DOCUMENTS', 'PARTNERSHIPS', 'CALENDAR', 'NOTIFICATIONS', 'SETTINGS'], array_keys($navigation));
            $this->assertSame(['My Projects', 'All Projects', 'Create Project', ...($role === 'member' ? [] : ['Project Reviews'])], array_column($navigation['PROJECTS'], 1));
            $this->assertSame(['My Tasks', 'Task Monitoring'], array_column($navigation['TASKS'], 1));
            $this->assertSame(['filter' => 'mine'], $navigation['PROJECTS'][0][3]);
            if ($role !== 'member') $this->assertSame(['filter' => 'review'], $navigation['PROJECTS'][3][3]);
            $this->assertSame(['mine' => 1], $navigation['TASKS'][0][3]);
            $this->assertSame(['mine' => 0], $navigation['TASKS'][1][3]);
            $this->assertSame('Finance', PmsNavigation::label('FINANCE'));
            $routes = collect($navigation)->flatten(1)->pluck(0);
            $this->assertFalse($routes->contains('account')); // Profile is the separate sidebar account link.
            $this->assertTrue($routes->contains('documents'));
            $this->assertTrue($routes->contains('notifications'));
            $this->assertSame(1, $routes->filter(fn ($route) => $route === 'settings')->count());
            $labels = collect($navigation)->flatten(1)->pluck(1);
            $this->assertTrue($labels->contains('Project Finances'));
            $this->assertTrue($labels->contains($role === 'treasurer' ? 'Claims to Review' : 'My Expense Claims'));
            $this->assertTrue($labels->contains($role === 'treasurer' ? 'Member Dues' : 'My Dues'));
            $this->assertFalse($labels->contains('Completed Project Library'));
            $this->assertFalse($labels->contains('Archived Projects'));
            $this->assertFalse($labels->contains('My Chairmanships'));
            $this->assertFalse($labels->contains('Project Documents'));
            $this->assertFalse($labels->contains('User Accounts & Roles'));
            $this->assertSame($role === 'treasurer', $labels->contains('Treasurer Ledger'));
            $this->assertSame(in_array($role, ['admin', 'bod'], true), $labels->contains('Manage Member Records'));
            $this->assertSame(in_array($role, ['admin', 'bod', 'treasurer'], true), $labels->contains('Project Reviews'));
            $this->assertSame($role === 'admin', $labels->contains('System Controls'));
            $page = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
            $this->assertSame(2, substr_count($page, 'href="'.route('account').'"'));
            $this->assertSame(2, substr_count($page, 'href="'.route('settings').'"'));
            $this->assertSame(2, substr_count($page, 'href="'.route('notifications').'"'));
            $this->assertStringContainsString('My account', $page);
            $this->assertSame(2, substr_count($page, 'action="'.route('logout').'"'));
        }
    }

    public function test_default_task_page_highlights_the_matching_sidebar_destination(): void
    {
        foreach (['member', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $tasksPage = $this->actingAs($user)->get(route('tasks'))->assertOk()->getContent();
            $defaultMine = $role === 'member' ? 1 : 0;
            $this->assertMatchesRegularExpression(
                '/<a class="side-link is-active" href="'.preg_quote(route('tasks', ['mine' => $defaultMine]), '/').'"\s+aria-current="page"/',
                $tasksPage
            );
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

    public function test_important_notifications_can_be_marked_read_in_bulk_and_stay_private(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $other = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $important = Notification::create(['user_id' => $member->id, 'title' => 'Project decision', 'body' => 'Please review the outcome.', 'url' => '/projects', 'requires_open' => true]);
        $routine = Notification::create(['user_id' => $member->id, 'title' => 'Calendar reminder', 'body' => 'Meeting tomorrow.', 'url' => '/calendar']);

        $page = $this->actingAs($member)->get(route('notifications', ['filter' => 'important']))->assertOk()
            ->assertSee('Project decision')->assertSee('Mark all notifications as read')->getContent();
        $this->assertSame(1, substr_count($page, '<tr class="is-unread"'));
        $this->assertStringContainsString('notification-important-table', $page);
        $this->post(route('notifications.read'))->assertRedirect();
        $this->assertNotNull($routine->fresh()->read_at);
        $this->assertNotNull($important->fresh()->read_at);
        $this->actingAs($other)->post(route('notifications.open', $important))->assertForbidden();
        $this->assertNotNull($important->fresh()->read_at);
        $this->actingAs($member)->post(route('notifications.open', $important))->assertRedirect('/projects');
        $this->assertNotNull($important->fresh()->read_at);
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
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'title' => $project->title, 'requires_open' => true]);
        $this->get('/dashboard')->assertOk()->assertSee('Admin community project');
        $this->get('/projects?filter=review')->assertOk()->assertSee('Admin community project')->assertSee('aria-label="Open project for review: '.$project->title.'"', false);
        $this->get('/projects/'.$project->id)->assertOk()->assertSee('Send for Budget Review')->assertSee('both project owner and Admin reviewer');
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

    public function test_spreadsheets_and_slides_use_private_browser_preview(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $outsider = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Office file project', 'area' => 'Community Impact', 'status' => 'Admin Review']);

        foreach (['budget.xlsx', 'presentation.pptx'] as $filename) {
            $path = 'project-documents/'.$filename;
            Storage::disk('local')->put($path, 'private office bytes');
            $document = ProjectDocument::create(['project_id' => $project->id, 'uploaded_by' => $owner->id, 'title' => $filename, 'category' => 'Supporting document', 'path' => $path, 'original_name' => $filename]);

            $this->assertTrue($document->canPreviewInline());
            $this->actingAs($owner)->get(route('documents.original-preview', $document))->assertOk()
                ->assertSee('private-office-viewer.js')
                ->assertSee(route('documents.download', $document))
                ->assertHeader('content-security-policy');
            $this->get(route('documents.preview', $document))->assertRedirect(route('documents.original-preview', $document));
            $this->actingAs($outsider)->get(route('documents.original-preview', $document))->assertForbidden();
        }
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
        $project->update(['budget_items' => \App\Support\ProjectBudget::normalize([['category' => 'materials_supplies', 'description' => 'Uploaded proposal expenses', 'quantity' => 1, 'unit_cost' => 2500]])]);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'status' => 'active']);
        Storage::disk('local')->put('signed-full.pdf', 'signed approval');
        app(ProposalWorkflowService::class)->transition($project, $member, 'submit_full_proposal', null);
        app(ProposalWorkflowService::class)->transition($project, $treasurer, 'review_full_budget', 'Verified full budget');
        app(ProposalWorkflowService::class)->transition($project, $admin, 'approve_full_proposal', 'Approved full proposal', ['meeting_date' => '2026-11-01', 'resolution_number' => 'RES-UPLOAD-2', 'evidence_path' => 'signed-full.pdf']);
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
        $project->update(['budget_items' => \App\Support\ProjectBudget::normalize([['category' => 'materials_supplies', 'description' => 'Workshop materials', 'quantity' => 1, 'unit_cost' => 2500]])]);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'status' => 'active']);
        Storage::fake('local');
        $this->actingAs($chair)->post('/projects/'.$project->id.'/transition', ['action' => 'submit_full_proposal'])->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post('/projects/'.$project->id.'/transition', ['action' => 'review_full_budget', 'comments' => 'Detailed budget verified'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/projects/'.$project->id.'/transition', ['action' => 'approve_full_proposal', 'comments' => 'Approved at meeting', 'meeting_date' => '2026-11-01', 'resolution_number' => 'RES-PPF-2', 'evidence' => UploadedFile::fake()->create('signed-full.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->actingAs($chair)->post('/projects/'.$project->id.'/transition', ['action' => 'lock_plan'])->assertSessionHasNoErrors();
        $this->assertSame('Plan Locked', $project->fresh()->status);
    }

    public function test_submit_proposal_starts_with_the_internal_project_letter_format(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->actingAs($member)->get('/projects/create')->assertOk()
            ->assertSee('Project Concept Letter')
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

        $this->put(route('projects.update', $project), [
            'title' => $project->title, 'area' => $project->area,
            'proposed_budget' => '9999.00', 'concept_submission_method' => 'online',
        ])->assertSessionHasNoErrors();
        $this->assertSame('1250.00', $project->fresh()->proposed_budget);
        $this->assertCount(2, $project->fresh()->budget_items);

        $this->put(route('projects.update', $project), [
            'title' => $project->title, 'area' => $project->area,
            'proposed_budget' => '1250.00', 'budget_items' => [], 'concept_submission_method' => 'online',
        ])->assertSessionHasNoErrors();
        $this->assertSame('0.00', $project->fresh()->proposed_budget);
        $this->assertSame([], $project->fresh()->budget_items);
    }

    public function test_full_proposal_under_review_is_visible_to_reviewers_but_not_unrelated_members(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $other = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create([
            'created_by' => $owner->id, 'title' => 'Proposal awaiting board review',
            'chair_id' => $chair->id, 'area' => 'Community Impact',
            'status' => 'Submitted for Formal Approval',
        ]);
        ProjectDocument::create([
            'project_id' => $project->id, 'uploaded_by' => $chair->id,
            'title' => 'Review proposal file', 'category' => 'Project proposal',
            'path' => 'project-submissions/review.docx', 'original_name' => 'review.docx',
        ]);

        $this->actingAs($other)->get(route('projects.show', $project))->assertForbidden();
        $this->get(route('projects'))->assertOk()->assertDontSee($project->title);
        $this->get(route('documents'))->assertOk()->assertDontSee('Review proposal file');
        $this->actingAs($chair)->get(route('projects.show', $project))->assertOk()->assertSee($project->title);
        $this->get(route('projects', ['filter' => 'mine']))->assertOk()->assertSee($project->title);
        $this->get(route('documents'))->assertOk()->assertSee('Review proposal file');
        $this->actingAs($admin)->get(route('projects.show', $project))->assertOk()->assertSee($project->title);
    }

    public function test_project_pages_keep_one_progress_indicator_and_the_project_menu_active(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Community project', 'area' => 'Community Impact', 'status' => 'Draft Concept']);
        $this->actingAs($owner);
        $page = $this->get(route('projects.show', $project))->assertOk();
        $this->assertSame(1, substr_count($page->getContent(), '<progress '));
        $this->assertTrue(PmsNavigation::active(['projects', 'All Projects', 'briefcase', []]));
        $page->assertSee('Account menu')->assertSee('account-dropdown')->assertDontSee('tracker-steps');

        $this->get(route('projects.archived'))->assertRedirect(route('projects.completed'));
        $this->get(route('projects.completed'))->assertOk()
            ->assertSee('Project Library')->assertSee('aria-current="page"', false);
        $this->assertTrue(PmsNavigation::active(['projects', 'All Projects', 'briefcase', []]));
        $this->assertFalse(PmsNavigation::active(['projects', 'My Projects', 'user', ['filter' => 'mine']]));

        $this->get(route('projects', ['filter' => 'mine']))->assertOk();
        $this->assertTrue(PmsNavigation::active(['projects', 'My Projects', 'user', ['filter' => 'mine']]));
        $this->assertFalse(PmsNavigation::active(['projects', 'All Projects', 'briefcase', []]));

        $this->get(route('projects.create'))->assertOk();
        $this->assertTrue(PmsNavigation::active(['projects.create', 'Create Project', 'filePlus', []]));
        $this->assertFalse(PmsNavigation::active(['projects', 'All Projects', 'briefcase', []]));
    }
}
