<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_all_reads_all_recipient_notifications_without_changing_other_users_updates(): void
    {
        $recipient = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $other = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $routine = Notification::create(['user_id' => $recipient->id, 'title' => 'Task update', 'body' => 'Routine update']);
        $important = Notification::create(['user_id' => $recipient->id, 'title' => 'Board decision', 'body' => 'Review this decision', 'requires_open' => true]);
        $foreign = Notification::create(['user_id' => $other->id, 'title' => 'Private update', 'body' => 'Other user']);

        $this->actingAs($recipient)->get(route('notifications'))->assertOk()
            ->assertSee('Mark all notifications as read')
            ->assertSee('Mark all notifications as read');

        $this->post(route('notifications.read'))->assertRedirect();

        $this->assertNotNull($routine->fresh()->read_at);
        $this->assertNotNull($important->fresh()->read_at);
        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_recipients_can_remove_their_notifications_including_unread_important_updates(): void
    {
        $recipient = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $other = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $routine = Notification::create(['user_id' => $recipient->id, 'title' => 'Reminder', 'body' => 'Routine update']);
        $important = Notification::create(['user_id' => $recipient->id, 'title' => 'Project decision', 'body' => 'Important update', 'requires_open' => true]);

        $this->actingAs($other)->delete(route('notifications.delete', $routine))->assertForbidden();
        $this->assertModelExists($routine);

        $this->actingAs($recipient)->delete(route('notifications.delete', $important))->assertRedirect();
        $this->assertModelMissing($important);

        $this->delete(route('notifications.delete', $routine))->assertRedirect();
        $this->assertModelMissing($routine);

    }

    public function test_icon_controls_and_confirmation_are_available_on_page_and_dropdown(): void
    {
        $recipient = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $routine = Notification::create(['user_id' => $recipient->id, 'title' => 'Routine task', 'body' => 'Open or remove']);
        $important = Notification::create(['user_id' => $recipient->id, 'title' => 'Important decision', 'body' => 'Open first', 'url' => '/projects', 'requires_open' => true]);

        $page = $this->actingAs($recipient)->get(route('notifications'))->assertOk()->getContent();
        $this->assertStringContainsString('action="'.route('notifications.delete', $routine).'"', $page);
        $this->assertStringContainsString('action="'.route('notifications.delete', $important).'"', $page);
        $this->assertStringContainsString('Remove notification: Routine task', $page);
        $this->assertStringContainsString('Remove notification: Important decision', $page);
        $this->assertStringContainsString('Remove this important unread notification?', $page);
        $this->assertStringContainsString('aria-label="Mark all notifications as read"', $page);
        $this->assertStringContainsString('aria-label="Open notification: Important decision"', $page);
    }

    public function test_old_cancelled_project_notices_are_informational_and_still_manageable(): void
    {
        $recipient = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $cancelled = Notification::create([
            'user_id' => $recipient->id,
            'title' => 'Cancelled chapter project',
            'body' => 'Project status: Cancelled',
            'url' => '/workspaces/3c2887bb-7524-48aa-a593-4302367f4e0c/projects/12',
            'requires_open' => true,
        ]);
        Notification::create([
            'user_id' => $recipient->id,
            'title' => 'Approved chapter project',
            'body' => 'Project status: Approved',
            'url' => '/projects',
            'requires_open' => true,
        ]);

        $page = $this->actingAs($recipient)->get(route('notifications'))->assertOk()->getContent();
        $this->assertFalse($cancelled->hasDestination());
        $this->assertStringContainsString('class="topbar-notification-static"', $page);
        $this->assertStringContainsString('aria-label="Mark notification as read: Cancelled chapter project"', $page);
        $this->assertStringNotContainsString('aria-label="Open notification: Cancelled chapter project"', $page);
        $this->assertStringContainsString('aria-label="Open notification: Approved chapter project"', $page);
        $this->assertStringContainsString('aria-label="Remove notification: Cancelled chapter project"', $page);

        $this->post(route('notifications.read'))->assertRedirect();
        $this->assertNotNull($cancelled->fresh()->read_at);
        $cancelled->update(['read_at' => null]);
        $this->post(route('notifications.open', $cancelled))->assertRedirect(route('notifications'));
        $this->assertNotNull($cancelled->fresh()->read_at);
        $this->delete(route('notifications.delete', $cancelled))->assertRedirect();
        $this->assertModelMissing($cancelled);
    }

    public function test_new_project_cancellation_notices_open_the_retained_project_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $creator = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $creator->id, 'title' => 'Community supply drive', 'status' => 'Draft Concept']);

        $this->actingAs($admin)->post(route('projects.transition', $project), [
            'action' => 'cancel', 'comments' => 'Duplicate project record.',
        ])->assertSessionHasNoErrors();

        $notice = Notification::where('user_id', $creator->id)->where('body', 'Project status: Cancelled')->firstOrFail();
        $this->assertSame('/projects/'.$project->id, $notice->url);
        $this->assertTrue($notice->requires_open);
        $this->actingAs($creator)->get(route('notifications'))->assertOk()
            ->assertSee('Community supply drive')
            ->assertSee('Open notification: Community supply drive', false);
        $this->post(route('notifications.open', $notice))->assertRedirect('/projects/'.$project->id);
    }
}
