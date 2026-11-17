<?php

namespace App\Console\Commands;

use App\Models\{MemberDue, Notification, Project, Task, User};
use App\Services\ProjectLaunchService;
use Illuminate\Console\Command;

class SendPmsReminders extends Command
{
    protected $signature = 'pms:reminders';
    protected $description = 'Send daily in-app reminders for upcoming projects, open tasks, and outstanding member dues';

    public function handle(): int
    {
        $launched = ProjectLaunchService::launchDue();
        $day = now('Asia/Manila')->toDateString();
        $tomorrow = now('Asia/Manila')->addDay()->toDateString();
        $projectDay = now('Asia/Manila')->addDays(3)->toDateString();
        $activeUserIds = User::where('status', 'active')->pluck('id');
        Project::whereIn('status', ['Approved', 'Plan Locked', 'Ongoing'])->whereDate('starts_on', $projectDay)
            ->chunkById(100, function ($projects) use ($activeUserIds, $projectDay) {
                foreach ($projects as $project) {
                    foreach ($activeUserIds as $userId) {
                        Notification::firstOrCreate(
                            ['user_id' => $userId, 'event_key' => 'project-start:'.$project->id.':'.$projectDay],
                            ['title' => 'Project in three days: '.$project->title,
                                'body' => 'Scheduled for '.$project->starts_on->format('d M Y').($project->venue ? ' · '.$project->venue : ''),
                                'url' => '/projects/'.$project->id]
                        );
                    }
                }
            });
        Task::with('project')->where('status', '!=', 'Completed')->whereDate('deadline', '<=', $tomorrow)
            ->whereHas('project', fn ($q) => $q->whereIn('status', ['Approved', 'Plan Locked', 'Ongoing']))->chunkById(100, function ($tasks) use ($day) {
                foreach ($tasks as $task) {
                    $ids = collect($task->assignees ?? [])->filter(fn ($id) => ($task->assignment_responses[$id]['response'] ?? null) !== 'declined')->push($task->project->chair_id)->filter()->unique();
                    $recipients = User::whereIn('id', $ids)->where('status', 'active')->get()
                        ->filter(fn (User $user) => (bool) data_get($user->profile, 'preferences.task_reminders', true));
                    foreach ($recipients->pluck('id') as $id) {
                        Notification::firstOrCreate(['user_id' => $id, 'event_key' => 'task:'.$task->id.':'.$task->deadline->toDateString().':'.$day], ['title' => 'Task deadline: '.$task->title, 'body' => $task->project->title.' · Due '.$task->deadline->format('d M Y'), 'url' => '/projects/'.$task->project_id.'#task-'.$task->id]);
                    }
                }
            });
        MemberDue::with(['member', 'payments'])->whereDate('due_date', '<=', $tomorrow)->chunkById(100, function ($dues) use ($day) {
            foreach ($dues as $due) if ($due->balance > 0 && $due->member?->status === 'active' && (bool) data_get($due->member->profile, 'preferences.dues_reminders', true)) {
                Notification::firstOrCreate(['user_id' => $due->member_id, 'event_key' => 'dues:'.$due->id.':'.$day], ['title' => 'Membership dues reminder', 'body' => $due->period.' · PHP '.number_format($due->balance, 2).' outstanding.', 'url' => '/dues']);
            }
        });
        $this->info("Daily reminders checked. {$launched} due project(s) launched.");
        return self::SUCCESS;
    }
}
