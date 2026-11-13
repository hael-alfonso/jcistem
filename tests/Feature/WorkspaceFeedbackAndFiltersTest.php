<?php

namespace Tests\Feature;

use App\Http\Middleware\WorkspaceSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceFeedbackAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_uses_a_toast_and_warnings_use_a_dialog(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->withoutMiddleware(WorkspaceSession::class)->actingAs($user);
        $this->withSession(['success' => 'Saved successfully.'])->get('/dashboard')->assertOk()
            ->assertSee('data-toast', false)->assertSee('Saved successfully.')->assertDontSee('id="feedback-dialog"', false);
        $this->flushSession();
        $this->actingAs($user);
        $this->withSession(['warning' => 'Please review this change.'])->get('/dashboard')->assertOk()
            ->assertSee('id="feedback-dialog"', false)->assertSee('Please review this change.')->assertDontSee('data-toast-dismiss', false);
    }

    public function test_list_filters_accept_an_end_month_alone_and_reject_reversed_or_day_ranges(): void
    {
        foreach ([
            'admin' => ['/projects', '/tasks', '/documents', '/members', '/activity-log', '/records/reports', '/registrations', '/partner-directory'],
            'treasurer' => ['/ledger', '/expense-claims'],
        ] as $role => $paths) {
            $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']));
            foreach ($paths as $path) {
                $this->get($path.'?to=2026-11')->assertOk();
                $response = $this->get($path.'?from=2026-12&to=2026-11');
                $this->assertTrue($response->isRedirect(), $path.' must reject a reversed month range; received HTTP '.$response->status().'.');
                $this->get($path.'?to=2026-11-03')->assertRedirect();
            }
        }
    }
}
