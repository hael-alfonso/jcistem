<?php

namespace Tests\Feature;

use App\Models\{MemberDue, Notification, Project, Task, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmsReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminders_are_private_and_idempotent(): void
    {
        $chair = User::factory()->create(['status' => 'active', 'role' => 'member']);
        $assignee = User::factory()->create(['status' => 'active', 'role' => 'member']);
        $other = User::factory()->create(['status' => 'active', 'role' => 'member']);
        $project = Project::create(['created_by' => $chair->id, 'chair_id' => $chair->id, 'title' => 'Active project', 'status' => 'Ongoing']);
        $task = Task::create(['project_id' => $project->id, 'title' => 'Due task', 'status' => 'To Do', 'deadline' => today(), 'assignees' => [$assignee->id]]);
        MemberDue::create(['member_id' => $assignee->id, 'period' => now()->format('Y-m'), 'amount' => '100.10', 'due_date' => today()]);
        $this->artisan('pms:reminders')->assertSuccessful();
        $this->artisan('pms:reminders')->assertSuccessful();
        $this->assertSame(3, Notification::count());
        $this->assertSame(0, Notification::where('user_id', $other->id)->count());
        $task->update(['status' => 'Completed']);
        $this->travel(1)->days();
        $this->artisan('pms:reminders')->assertSuccessful();
        $this->assertSame(4, Notification::count());
        $this->travelBack();
    }
}
