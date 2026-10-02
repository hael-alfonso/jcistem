<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class JciCarmonaTaskSeeder extends Seeder
{
    public function run(): void
    {
        $member = User::where('role', 'member')->orderBy('id')->first();
        $assignees = array_values(array_filter([$member?->id]));
        $adminIds = User::where('role', 'admin')->pluck('id')->all();
        if (!$assignees) return;

        $tasks = [
            'JCI-2026-LAPIS' => [
                ['Confirm chapter participation plan', 'Completed', 'High', '2026-09-18', 'Coordination'],
                ['Coordinate literacy volunteer schedule', 'In Progress', 'High', '2026-10-16', 'Volunteers'],
                ['Prepare learning materials and reading kits', 'To Do', 'Medium', '2026-10-23', 'Materials'],
            ],
            'JCI-2025-REDHEART' => [
                ['Document chapter participation', 'Completed', 'Medium', '2025-03-05', 'Documentation'],
                ['Gather activity feedback', 'Completed', 'Low', '2025-03-12', 'Follow-up'],
            ],
            'JCI-2026-IVY' => [
                ['Prepare chapter volunteerism presentation', 'Completed', 'High', '2026-07-10', 'Forum preparation'],
                ['Record forum follow-up ideas', 'Completed', 'Medium', '2026-07-20', 'Follow-up'],
            ],
            'JCI-2026-LAPIS-FAMILY' => [
                ['Discuss family learning needs with partners', 'To Do', 'Medium', '2026-10-30', 'Discovery'],
                ['Draft a family session outline', 'To Do', 'Medium', '2026-11-06', 'Planning'],
            ],
            'JCI-2026-YOUTH-DIALOGUE' => [
                ['Prepare a youth workshop outline', 'To Do', 'High', '2026-10-23', 'Program'],
                ['Confirm potential facilitators', 'To Do', 'Medium', '2026-10-30', 'Coordination'],
            ],
            'JCI-2026-VOLUNTEER-TOOLKIT' => [
                ['Collect volunteer mobilization examples', 'In Progress', 'Medium', '2026-11-13', 'Research'],
                ['Draft toolkit sections', 'To Do', 'High', '2026-11-27', 'Drafting'],
                ['Review toolkit with volunteers', 'To Do', 'Medium', '2026-12-04', 'Review'],
            ],
        ];

        foreach ($tasks as $reference => $items) {
            $project = Project::where('reference', $reference)->first();
            if (!$project) continue;
            foreach ($items as [$title, $status, $priority, $deadline, $milestone]) {
                $task = Task::firstOrCreate(['project_id' => $project->id, 'title' => $title], [
                    'description' => 'Coordinate this task with the assigned project chair and team.',
                    'assignees' => $assignees,
                    'priority' => $priority,
                    'status' => $status,
                    'deadline' => $deadline,
                    'milestone' => $milestone,
                    'dependencies' => [],
                ]);
                if (array_intersect($adminIds, $task->assignees ?? [])
                    || str_contains((string) $task->description, 'reference record')) {
                    $task->update(['assignees' => $assignees, 'description' => 'Coordinate this task with the assigned project chair and team.']);
                }
            }
        }
    }
}
