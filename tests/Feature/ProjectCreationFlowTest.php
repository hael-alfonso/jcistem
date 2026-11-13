<?php

namespace Tests\Feature;

use App\Models\{Project, ProjectDocument, Task, User};
use App\Support\{ChapterForms, ProjectFlow};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectCreationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_cost_idea_can_be_saved_incomplete_but_only_submitted_when_ready(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->actingAs($owner)->get(route('projects.create'))->assertOk()->assertSee('No project expenses planned');

        $this->post(route('projects.store'), [
            'title' => 'Zero-cost volunteer day', 'area' => 'Community Impact',
            'no_budget' => '1', 'proposed_budget' => '0.00', 'concept_submission_method' => 'online',
        ])->assertSessionHasNoErrors();

        $project = Project::firstOrFail();
        $this->assertSame('Draft Concept', $project->status);
        $this->assertSame('0.00', $project->proposed_budget);
        $this->assertSame([], $project->budget_items);
        $this->post(route('projects.transition', $project), ['action' => 'submit_concept'])
            ->assertSessionHasErrors();

        $this->put(route('projects.update', $project), [
            'title' => $project->title, 'area' => $project->area,
            'starts_on' => '2026-11-20', 'venue' => 'Carmona',
            'no_budget' => '1', 'proposed_budget' => '0.00', 'concept_submission_method' => 'online',
            'concept' => array_merge(array_fill_keys(array_keys(ChapterForms::CONCEPT_REQUIRED), 'Volunteer-led community activity'), ['sdg_number' => '4']),
        ])->assertSessionHasNoErrors();
        $this->post(route('projects.transition', $project), ['action' => 'submit_concept'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Admin Review', $project->fresh()->status);
    }

    public function test_upload_draft_requires_a_file_only_when_submitted_and_rejects_missing_storage(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->actingAs($owner)->post(route('projects.store'), [
            'title' => 'Uploaded concept draft', 'area' => 'Community Impact',
            'starts_on' => '2026-11-20', 'venue' => 'Carmona',
            'no_budget' => '1', 'proposed_budget' => '0.00', 'concept_submission_method' => 'upload',
        ])->assertSessionHasNoErrors();
        $project = Project::firstOrFail();
        $this->post(route('projects.transition', $project), ['action' => 'submit_concept'])
            ->assertSessionHasErrors('action');

        $payload = [
            'title' => $project->title, 'area' => $project->area,
            'starts_on' => '2026-11-20', 'venue' => 'Carmona',
            'no_budget' => '1', 'proposed_budget' => '0.00', 'concept_submission_method' => 'upload',
            'submission_file' => UploadedFile::fake()->create('concept.pdf', 8, 'application/pdf'),
        ];
        $this->put(route('projects.update', $project), $payload)->assertSessionHasNoErrors();
        $document = ProjectDocument::where('project_id', $project->id)->firstOrFail();
        Storage::disk('local')->delete($document->path);
        $this->post(route('projects.transition', $project), ['action' => 'submit_concept'])
            ->assertSessionHasErrors('action');

        $payload['submission_file'] = UploadedFile::fake()->create('concept-v2.pdf', 8, 'application/pdf');
        $this->put(route('projects.update', $project), $payload)->assertSessionHasNoErrors();
        $revision = $project->fresh()->revision;
        $withoutFile = $payload;
        unset($withoutFile['submission_file']);
        $this->put(route('projects.update', $project), $withoutFile)->assertSessionHasNoErrors();
        $this->assertSame($revision, $project->fresh()->revision);

        $withoutFile['title'] = 'Updated uploaded concept';
        $this->put(route('projects.update', $project), $withoutFile)->assertSessionHasNoErrors();
        $this->assertSame($revision + 1, $project->fresh()->revision);
        $this->post(route('projects.transition', $project), ['action' => 'submit_concept'])
            ->assertSessionHasErrors('action');

        $withoutFile['submission_file'] = UploadedFile::fake()->create('updated-concept.pdf', 8, 'application/pdf');
        $this->put(route('projects.update', $project), $withoutFile)->assertSessionHasNoErrors();
        $this->post(route('projects.transition', $project), ['action' => 'submit_concept'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Admin Review', $project->fresh()->status);
    }

    public function test_zero_cost_project_can_complete_both_review_cycles_before_delivery(): void
    {
        Storage::fake('local');
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'status' => 'active']);
        $project = Project::create([
            'created_by' => $chair->id, 'title' => 'Volunteer mentorship', 'area' => 'Individual Development',
            'status' => 'Draft Concept', 'starts_on' => today()->addDays(15), 'venue' => 'Carmona',
            'proposed_budget' => '0.00', 'budget_items' => [],
            'concept' => array_fill_keys(array_keys(ChapterForms::CONCEPT_REQUIRED), 'Volunteer-led mentorship'),
        ]);
        $url = route('projects.transition', $project);
        $this->actingAs($chair)->post($url, ['action' => 'submit_concept'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($url, ['action' => 'endorse', 'comments' => 'Ready for budget review'])->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($url, ['action' => 'budget_review', 'comments' => 'No spending required'])->assertSessionHasNoErrors();
        $this->assertSame('Proposal preparation', ProjectFlow::statusLabel($project->fresh()));

        $proposalBase = [
            'title' => $project->title, 'area' => $project->area,
            'starts_on' => today()->addDays(15)->toDateString(), 'venue' => 'Carmona',
            'no_budget' => '1', 'proposed_budget' => '0.00', 'proposal_submission_method' => 'online',
        ];
        $this->actingAs($chair)->put(route('projects.update', $project), $proposalBase + ['proposal' => 'invalid shape'])
            ->assertSessionHasErrors('proposal');
        $this->put(route('projects.update', $project), $proposalBase + [
            'proposal' => ['income_lines' => ['unknown_source' => 'not a number']],
        ])->assertSessionHasErrors('proposal.income_lines');
        $this->put(route('projects.update', $project), $proposalBase + [
            'proposal' => ['income_lines' => ['chapter_allocation' => '999999999.99', 'sponsorships' => '999999999.99']],
        ])->assertSessionHasErrors('proposal.income_lines');

        $this->actingAs($chair)->put(route('projects.update', $project), [
            ...$proposalBase,
            'proposal' => array_fill_keys(array_keys(ChapterForms::PROPOSAL_REQUIRED), 'Volunteer-led delivery and evaluation'),
        ])->assertSessionHasNoErrors();
        $this->post($url, ['action' => 'submit_full_proposal'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($url, [
            'action' => 'return_full_proposal', 'comments' => 'Clarify how results will be measured',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($chair)->post($url, ['action' => 'submit_full_proposal'])->assertSessionHasErrors('action');
        $proposal = $project->fresh()->proposal;
        $proposal[array_key_first(ChapterForms::PROPOSAL_REQUIRED)] = 'Volunteer-led delivery with attendance and outcome tracking';
        $this->put(route('projects.update', $project), [...$proposalBase, 'proposal' => $proposal])
            ->assertSessionHasNoErrors();
        $this->post($url, ['action' => 'submit_full_proposal'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($treasurer)->post($url, ['action' => 'review_full_budget', 'comments' => 'Zero-cost plan verified'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($url, [
            'action' => 'approve_full_proposal', 'comments' => 'Final plan approved',
            'meeting_date' => today()->toDateString(),
            'evidence' => UploadedFile::fake()->create('signed-proposal.pdf', 8, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Proposal approved', ProjectFlow::statusLabel($project->fresh()));
        $this->actingAs($chair)->post($url, ['action' => 'lock_plan'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Plan Locked', $project->fresh()->status);
        $this->assertSame([], $project->fresh()->budget_items);
    }

    public function test_itemized_total_is_checked_after_calculation_and_line_ids_must_be_unique(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $base = ['title' => 'Budget validation', 'area' => 'Community Impact',
            'proposed_budget' => '0.00', 'concept_submission_method' => 'online'];
        $line = ['category' => 'materials_supplies', 'description' => 'Materials', 'quantity' => '2.00', 'unit_cost' => '999999999.99'];

        $this->actingAs($owner)->post(route('projects.store'), $base + ['budget_items' => [$line]])
            ->assertSessionHasErrors('budget_items');
        $this->assertSame(0, Project::count());

        $id = (string) Str::uuid();
        $line = ['id' => $id, 'category' => 'materials_supplies', 'description' => 'Materials', 'quantity' => '1.00', 'unit_cost' => '100.00'];
        $this->post(route('projects.store'), $base + ['budget_items' => [$line, $line]])
            ->assertSessionHasErrors('budget_items.0.id');
        $this->assertSame(0, Project::count());
    }

    public function test_uploaded_form_is_the_current_record_even_if_old_online_answers_exist(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $this->actingAs($owner)->post(route('projects.store'), [
            'title' => 'Online concept', 'area' => 'Community Impact',
            'proposed_budget' => '0.00', 'concept_submission_method' => 'online',
            'submission_file' => UploadedFile::fake()->create('unexpected.pdf', 8, 'application/pdf'),
        ])->assertSessionHasErrors('submission_file');

        $project = Project::create([
            'created_by' => $owner->id, 'title' => 'Uploaded project', 'area' => 'Community Impact',
            'status' => 'Approved', 'concept_submission_method' => 'upload',
            'proposal_submission_method' => 'upload',
            'concept' => ['objective' => 'Old online objective', 'beneficiaries' => 'Old online beneficiaries'],
            'proposal' => ['rationale' => 'Old online proposal'],
        ]);
        $this->get(route('projects.show', $project))->assertOk()
            ->assertSee('The current project objective is in the uploaded official concept below.')
            ->assertDontSee('Old online objective')
            ->assertDontSee('Print proposal packet');
        $this->get(route('projects.proposal', $project))->assertNotFound();
    }

    public function test_legacy_review_restart_cannot_discard_delivery_history(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $active = Project::create(['created_by' => $owner->id, 'title' => 'Legacy live project', 'status' => 'Ongoing']);
        Task::create(['project_id' => $active->id, 'title' => 'Delivered work', 'status' => 'Completed']);
        $this->actingAs($owner)->post(route('projects.transition', $active), [
            'action' => 'restart_review', 'comments' => 'Restart despite completed work',
        ])->assertForbidden();

        $older = Project::create([
            'created_by' => $owner->id, 'title' => 'Unreviewed legacy proposal',
            'status' => 'Approved', 'proposal_reviewed_at' => now(),
        ]);
        $this->assertSame('Proposal approved', ProjectFlow::statusLabel($older));
        $this->post(route('projects.transition', $older), [
            'action' => 'restart_review', 'comments' => 'Run through signed review',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Admin Review', $older->fresh()->status);
        $this->assertNull($older->fresh()->proposal_reviewed_at);
    }
}
