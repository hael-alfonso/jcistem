<?php

namespace App\Support;

use App\Models\Project;

final class ProjectFlow
{
    public static function statusLabel(Project $project): string
    {
        return match ($project->status) {
            'Draft Concept' => 'Draft',
            'Needs Revision', 'Returned for Revision' => 'Needs changes',
            'Admin Review', 'Submitted for President Review' => 'In review',
            'Treasurer Review' => 'Budget review',
            'BOD Agenda' => 'Admin routing',
            'Approved' => $project->proposal_reviewed_at ? 'Proposal approved' : 'Concept approved',
            'Endorsed for Development' => 'Proposal preparation',
            'Full Proposal Draft' => 'Proposal draft',
            'Submitted for Formal Approval' => $project->proposal_budget_reviewed_at ? 'Board review' : 'Budget review',
            'Plan Locked' => 'Ready to start',
            'Ongoing' => 'In progress',
            'Completion Review' => 'Final report review',
            'Not Approved' => 'Not approved',
            default => $project->status,
        };
    }

    /** @return array{title: string, actor: string, detail: string, anchor: string} */
    public static function next(Project $project): array
    {
        return match ($project->status) {
            'Draft Concept' => [
                'title' => 'Finish and submit the concept letter', 'actor' => 'Project proponent',
                'detail' => 'Save the project idea, then send it to Admin for the first review.', 'anchor' => 'project-actions',
            ],
            'Needs Revision' => [
                'title' => 'Revise the concept letter', 'actor' => 'Project proponent',
                'detail' => 'Use the review comments, save the revised concept, then resubmit it.', 'anchor' => 'project-actions',
            ],
            'Admin Review', 'Submitted for President Review' => [
                'title' => 'Review the concept', 'actor' => 'Admin',
                'detail' => 'Check the concept and either return it or pass it to the Treasurer.', 'anchor' => 'overview',
            ],
            'Treasurer Review' => [
                'title' => 'Check funding feasibility', 'actor' => 'Treasurer',
                'detail' => 'Review the concept budget before the project moves to full proposal preparation.', 'anchor' => 'budget',
            ],
            'BOD Agenda' => [
                'title' => 'Move the legacy concept to proposal preparation', 'actor' => 'Admin',
                'detail' => 'Concepts no longer receive a BOD verdict here. The formal Board decision belongs to the completed proposal.', 'anchor' => 'project-actions',
            ],
            'Approved' => $project->proposal_reviewed_at
                ? ['title' => 'Lock the approved plan', 'actor' => 'Project Chair', 'detail' => 'Confirm the final PPF before task assignments and delivery begin.', 'anchor' => 'project-actions']
                : ['title' => 'Prepare the full proposal', 'actor' => 'Project Chair', 'detail' => 'Complete the PPF and submit it for budget verification and final Board approval.', 'anchor' => 'project-actions'],
            'Endorsed for Development', 'Full Proposal Draft', 'Returned for Revision' => [
                'title' => 'Complete the full proposal', 'actor' => 'Project Chair',
                'detail' => 'Revise the plan and budget, then submit the current proposal version.', 'anchor' => 'proposal',
            ],
            'Submitted for Formal Approval' => $project->proposal_budget_reviewed_at
                ? ['title' => 'Record final Board approval', 'actor' => 'Admin after the Board meeting', 'detail' => 'Attach the signed final decision before the Chair can finalize the plan.', 'anchor' => 'project-actions']
                : ['title' => 'Verify the full proposal budget', 'actor' => 'Treasurer', 'detail' => 'Check the expense lines before the final Board decision.', 'anchor' => 'budget'],
            'Plan Locked' => [
                'title' => 'Assign tasks and launch the project', 'actor' => 'Project Chair',
                'detail' => 'Create clear deliverables, assign members, then start implementation.', 'anchor' => 'delivery',
            ],
            'Ongoing' => [
                'title' => 'Deliver work and record spending', 'actor' => 'Chair, assignees, and Treasurer',
                'detail' => 'Finish tasks, confirm fund handovers, and keep receipts and project evidence together.', 'anchor' => 'delivery',
            ],
            'Completion Review' => [
                'title' => 'Submit and review final reports', 'actor' => 'Chair, Admin, and Treasurer',
                'detail' => 'Submit the completion report and liquidation for separate reviews.', 'anchor' => 'completion',
            ],
            'Completed' => [
                'title' => 'Keep the finished record', 'actor' => 'Chapter',
                'detail' => 'The project is complete. Members can use it as a reference or start a new cycle.', 'anchor' => 'overview',
            ],
            'Archived' => [
                'title' => 'Use the stored project library', 'actor' => 'Chapter',
                'detail' => 'The final record remains available for reference and future reuse.', 'anchor' => 'overview',
            ],
            default => [
                'title' => 'Review the closed project record', 'actor' => 'Chapter',
                'detail' => 'Earlier decisions and posted finance records remain in the project history.', 'anchor' => 'overview',
            ],
        };
    }

