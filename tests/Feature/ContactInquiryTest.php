<?php

namespace Tests\Feature;

use App\Models\{AuditLog, ContactInquiry, Notification, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_contact_form_creates_an_admin_inquiry_and_reference(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->get('/contact')->assertOk()->assertSee('Let’s start a')->assertSee('Send inquiry');
        $response = $this->post('/contact', [
            'name' => '  Maria Santos  ',
            'email' => 'MARIA@EXAMPLE.TEST',
            'phone' => '0917 123 4567',
            'organization' => 'Carmona Youth Council',
            'category' => 'partnership',
            'subject' => 'Community project partnership',
            'message' => 'We would like to discuss a community learning activity with JCI Carmona next quarter.',
            'consent' => '1',
            'website' => '',
        ]);

        $response->assertRedirect('/contact/thank-you');
        $response->assertSessionHas('inquiry_reference');
        $inquiry = ContactInquiry::firstOrFail();
        $this->assertSame('Maria Santos', $inquiry->name);
        $this->assertSame('maria@example.test', $inquiry->email);
        $this->assertMatchesRegularExpression('/^JCI-INQ-\d{4}-[A-Z0-9]{6}$/', $inquiry->reference);
        $this->assertNotNull($inquiry->ip_hash);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'title' => 'New public inquiry']);
    }

    public function test_only_admin_can_review_and_update_inquiries(): void
    {
        $inquiry = ContactInquiry::create([
            'reference' => 'JCI-INQ-2026-ABC123', 'name' => 'Public Sender', 'email' => 'sender@example.test',
            'category' => 'membership', 'subject' => 'Membership question',
            'message' => 'I would like to learn more about membership and the next orientation schedule.',
            'consent_at' => now(),
        ]);
        $member = User::factory()->create(['role' => 'member', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($member)->get('/inquiries')->assertForbidden();
        $this->actingAs($member)->get('/inquiries/'.$inquiry->id)->assertForbidden();

        $this->actingAs($admin)->get('/inquiries')->assertOk()->assertSee('Membership question')->assertSee($inquiry->reference);
        $this->actingAs($admin)->put('/inquiries/'.$inquiry->id, [
            'status' => 'in_progress', 'assigned_to' => $admin->id, 'internal_notes' => 'Reply requested from the membership team.',
        ])->assertRedirect();
        $this->assertDatabaseHas('contact_inquiries', ['id' => $inquiry->id, 'status' => 'in_progress', 'assigned_to' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'public_inquiry_updated', 'object_id' => $inquiry->id]);
        $this->assertStringNotContainsString('sender@example.test', json_encode(AuditLog::latest()->first()->toArray()));
    }

    public function test_contact_form_rejects_spam_field_and_incomplete_messages(): void
    {
        $this->post('/contact', [
            'name' => 'Bot', 'email' => 'bot@example.test', 'category' => 'other', 'subject' => 'Spam',
            'message' => 'This message is long enough to pass the message length rule.', 'consent' => '1', 'website' => 'https://spam.test',
        ])->assertSessionHasErrors('website');
        $this->assertDatabaseCount('contact_inquiries', 0);
    }
}
