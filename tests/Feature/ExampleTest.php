<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_can_read_the_public_chapter_homepage(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Lead with purpose.')
            ->assertSee('Member sign in')
            ->assertSee('href="'.route('login').'"', false);
    }
}
