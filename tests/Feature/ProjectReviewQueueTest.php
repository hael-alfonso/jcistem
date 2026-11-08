<?php

namespace Tests\Feature;

use App\Models\{Project, ProjectReport, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectReviewQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_queues_show_only_actionable_work_for_each_role(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'status' => 'active']);
        $board = User::factory()->create(['role' => 'bod', 'status' => 'active']);
        $project = fn (string $title, string $status, array $extra = []) => Project::create($extra + [
            'created_by' => $owner->id, 'chair_id' => $owner->id, 'title' => $title,
            'area' => 'Community Impact', 'status' => $status,
        ]);
        $concept = $project('Concept awaiting Admin', 'Admin Review');
        $agenda = $project('Concept on BOD agenda', 'BOD Agenda');
        $budget = $project('Concept awaiting Treasurer', 'Treasurer Review');
        $formalPending = $project('Full proposal awaiting Treasurer', 'Submitted for Formal Approval');
        $formalVerified = $project('Full proposal ready for Admin', 'Submitted for Formal Approval', ['proposal_budget_reviewed_at' => now()]);
        $completion = $project('Completion report ready for Admin', 'Completion Review');
        $liquidation = $project('Liquidation ready for Treasurer', 'Completion Review');
        $selfReview = $project('Treasurer own concept', 'Treasurer Review', ['created_by' => $treasurer->id]);
        ProjectReport::create(['project_id' => $completion->id, 'created_by' => $owner->id, 'title' => 'Completion', 'type' => 'Completion', 'status' => 'Submitted']);
        ProjectReport::create(['project_id' => $liquidation->id, 'created_by' => $owner->id, 'title' => 'Liquidation', 'type' => 'Liquidation', 'status' => 'Submitted']);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertViewHas('pending', fn ($items) => $items->pluck('id')->sort()->values()->all() === collect([$concept, $agenda, $formalVerified, $completion])->pluck('id')->sort()->values()->all());
        $this->get(route('projects', ['filter' => 'review']))->assertOk()
            ->assertSee($concept->title)->assertSee($agenda->title)->assertSee($formalVerified->title)->assertSee($completion->title)
            ->assertDontSee($formalPending->title)->assertDontSee($budget->title)->assertDontSee($liquidation->title);

        $this->actingAs($treasurer)->get(route('dashboard'))->assertOk()
            ->assertViewHas('pending', fn ($items) => $items->pluck('id')->sort()->values()->all() === collect([$budget, $formalPending, $liquidation])->pluck('id')->sort()->values()->all());
        $this->get(route('projects', ['filter' => 'review']))->assertOk()
            ->assertSee($budget->title)->assertSee($formalPending->title)->assertSee($liquidation->title)
            ->assertDontSee($formalVerified->title)->assertDontSee($selfReview->title);

        $this->actingAs($board)->get(route('dashboard'))->assertOk()
            ->assertViewHas('pending', fn ($items) => $items->pluck('id')->all() === [$agenda->id]);
        $this->get(route('projects', ['filter' => 'review']))->assertOk()->assertSee($agenda->title)->assertDontSee($concept->title);

        $this->actingAs($admin)->get(route('projects', ['filter' => 'concepts']))->assertOk()
            ->assertSee($concept->title)->assertSee($agenda->title)->assertSee($budget->title)
            ->assertDontSee($formalPending->title);
    }
}
