<?php

namespace Tests\Feature;

use App\Models\{Project, ProjectReview, User};
use App\Support\ProjectFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_six_stages_follow_the_actual_review_task_delivery_and_reporting_states(): void
    {
        $project = new Project(['status' => 'Draft Concept']);
        $tracker = ProjectFlow::tracker($project);

        $this->assertSame(['Project Idea', 'Review & Approval', 'Work Plan', 'Implementation', 'Reports & Evaluation', 'Completed'], array_column($tracker['steps'], 'title'));
        $this->assertSame(['current', 'next', 'later', 'later', 'later', 'later'], array_column($tracker['steps'], 'state'));
        $this->assertSame('Stage 1 of 6', $tracker['position']);

        $project->status = 'Approved';
        $tracker = ProjectFlow::tracker($project);
        $this->assertSame('current', $tracker['steps'][1]['state']);
        $this->assertStringContainsString('concept is approved', $tracker['steps'][1]['detail']);
        $this->assertSame('proposal', $tracker['steps'][1]['anchor']);

        $project->proposal_reviewed_at = now();
        $this->assertStringContainsString('full proposal is approved', ProjectFlow::tracker($project)['steps'][1]['detail']);

        $project->status = 'Plan Locked';
        $this->assertSame('current', ProjectFlow::tracker($project)['steps'][2]['state']);
        $project->status = 'Ongoing';
        $this->assertSame('2 of 3 tasks complete. Record activities and evidence.', ProjectFlow::tracker($project, 2, 3)['steps'][3]['detail']);
        $project->status = 'Completion Review';
        $this->assertSame('current', ProjectFlow::tracker($project)['steps'][4]['state']);
        $project->status = 'Completed';
        $this->assertSame('finished', ProjectFlow::tracker($project)['steps'][5]['state']);
        $this->assertSame('All 6 stages complete', ProjectFlow::tracker($project)['position']);
    }

    public function test_cancelled_project_stops_at_its_last_reached_stage(): void
    {
        $project = new Project(['status' => 'Cancelled']);
        $project->setRelation('reviews', collect([
            new ProjectReview(['id' => 4, 'action' => 'cancel', 'from_status' => 'Ongoing']),
        ]));

        $tracker = ProjectFlow::tracker($project);
        $this->assertSame('Stopped at Implementation', $tracker['position']);
        $this->assertSame(['done', 'done', 'done', 'stopped', 'later', 'later'], array_column($tracker['steps'], 'state'));
        $this->assertFalse($tracker['steps'][4]['reached']);
    }

    public function test_project_page_keeps_tracker_stages_static_and_cancel_control_separate(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $project = Project::create(['created_by' => $admin->id, 'title' => 'Stage navigation check', 'status' => 'Draft Concept']);

        $response = $this->actingAs($admin)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Follow the project from idea to completion.')
            ->assertSee('aria-current="step"', false)
            ->assertSee('project-cancel-wrap', false)
            ->assertSee('project-cancel-label', false);

        preg_match('/<ol class="project-stage-list".*?<\/ol>/s', $response->getContent(), $trackerMarkup);
        $this->assertNotEmpty($trackerMarkup);
        $this->assertStringNotContainsString('<a ', $trackerMarkup[0]);
    }
}
