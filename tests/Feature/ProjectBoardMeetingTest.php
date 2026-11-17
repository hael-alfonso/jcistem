<?php

namespace Tests\Feature;

use App\Models\{CalendarEvent, Notification, Project, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectBoardMeetingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_schedule_and_reschedule_one_calendar_meeting_for_a_verified_proposal(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $board = User::factory()->create(['role' => 'bod', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Proposal for the Board',
            'status' => 'Submitted for Formal Approval', 'proposal_admin_reviewed_at' => now(), 'proposal_budget_reviewed_at' => now(),
            'proposal_submitted_revision' => 1, 'revision' => 1]);
        $firstDate = now()->addDays(2)->toDateString();
        $secondDate = now()->addDays(4)->toDateString();

        $this->actingAs($owner)->post(route('projects.board-meeting.schedule', $project),
            ['meeting_date' => $firstDate])->assertForbidden();
        $this->actingAs($admin)->post(route('projects.board-meeting.schedule', $project),
            ['meeting_date' => $firstDate, 'venue' => 'Chapter hall'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('calendar_events', ['project_id' => $project->id,
            'purpose' => 'proposal_board', 'type' => 'Meeting', 'venue' => 'Chapter hall']);
        $this->assertDatabaseHas('notifications', ['user_id' => $board->id, 'title' => 'Board meeting scheduled']);
        $this->actingAs($board)->get(route('calendar', ['month' => substr($firstDate, 0, 7)]))
            ->assertOk()->assertSee('Full proposal Board meeting: Proposal for the Board');

        $notificationCount = Notification::where('user_id', $board->id)->count();
        $this->actingAs($admin)->post(route('projects.board-meeting.schedule', $project),
            ['meeting_date' => $firstDate, 'venue' => 'Chapter hall'])
            ->assertSessionHas('success', 'Board meeting details are unchanged.');
        $this->assertSame($notificationCount, Notification::where('user_id', $board->id)->count());

        $this->actingAs($admin)->post(route('projects.board-meeting.schedule', $project),
            ['meeting_date' => $secondDate, 'venue' => 'New meeting venue'])->assertSessionHasNoErrors();
        $this->assertSame(1, CalendarEvent::where('project_id', $project->id)->where('purpose', 'proposal_board')->count());
        $this->assertSame($secondDate, CalendarEvent::where('project_id', $project->id)->firstOrFail()->starts_on->toDateString());
        $this->assertDatabaseHas('notifications', ['user_id' => $board->id, 'title' => 'Board meeting rescheduled']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'project_board_meeting_rescheduled']);
    }

    public function test_signed_board_verdict_must_use_the_scheduled_date(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'title' => 'Scheduled decision',
            'status' => 'Submitted for Formal Approval', 'proposal_admin_reviewed_at' => now(), 'proposal_budget_reviewed_at' => now(),
            'proposal_submitted_revision' => 1, 'revision' => 1]);
        $scheduledDate = now()->addDay()->toDateString();
        $this->actingAs($admin)->post(route('projects.board-meeting.schedule', $project),
            ['meeting_date' => $scheduledDate])->assertSessionHasNoErrors();

        $this->travel(2)->days();
        $this->post(route('projects.transition', $project), [
            'action' => 'approve_full_proposal', 'comments' => 'Signed Board verdict recorded.',
            'meeting_date' => now()->toDateString(),
            'evidence' => UploadedFile::fake()->create('signed-board.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('action');
        $this->assertSame('Submitted for Formal Approval', $project->fresh()->status);

        $this->post(route('projects.transition', $project), [
            'action' => 'approve_full_proposal', 'comments' => 'Signed Board verdict recorded.',
            'meeting_date' => $scheduledDate,
            'evidence' => UploadedFile::fake()->create('signed-board.pdf', 10, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->assertSame('Approved', $project->fresh()->status);
        $this->assertDatabaseHas('bod_decisions', ['project_id' => $project->id, 'meeting_date' => $scheduledDate.' 00:00:00']);
        $this->travelBack();
    }

    public function test_board_deferral_returns_the_proposal_for_revision_and_clears_the_old_meeting(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $owner->id, 'chair_id' => $owner->id,
            'title' => 'Deferred proposal', 'status' => 'Submitted for Formal Approval',
            'proposal_admin_reviewed_at' => now(), 'proposal_budget_reviewed_at' => now(),
            'proposal_submitted_revision' => 1, 'revision' => 1]);
        $url = route('projects.transition', $project);
        $this->actingAs($admin)->post($url, ['action' => 'approve_full_proposal', 'comments' => 'Too early',
            'meeting_date' => today()->toDateString(),
            'evidence' => UploadedFile::fake()->create('early.pdf', 10, 'application/pdf')])->assertSessionHasErrors('action');
        $this->post(route('projects.board-meeting.schedule', $project), ['meeting_date' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->post($url, ['action' => 'defer_full_proposal', 'comments' => 'Revise delivery measures',
            'meeting_date' => today()->toDateString(),
            'evidence' => UploadedFile::fake()->create('deferred.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $project->refresh();
        $this->assertSame('Returned for Revision', $project->status);
        $this->assertNull($project->proposal_admin_reviewed_at);
        $this->assertNull($project->proposal_budget_reviewed_at);
        $this->assertDatabaseMissing('calendar_events', ['project_id' => $project->id, 'purpose' => 'proposal_board']);
        $this->assertDatabaseHas('bod_decisions', ['project_id' => $project->id, 'decision' => 'deferred']);
    }
}
