<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\MemberDue;
use App\Models\Project;
use App\Models\RegistrationRequest;
use App\Models\Task;
use App\Models\User;
use App\Support\NotificationIndicators;
use App\Support\PmsNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_bell_loads_a_small_first_page_and_scroll_pages_stay_private(): void
    {
        $recipient = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $other = User::factory()->create(['role' => 'member', 'status' => 'active']);
        foreach (range(1, 12) as $number) {
            Notification::create(['user_id' => $recipient->id, 'title' => 'Notice '.$number, 'body' => 'Update '.$number]);
        }
        Notification::create(['user_id' => $other->id, 'title' => 'Other account only', 'body' => 'Private']);

        $page = $this->actingAs($recipient)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertSame(10, substr_count($page, 'class="topbar-notification-item '));
        $this->assertStringContainsString('Notice 12', $page);
        $this->assertStringNotContainsString('Notice 1</span>', $page);
        $this->assertStringNotContainsString('Other account only', $page);
        $this->assertStringContainsString('menu_page=2', $page);

        $next = $this->getJson(route('notifications.menu', ['menu_page' => 2]))->assertOk();
        $this->assertStringContainsString('Notice 1', $next->json('html'));
        $this->assertStringNotContainsString('Other account only', $next->json('html'));
        $this->assertNull($next->json('next'));
    }

    public function test_navigation_shows_dots_only_for_unread_notifications_with_a_relevant_destination(): void
    {
        $recipient = User::factory()->create(['role' => 'member', 'status' => 'active']);
        Notification::create(['user_id' => $recipient->id, 'title' => 'Dues reminder', 'body' => 'Review your balance', 'url' => '/dues']);
        Notification::create(['user_id' => $recipient->id, 'title' => 'Old project update', 'body' => 'Already read', 'url' => '/projects/12', 'read_at' => now()]);
        Notification::create(['user_id' => $recipient->id, 'title' => 'General notice', 'body' => 'No module destination']);

        $page = $this->actingAs($recipient)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($page, 'class="nav-menu-dot"')); // Finance and My Member Dues; the bell keeps the unread count.
        $this->assertStringContainsString('aria-label="Notifications (2 unread)"', $page);
        $this->assertStringContainsString('<b>2</b>', $page);

        $this->post(route('notifications.read'))->assertRedirect();
        $readPage = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('class="nav-menu-dot"', $readPage);
        $this->assertStringNotContainsString('aria-label="Notifications (2 unread)"', $readPage);
    }

    public function test_unread_submenu_dots_follow_the_recipient_role_and_notification_type(): void
    {
        $admin = User::factory()->make(['role' => 'admin', 'status' => 'active']);
        $treasurer = User::factory()->make(['role' => 'treasurer', 'status' => 'active']);
        $member = User::factory()->make(['role' => 'member', 'status' => 'active']);
        $notices = [
            Notification::make(['title' => 'Community project', 'body' => 'Project status: In review', 'url' => '/projects/12']),
            Notification::make(['title' => 'Expense claim needs review', 'url' => '/expense-claims']),
            Notification::make(['title' => 'Liquidation submitted: Treasurer review, Admin copy', 'url' => '/records/reports/9']),
            Notification::make(['title' => 'Member dues', 'url' => '/dues']),
            Notification::make(['title' => 'Task updated: Outreach', 'url' => '/projects/12#task-3']),
        ];

        $adminItems = PmsNavigation::unreadItems($notices, $admin);
        $this->assertArrayHasKey('Project Reviews', $adminItems['PROJECTS']);
        $this->assertArrayNotHasKey('REPORTS & DOCUMENTS', $adminItems);

        $treasurerItems = PmsNavigation::unreadItems($notices, $treasurer);
        $this->assertArrayHasKey('Claims to Review', $treasurerItems['FINANCE']);
        $this->assertArrayHasKey('Liquidation Report Review', $treasurerItems['REPORTS & DOCUMENTS']);
        $this->assertArrayHasKey('Task Monitoring', $treasurerItems['TASKS']);

        $memberItems = PmsNavigation::unreadItems($notices, $member);
        $this->assertArrayHasKey('My Projects', $memberItems['PROJECTS']);
        $this->assertArrayHasKey('My Tasks', $memberItems['TASKS']);
        $this->assertArrayHasKey('My Member Dues', $memberItems['FINANCE']);

        foreach ([[$admin, $adminItems], [$treasurer, $treasurerItems], [$member, $memberItems]] as [$user, $items]) {
            foreach ($items as $group => $labels) {
                $this->assertArrayHasKey($group, PmsNavigation::for($user));
                foreach (array_keys($labels) as $label) {
                    $this->assertContains($label, array_column(PmsNavigation::for($user)[$group], 1));
                }
            }
        }
    }

    public function test_review_queue_dots_only_appear_for_the_responsible_role_and_unread_notice(): void
    {
        $admin = User::factory()->make(['role' => 'admin', 'status' => 'active']);
        $treasurer = User::factory()->make(['role' => 'treasurer', 'status' => 'active']);
        $bod = User::factory()->make(['role' => 'bod', 'status' => 'active']);
        $notices = [
            Notification::make(['title' => 'Report submitted for Admin review', 'url' => '/records/reports/9']),
            Notification::make(['title' => 'Liquidation submitted: Treasurer review, Admin copy', 'url' => '/records/reports/10']),
            Notification::make(['title' => 'JCI LOI for review', 'url' => '/records/letters/11']),
            Notification::make(['title' => 'Partnership agreement for review', 'url' => '/partnership-agreements/12']),
            Notification::make(['title' => 'New member registration', 'url' => '/registrations/13']),
            Notification::make(['title' => 'Project A', 'body' => 'Cancellation appeal needs review', 'url' => '/projects/14#cancellation-decision']),
            Notification::make(['title' => 'Project B', 'body' => 'Project status: Board review', 'url' => '/projects/16']),
            Notification::make(['title' => 'Project C', 'body' => 'Project status: Budget review', 'url' => '/projects/17']),
            Notification::make(['title' => 'Expense claim needs review', 'url' => '/expense-claims']),
            Notification::make(['title' => 'Old report', 'url' => '/records/reports/15', 'read_at' => now()]),
        ];

        $adminItems = PmsNavigation::unreadItems($notices, $admin);
        $this->assertArrayHasKey('Completion Report Review', $adminItems['REPORTS & DOCUMENTS']);
        $this->assertArrayNotHasKey('Liquidation Report Review', $adminItems['REPORTS & DOCUMENTS']);
        $this->assertArrayHasKey('External LOI Reviews', $adminItems['PARTNERSHIPS']);
        $this->assertArrayHasKey('Partnership Agreements', $adminItems['PARTNERSHIPS']);
        $this->assertArrayHasKey('Registration Requests', $adminItems['MEMBERS']);
        $this->assertArrayHasKey('Project Reviews', $adminItems['PROJECTS']);
        $this->assertArrayNotHasKey('Claims to Review', $adminItems['FINANCE'] ?? []);

        $treasurerItems = PmsNavigation::unreadItems($notices, $treasurer);
        $this->assertArrayHasKey('Liquidation Report Review', $treasurerItems['REPORTS & DOCUMENTS']);
        $this->assertArrayHasKey('Claims to Review', $treasurerItems['FINANCE']);
        $this->assertArrayNotHasKey('Completion Report Review', $treasurerItems['REPORTS & DOCUMENTS']);
        $this->assertArrayNotHasKey('MEMBERS', $treasurerItems);
        $this->assertArrayNotHasKey('PARTNERSHIPS', $treasurerItems);

        $bodItems = PmsNavigation::unreadItems($notices, $bod);
        $this->assertArrayHasKey('Project Reviews', $bodItems['PROJECTS']);
        $this->assertArrayNotHasKey('REPORTS & DOCUMENTS', $bodItems);
        $this->assertArrayNotHasKey('MEMBERS', $bodItems);

        $adminGroups = PmsNavigation::unreadGroups($notices, $admin);
        $this->assertArrayHasKey('REPORTS & DOCUMENTS', $adminGroups);
        $this->assertArrayHasKey('PARTNERSHIPS', $adminGroups);
        $this->assertArrayHasKey('MEMBERS', $adminGroups);
        $this->assertArrayNotHasKey('REPORTS & DOCUMENTS', PmsNavigation::unreadGroups($notices, $bod));
    }

    public function test_unread_review_notices_render_parent_and_submenu_dots_without_replacing_the_bell_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Notification::create(['user_id' => $admin->id, 'title' => 'New member registration', 'body' => 'Review this applicant', 'url' => '/registrations/13']);
        Notification::create(['user_id' => $admin->id, 'title' => 'Report submitted for Admin review', 'body' => 'Completion report', 'url' => '/records/reports/9']);

        $page = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertSame(4, substr_count($page, 'class="nav-menu-dot"'));
        $this->assertStringContainsString('aria-label="Notifications (2 unread)"', $page);
        $this->assertStringContainsString('<b>2</b>', $page);

        $this->post(route('notifications.read'))->assertRedirect();
        $readPage = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('class="nav-menu-dot"', $readPage);
    }

    public function test_opening_a_marked_registration_clears_only_its_record_updates(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $first = RegistrationRequest::create(['reference' => 'REG-001', 'name' => 'Applicant One', 'username' => 'applicant_one', 'email' => 'one@example.test', 'reason' => 'Join the chapter']);
        $second = RegistrationRequest::create(['reference' => 'REG-002', 'name' => 'Applicant Two', 'username' => 'applicant_two', 'email' => 'two@example.test', 'reason' => 'Join the chapter']);
        $firstNotice = Notification::create(['user_id' => $admin->id, 'title' => 'New member registration', 'url' => route('registrations.show', $first, false)]);
        $sameRecord = Notification::create(['user_id' => $admin->id, 'title' => 'New member registration', 'url' => route('registrations.show', $first, false)]);
        $otherNotice = Notification::create(['user_id' => $admin->id, 'title' => 'New member registration', 'url' => route('registrations.show', $second, false)]);

        $list = $this->actingAs($admin)->get(route('registrations'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($list, 'Needs your review'));
        $this->assertStringContainsString(route('notifications.open', $firstNotice), $list);

        $this->post(route('notifications.open', $firstNotice))->assertRedirect(route('registrations.show', $first, false));
        $this->assertNotNull($firstNotice->fresh()->read_at);
        $this->assertNotNull($sameRecord->fresh()->read_at);
        $this->assertNull($otherNotice->fresh()->read_at);

        $updatedList = $this->get(route('registrations'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($updatedList, 'Needs your review'));
    }

    public function test_review_content_targets_do_not_confuse_reports_letters_and_claims(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'status' => 'active']);
        $report = Notification::create(['user_id' => $admin->id, 'title' => 'Report submitted for Admin review', 'url' => '/records/reports/7']);
        $letter = Notification::create(['user_id' => $admin->id, 'title' => 'JCI LOI for review', 'url' => '/records/letters/7']);
        $claim = Notification::create(['user_id' => $treasurer->id, 'title' => 'Expense claim needs review', 'url' => '/expense-claims?status=all&claim=42#claim-42']);

        $adminTargets = NotificationIndicators::for($admin);
        $this->assertSame($report->id, $adminTargets['review_reports'][7]);
        $this->assertSame($letter->id, $adminTargets['review_letters'][7]);
        $this->assertSame('report:7', NotificationIndicators::destinationKey($report));
        $this->assertSame('letter:7', NotificationIndicators::destinationKey($letter));
        $this->assertSame($claim->id, NotificationIndicators::for($treasurer)['review_claims'][42]);
        $this->assertSame('claim:42', NotificationIndicators::destinationKey($claim));
    }

    public function test_unread_navigation_groups_follow_safe_notification_destinations(): void
    {
        $groups = PmsNavigation::unreadGroups([
            Notification::make(['url' => '/workspaces/3c2887bb-7524-48aa-a593-4302367f4e0c/projects/12']),
            Notification::make(['url' => '/records/letters/4']),
            Notification::make(['url' => '/records/reports/9']),
            Notification::make(['url' => '/registrations/2']),
            Notification::make(['title' => 'Task completed: Outreach', 'url' => '/projects/12#task-3']),
            Notification::make(['url' => '/dues', 'read_at' => now()]),
            Notification::make(['url' => 'https://untrusted.example/projects']),
        ]);

        $this->assertEqualsCanonicalizing(['PROJECTS', 'TASKS'], array_keys($groups));
    }

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

    public function test_unread_project_task_and_dues_updates_mark_the_matching_records_only(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $project = Project::create(['created_by' => $member->id, 'title' => 'Community outreach', 'status' => 'Ongoing', 'plan_locked_at' => now()]);
        $task = Task::create(['project_id' => $project->id, 'title' => 'Prepare kits', 'status' => 'To Do', 'assignees' => [$member->id]]);
        $due = MemberDue::create(['member_id' => $member->id, 'period' => '2026-11', 'due_date' => '2026-11-30', 'amount' => 150]);
        $otherDue = MemberDue::create(['member_id' => $member->id, 'period' => '2026-10', 'due_date' => '2026-10-30', 'amount' => 100]);

        $projectNotice = Notification::create(['user_id' => $member->id, 'title' => $project->title, 'body' => 'Project status: Ongoing', 'url' => '/projects/'.$project->id]);
        Notification::create(['user_id' => $member->id, 'title' => 'Task updated: '.$task->title, 'body' => 'To Do', 'url' => '/projects/'.$project->id.'#task-'.$task->id]);
        Notification::create(['user_id' => $member->id, 'title' => 'Member dues: '.$due->period, 'body' => 'Amount due', 'url' => '/dues?due='.$due->id.'#due-'.$due->id]);

        $targets = NotificationIndicators::for($member);
        $this->assertArrayHasKey($project->id, $targets['projects']);
        $this->assertArrayHasKey($task->id, $targets['tasks']);
        $this->assertArrayHasKey($due->id, $targets['dues']);
        $this->assertArrayNotHasKey($otherDue->id, $targets['dues']);

        $projectPage = $this->actingAs($member)->get(route('projects.show', $project))->assertOk()->getContent();
        $this->assertTrue(str_contains($projectPage, 'Unread project update'), 'Project detail should mark its unread project update.');
        $this->assertStringContainsString(route('notifications.open', $projectNotice), $projectPage);
        $this->assertTrue(str_contains($projectPage, 'Unread task update'), 'Project detail should mark its unread task update.');
        $projectList = $this->get(route('projects', ['filter' => 'mine']))->assertOk()->getContent();
        $this->assertTrue(str_contains($projectList, 'Unread project update'), 'Project list should identify its unread card.');
        $taskList = $this->get(route('tasks', ['mine' => 1]))->assertOk()->getContent();
        $this->assertTrue(str_contains($taskList, 'Unread task update'), 'Task list should identify its unread row.');
        $duesPage = $this->get(route('dues', ['due' => $due->id]))->assertOk()->getContent();
        $this->assertTrue(str_contains($duesPage, 'id="due-'.$due->id.'"'), 'The requested dues period should be visible.');
        $this->assertFalse(str_contains($duesPage, 'id="due-'.$otherDue->id.'"'), 'Other dues periods should be excluded.');
        $this->assertTrue(str_contains($duesPage, 'Unread dues update'), 'The due card should mark its unread update.');

        $this->post(route('notifications.open', $projectNotice))->assertRedirect('/projects/'.$project->id)->assertSessionHas('notification_focus', true);
        $readPage = $this->withCookie(config('session.cookie'), session()->getId())->get(route('projects.show', $project))->assertOk()->getContent();
        $this->assertFalse(str_contains($readPage, 'Unread project update'), 'Reading the project notice should remove its dot.');
        $this->assertTrue(str_contains($readPage, 'Unread task update'), 'The separate task notice should remain unread.');
        $this->assertTrue(str_contains($readPage, 'data-notification-focus="true"'), 'Opening the notice should trigger the focused highlight.');
    }

    public function test_new_dues_notice_points_to_the_exact_private_assessment(): void
    {
        $treasurer = User::factory()->create(['role' => 'treasurer', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);

        $this->actingAs($treasurer)->post(route('dues.store'), [
            'period' => '2026-11', 'due_date' => '2026-11-30', 'amount' => 150,
            'member_id' => $member->id, 'remarks' => 'Monthly chapter dues',
        ])->assertSessionHasNoErrors();

        $due = MemberDue::where('member_id', $member->id)->firstOrFail();
        $notice = Notification::where('user_id', $member->id)->where('title', 'Member dues: 2026-11')->firstOrFail();
        $this->assertSame('/dues?due='.$due->id.'#due-'.$due->id, $notice->url);
        $this->actingAs($member)->get($notice->url)->assertOk()->assertSee('id="due-'.$due->id.'"', false);
        $this->actingAs($treasurer)->get(route('dues', ['due' => $due->id]))->assertOk()->assertDontSee('id="due-'.$due->id.'"', false);
    }

    public function test_older_dues_notice_without_an_anchor_still_opens_its_period(): void
    {
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $due = MemberDue::create(['member_id' => $member->id, 'period' => '2026-11', 'due_date' => '2026-11-30', 'amount' => 150]);
        $notice = Notification::create(['user_id' => $member->id, 'title' => 'Member dues: 2026-11', 'body' => 'Amount due', 'url' => '/dues']);

        $this->actingAs($member)->post(route('notifications.open', $notice))
            ->assertRedirect('/dues?due='.$due->id.'#due-'.$due->id)
            ->assertSessionHas('notification_focus', true);
    }
}
