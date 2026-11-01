<?php

namespace Tests\Feature;

use App\Models\{BodDecision, Loi, Partner, PartnershipAgreement, Project, User};
use App\Support\ChapterForms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnershipWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_loi_requires_admin_approval_before_print_and_records_delivery_in_partner_history(): void
    {
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $chair->id, 'chair_id' => $chair->id, 'title' => 'Community event', 'area' => 'Community Impact', 'status' => 'Ongoing', 'plan_locked_at' => now(), 'proposed_budget' => 0]);
        BodDecision::create(['project_id' => $project->id, 'recorded_by' => $admin->id, 'meeting_date' => today(), 'decision' => 'approved', 'resolution_number' => 'PARTNER-TEST-1', 'evidence_path' => 'board.pdf', 'comments' => 'Approved']);
        $data = array_fill_keys(array_keys(ChapterForms::LETTER_REQUIRED), 'Partnership details');
        $data['letter_date'] = '2026-10-31';
        $data['organization'] = 'Carmona Partner Foundation';
        $letter = Loi::create(['project_id' => $project->id, 'created_by' => $chair->id, 'type' => 'JCI LOI', 'status' => 'Draft', 'title' => 'Community project partnership', 'data' => $data]);

        $this->actingAs($chair)->get('/records/letters/'.$letter->id)->assertOk()->assertDontSee('Print approved LOI');
        $this->post('/records/letters/'.$letter->id.'/transition', ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->assertSame('For Review', $letter->fresh()->status);
        $this->assertNotNull($letter->fresh()->partner_id);
        $this->post('/records/letters/'.$letter->id.'/transition', ['action' => 'sent', 'delivered_on' => '2026-10-31'])->assertForbidden();
        $this->actingAs($admin)->post('/records/letters/'.$letter->id.'/transition', ['action' => 'approve', 'comments' => 'Ready for signature'])->assertSessionHasNoErrors();
        $this->get('/records/letters/'.$letter->id)->assertOk()->assertSee('Print approved LOI');
        $this->actingAs($chair)->post('/records/letters/'.$letter->id.'/transition', ['action' => 'sent', 'delivered_on' => '2026-11-01', 'delivery_notes' => 'Handed to partner representative'])->assertSessionHasNoErrors();
        $this->assertSame('Sent', $letter->fresh()->status);
        $this->assertSame('2026-11-01', $letter->fresh()->data['delivered_on']);
        $this->get('/partner-directory/'.$letter->partner_id)->assertOk()->assertSee($letter->title);
    }

    public function test_agreement_needs_independent_admin_approval_and_signed_copy_is_private(): void
    {
        Storage::fake('local');
        $chair = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $outsider = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $chair->id, 'chair_id' => $chair->id, 'title' => 'Education outreach', 'area' => 'Community Impact', 'status' => 'Ongoing', 'plan_locked_at' => now(), 'proposed_budget' => 0]);
        BodDecision::create(['project_id' => $project->id, 'recorded_by' => $admin->id, 'meeting_date' => today(), 'decision' => 'approved', 'resolution_number' => 'PARTNER-TEST-2', 'evidence_path' => 'board.pdf', 'comments' => 'Approved']);
        $partner = Partner::create(['name' => 'School Partner']);
        $terms = ['purpose' => 'Education outreach', 'partner_support' => 'Venue and volunteers', 'chapter_commitments' => 'Training team', 'deliverables' => 'One event and report', 'start_on' => '2026-11-01', 'end_on' => '2026-11-30', 'reporting' => 'Weekly updates', 'contact_person' => 'Project Chair', 'president_name' => 'LO President', 'partner_signatory' => 'School Director', 'changes_and_termination' => 'Written notice from both parties'];
        $this->actingAs($chair)->post('/partner-directory/'.$partner->id.'/agreements', ['project_id' => $project->id, 'title' => 'Education partnership', 'terms' => $terms])->assertSessionHasNoErrors();
        $agreement = PartnershipAgreement::firstOrFail();
        $this->get('/partnership-agreements/'.$agreement->id)->assertOk()->assertDontSee('Print approved agreement');
        $this->post('/partnership-agreements/'.$agreement->id.'/transition', ['action' => 'submit'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post('/partnership-agreements/'.$agreement->id.'/transition', ['action' => 'approve', 'comments' => 'Terms checked'])->assertSessionHasNoErrors();
        $this->get('/partnership-agreements/'.$agreement->id)->assertOk()->assertSee('Print approved agreement');
        $this->post('/partnership-agreements/'.$agreement->id.'/signed', ['signed_on' => '2026-11-02', 'file' => UploadedFile::fake()->create('signed.pdf', 20, 'application/pdf')])->assertSessionHasNoErrors();
        $this->assertSame('Executed', $agreement->fresh()->status);
        $this->actingAs($outsider)->get('/partnership-agreements/'.$agreement->id.'/signed')->assertOk();
        $this->get('/partner-directory/'.$partner->id)->assertOk()->assertSee('Education partnership');
    }
}
