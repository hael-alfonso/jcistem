<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Facades\Storage;

final class ProjectSubmission
{
    /** @return array<string, string> */
    public static function issues(Project $project, string $stage): array
    {
        $issues = [];
        $method = $stage === 'proposal' ? $project->proposal_submission_method : $project->concept_submission_method;
        if ($method === 'upload') {
            $latest = $project->documents()->where('submission_stage', $stage)->latest('version')->first();
            if (!$latest?->path || !Storage::disk('local')->exists($latest->path)) {
                $issues['action'] = $stage === 'proposal'
                    ? 'Upload the completed full proposal form first.'
                    : 'Upload the completed concept proposal template first.';
            } elseif ($latest->project_revision !== null && (int) $latest->project_revision !== (int) $project->revision) {
                $issues['action'] = 'Project details changed after the last upload. Upload the updated completed form before submitting.';
            }
        } else {
            $answers = $stage === 'proposal' ? ($project->proposal ?? []) : ($project->concept ?? []);
            $required = $stage === 'proposal' ? ChapterForms::PROPOSAL_REQUIRED : ChapterForms::CONCEPT_REQUIRED;
            foreach ($required as $key => $label) {
                if (trim((string) ($answers[$key] ?? '')) === '') {
                    $issues[$stage.'.'.$key] = $label.' is required before submission.';
                }
            }
        }

        if ($stage === 'concept') {
            if ($project->status === 'Needs Revision') {
                $returned = $project->reviews()->whereIn('action', ['revise_concept', 'revise_budget'])->latest('id')->first();
                if ($returned && (int) $project->revision <= (int) ($returned->snapshot['revision'] ?? 0)) {
                    $issues['action'] = 'Update the concept or budget in response to the review before resubmitting.';
                }
            }
            if (!$project->starts_on || !trim((string) $project->venue)) {
                $issues['project_details'] = 'Set the proposed date and venue first.';
            }
        } else {
            if (in_array($project->status, ['Returned for Revision', 'Approved'], true) && !$project->proposal_reviewed_at) {
                $returned = $project->reviews()->where('action', 'return_full_proposal')->latest('id')->first();
                if ($returned && (int) $project->revision <= (int) ($returned->snapshot['revision'] ?? 0)) {
                    $issues['action'] = 'Update the full proposal in response to the review before resubmitting.';
                }
            }
            if (!$project->budget_items && Money::cents($project->proposed_budget) > 0) {
                $issues['budget_items'] = 'Itemize the proposed expenses before formal review.';
            } elseif ($project->budget_items && Money::cents(ProjectBudget::total($project->budget_items)) !== Money::cents($project->proposed_budget)) {
                $issues['budget_items'] = 'The expense breakdown must equal the proposed budget.';
            }
        }

        return $issues;
    }
}
