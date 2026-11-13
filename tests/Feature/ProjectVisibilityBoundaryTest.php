<?php

namespace Tests\Feature;

use App\Models\{Project, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectVisibilityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_drafts_are_private_from_all_projects_but_review_onward_is_visible_to_every_active_member(): void
    {
        $owner = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $treasurer = User::factory()->create(['role' => 'treasurer', 'status' => 'active']);
        $board = User::factory()->create(['role' => 'bod', 'status' => 'active']);
        $project = fn (string $title, string $status) => Project::create([
            'created_by' => $owner->id, 'title' => $title, 'status' => $status, 'area' => 'Community Impact',
        ]);
        $conceptDraft = $project('Private concept draft', 'Draft Concept');
        $proposalDraft = $project('Private proposal draft', 'Full Proposal Draft');
        $review = $project('Submitted concept for chapter monitoring', 'Admin Review');
        $cancelled = $project('Closed project for chapter monitoring', 'Cancelled');
        $cancelled->update(['archived_at' => now()]);
        $completed = $project('Completed project for chapter monitoring', 'Completed');

        $this->actingAs($member)->get(route('projects'))->assertOk()->assertViewHas('projects', fn ($items) =>
            $items->pluck('id')->sort()->values()->all() === collect([$review, $cancelled, $completed])->pluck('id')->sort()->values()->all());
        $this->get(route('projects.show', $conceptDraft))->assertForbidden();
        $this->get(route('projects.show', $proposalDraft))->assertForbidden();
        $this->get(route('projects.show', $review))->assertOk();
        $this->get(route('projects.show', $cancelled))->assertOk()->assertDontSee('Send appeal to Admin');
        $this->get(route('projects.show', $completed))->assertOk();
        $this->get(route('projects', ['filter' => 'mine']))->assertOk()->assertViewHas('projects', fn ($items) => $items->isEmpty());

        foreach ([$owner, $admin, $treasurer, $board] as $allowed) {
            $this->actingAs($allowed)->get(route('projects.show', $conceptDraft))->assertOk();
            $this->get(route('projects.show', $proposalDraft))->assertOk();
            $this->get(route('projects'))->assertOk()->assertViewHas('projects', fn ($items) =>
                !$items->pluck('id')->contains($conceptDraft->id) && !$items->pluck('id')->contains($proposalDraft->id));
        }

        $this->actingAs($owner)->get(route('projects', ['filter' => 'mine']))->assertOk()->assertViewHas('projects', fn ($items) =>
            $items->pluck('id')->contains($conceptDraft->id) && $items->pluck('id')->contains($cancelled->id));
        foreach ([$admin, $treasurer, $board] as $reviewer) {
            $this->actingAs($reviewer)->get(route('projects', ['filter' => 'review']))->assertOk()->assertViewHas('projects', fn ($items) =>
                $items->pluck('id')->contains($conceptDraft->id) && $items->pluck('id')->contains($proposalDraft->id));
        }
    }
}
