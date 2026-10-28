<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceFormDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_active_roles_can_find_and_download_the_original_word_forms(): void
    {
        foreach (['admin', 'bod', 'treasurer', 'member'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']));
            $page = $this->get('/documents/templates')->assertOk();
            foreach (config('reference_forms') as $key => $form) {
                $page->assertSee($form['title'])->assertSee(route('templates.reference.download', $key));
                $download = $this->get('/documents/templates/reference/'.$key)
                    ->assertOk()->assertDownload($form['file'])
                    ->assertHeader('X-Content-Type-Options', 'nosniff')
                    ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                $this->assertSame(
                    realpath(resource_path('forms/'.$form['file'])),
                    $download->baseResponse->getFile()->getRealPath()
                );
            }
        }
    }

    public function test_guests_inactive_accounts_and_unknown_forms_cannot_download(): void
    {
        $url = '/documents/templates/reference/project-proposal-form';
        $this->get($url)->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'member', 'status' => 'inactive']))
            ->get($url)->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'member', 'status' => 'active']))
            ->get('/documents/templates/reference/unknown-form')->assertNotFound();
        $this->get('/documents/templates/reference/.env')->assertNotFound();
    }
}
