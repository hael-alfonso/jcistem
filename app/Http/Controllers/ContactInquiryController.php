<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, ContactInquiry, User};
use App\Services\ChapterService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactInquiryController extends Controller
{
    public function create(): View
    {
        return view('contact', ['categories' => ContactInquiry::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:200'],
            'phone' => ['nullable', 'string', 'max:40'],
            'organization' => ['nullable', 'string', 'max:160'],
            'category' => ['required', Rule::in(array_keys(ContactInquiry::CATEGORIES))],
            'subject' => ['required', 'string', 'min:4', 'max:180'],
            'message' => ['required', 'string', 'min:30', 'max:5000'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ], [
            'consent.accepted' => 'Please confirm that JCI Carmona may use these details to respond to your inquiry.',
            'website.max' => 'Your inquiry could not be submitted. Please try again.',
        ]);

        $inquiry = DB::transaction(function () use ($data, $request) {
            do {
                $reference = 'JCI-INQ-'.now()->format('Y').'-'.Str::upper(Str::random(6));
            } while (ContactInquiry::where('reference', $reference)->exists());

            return ContactInquiry::create([
                'reference' => $reference,
                'name' => trim($data['name']),
                'email' => Str::lower(trim($data['email'])),
                'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                'organization' => filled($data['organization'] ?? null) ? trim($data['organization']) : null,
                'category' => $data['category'],
                'subject' => trim($data['subject']),
                'message' => trim($data['message']),
                'consent_at' => now(),
                'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);
        });

        $admins = User::where('role', 'admin')->where('status', 'active')->pluck('id');
        ChapterService::notify($admins, 'New public inquiry', $inquiry->name.' sent a '.ContactInquiry::CATEGORIES[$inquiry->category].'.', route('inquiries.show', $inquiry));

        return redirect()->route('contact.thanks')->with('inquiry_reference', $inquiry->reference);
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('inquiry_reference')) {
            return redirect()->route('contact');
        }

        return view('contact-thanks', ['reference' => $request->session()->get('inquiry_reference')]);
    }

    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_keys(ContactInquiry::STATUSES))],
            'category' => ['nullable', Rule::in(array_keys(ContactInquiry::CATEGORIES))],
        ]);
        $query = ContactInquiry::query()->with('assignee')->latest();
        if (filled($data['q'] ?? null)) {
            $term = '%'.$data['q'].'%';
            $query->where(fn ($q) => $q->where('reference', 'like', $term)->orWhere('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('subject', 'like', $term));
        }
        if (filled($data['status'] ?? null)) $query->where('status', $data['status']);
        if (filled($data['category'] ?? null)) $query->where('category', $data['category']);

        return view('chapter.inquiries', [
            'inquiries' => $query->paginate(20)->withQueryString(),
            'newCount' => ContactInquiry::where('status', 'new')->count(),
            'categories' => ContactInquiry::CATEGORIES,
            'statuses' => ContactInquiry::STATUSES,
        ]);
    }

    public function show(ContactInquiry $inquiry): View
    {
        return view('chapter.inquiry', [
            'inquiry' => $inquiry->load('assignee'),
            'admins' => User::where('role', 'admin')->where('status', 'active')->orderBy('name')->get(),
            'categories' => ContactInquiry::CATEGORIES,
            'statuses' => ContactInquiry::STATUSES,
        ]);
    }

    public function update(Request $request, ContactInquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ContactInquiry::STATUSES))],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'admin')->where('status', 'active'))],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $before = $inquiry->only(['status', 'assigned_to']);
        $inquiry->update([
            ...$data,
            'resolved_at' => $data['status'] === 'resolved' ? ($inquiry->resolved_at ?? now()) : null,
        ]);
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'public_inquiry_updated',
            'object_type' => 'ContactInquiry',
            'object_id' => $inquiry->id,
            'before' => $before,
            'after' => $inquiry->only(['status', 'assigned_to']),
            'remarks' => $inquiry->reference,
        ]);

        return back()->with('success', 'Inquiry updated.');
    }
}
