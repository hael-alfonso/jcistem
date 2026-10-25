<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicChapterPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_can_browse_and_read_public_stories(): void
    {
        $this->get('/activities')->assertOk()->assertSee('Project LAPIS')->assertSee('Bags2School 2026')->assertSee('How to Spot the Red Heart')->assertSee('Reported 1 March 2025')->assertDontSee('Apply filters');
        foreach (config('chapter_stories') as $story) {
            $this->get('/activities/'.$story['slug'])->assertOk()->assertSee($story['title'])->assertSee($story['source'])->assertSee($story['chapter_role']);
            if ($story['image']) $this->assertFileExists(public_path('assets/images/community/'.$story['image']));
        }
        $this->get('/activities/not-a-project')->assertNotFound();
    }

    public function test_public_pages_and_inquiry_links_work_without_member_login(): void
    {
        $this->get('/')->assertOk()->assertSee('/get-involved');
        $this->get('/get-involved')->assertOk()->assertSee('Explore membership')->assertSee('Project IVAN')->assertSee('administrator approval');
        $this->get('/our-chapter')->assertOk()->assertSee('Learn together.');
        $this->get('/partners')->assertOk()->assertSee('Layag Kabataan')->assertSee('Barangay Lantic');
        foreach (config('chapter_partners') as $partner) {
            if ($partner['image']) $this->assertFileExists(public_path('assets/images/partners/'.$partner['image']));
        }
        $this->get('/contact')->assertOk()->assertSee('0967 899 4639')->assertSee('tel:+639678994639', false)
            ->assertSee('mailto:jcicarmona2024@gmail.com', false)->assertSee('Carmona, Philippines, 4116');
        $this->get('/contact?category=project&subject=Project%20IVAN%20inquiry')->assertOk()->assertSee('value="Project IVAN inquiry"', false);
        $this->get('/workspaces/208829c7-31a6-4287-9528-732f1548e9e7/activities')->assertOk()
            ->assertSee('/workspaces/208829c7-31a6-4287-9528-732f1548e9e7/activities/project-lapis');
    }
}