    /**
     * The member-facing project journey. Internal approval steps stay visible in
     * the current Review and Approval description without multiplying the main stages.
     *
     * @return array{steps: array<int, array{title: string, detail: string, anchor: string, state: string, reached: bool}>, position: string}
     */
    public static function tracker(Project $project, int $completedTasks = 0, int $totalTasks = 0): array
    {
        $closed = in_array($project->status, ['Cancelled', 'Declined', 'Not Approved'], true);
        $status = $project->status;

        if ($status === 'Cancelled') {
            $cancelReview = $project->relationLoaded('reviews')
                ? $project->reviews->where('action', 'cancel')->sortByDesc('id')->first()
                : $project->reviews()->where('action', 'cancel')->latest('id')->first();
            $status = $cancelReview?->from_status ?? '';
        }

        $current = match (true) {
            in_array($status, ['Draft Concept', 'Needs Revision'], true) => 0,
            in_array($status, [
                'Admin Review', 'Submitted for President Review', 'Treasurer Review',
                'BOD Agenda', 'Approved', 'Endorsed for Development',
                'Full Proposal Draft', 'Returned for Revision',
                'Submitted for Formal Approval', 'Declined', 'Not Approved',
            ], true) => 1,
            $status === 'Plan Locked' => 2,
            $status === 'Ongoing' => 3,
            $status === 'Completion Review' => 4,
            in_array($status, ['Completed', 'Archived'], true) => 5,
            default => null,
        };

        $reviewDetail = match ($status) {
            'Admin Review', 'Submitted for President Review' => 'Admin is checking the concept.',
            'Treasurer Review' => 'Treasurer is checking the concept budget.',
            'BOD Agenda' => 'This legacy concept is being routed to full proposal preparation.',
            'Approved' => $project->proposal_reviewed_at
                ? 'The full proposal is approved. The Chair can lock the plan.'
                : 'The concept is approved. The Chair prepares the full proposal.',
            'Submitted for Formal Approval' => $project->proposal_budget_reviewed_at
                ? 'The full proposal budget is verified; the final Board decision is next.'
                : 'Treasurer is checking the full proposal budget.',
            'Endorsed for Development', 'Full Proposal Draft' => 'The Chair is preparing the full proposal.',
            'Returned for Revision' => 'The Chair is revising the full proposal from review notes.',
            'Needs Revision' => 'The concept needs changes before another review.',
            'Not Approved', 'Declined' => 'The concept was not approved.',
            default => 'Review the concept, budget, and full proposal.',
        };

        $definitions = [
            ['Project Idea', $status === 'Needs Revision' ? 'Revise the concept using reviewer feedback.' : 'Define the need and submit the concept.', 'overview'],
            ['Review & Approval', $current === 1 ? $reviewDetail : 'Review and approve the concept, budget, and proposal.',
                in_array($status, ['Admin Review', 'Submitted for President Review', 'Treasurer Review', 'BOD Agenda', 'Not Approved', 'Declined'], true) ? 'overview' : 'proposal'],
            ['Work Plan', $current === 2 ? 'Set deliverables, owners, and deadlines.' : 'Turn the approved plan into assigned work.', 'tasks'],
            ['Implementation', $current === 3
                ? ($totalTasks > 0
                    ? $completedTasks.' of '.$totalTasks.' tasks complete. Record activities and evidence.'
                    : 'Add project tasks and record delivery evidence.')
                : 'Carry out the project and document results.', 'delivery'],
            ['Reports & Evaluation', 'Submit the completion and liquidation reports, then review results.', 'completion'],
            ['Completed', 'Keep the final project record for learning and future reuse.', 'overview'],
        ];

        $steps = [];
        foreach ($definitions as $index => [$title, $detail, $anchor]) {
            $state = match (true) {
                $current === null => 'later',
                $index < $current => 'done',
                $index === $current && $closed => 'stopped',
                $index === $current && $current === 5 => 'finished',
                $index === $current => 'current',
                !$closed && $index === $current + 1 => 'next',
                default => 'later',
            };
            $steps[] = compact('title', 'detail', 'anchor', 'state') + [
                'reached' => in_array($state, ['done', 'stopped', 'current', 'finished'], true),
            ];
        }

        $position = match (true) {
            $current === null => 'Closed project record',
            $closed => 'Stopped at '.$steps[$current]['title'],
            $current === 5 => 'All 6 stages complete',
            default => 'Stage '.($current + 1).' of 6',
        };

        return compact('steps', 'position');
    }
}
