<?php

namespace App\Http\Controllers;

use App\Models\{RegistrationRequest, User};
use App\Services\ChapterService;
use App\Support\Permissions;
use App\Support\ChapterForms;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
            'email' => ['required', 'email:rfc', 'max:200'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:500'],
            'occupation' => ['nullable', Rule::in(['Student', 'Employed', 'Self-employed', 'Other'])],
            'organization' => ['nullable', 'string', 'max:200'],
            'interests' => ['nullable', 'array', 'max:4'],
            'interests.*' => [Rule::in(ChapterForms::AREAS)],
            'motivation' => ['nullable', 'string', 'max:1000'],
            'password' => ['required', 'confirmed', Password::min(10)],
            'consent' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ]);

        $email = Str::lower(trim($data['email']));
        $username = Str::lower(trim($data['username']));
        if (User::whereRaw('LOWER(email) = ?', [$email])->exists() || User::whereRaw('LOWER(username) = ?', [$username])->exists()
            || RegistrationRequest::whereRaw('LOWER(email) = ?', [$email])->orWhereRaw('LOWER(username) = ?', [$username])->exists()) {
            throw ValidationException::withMessages(['email' => 'This email or username is already in use or awaiting approval. Please sign in or contact the chapter administrator.']);
        }

        $registration = DB::transaction(function () use ($data, $email, $username) {
            do {
                $reference = 'JCI-REG-'.now()->format('Y').'-'.Str::upper(Str::random(6));
            } while (RegistrationRequest::where('reference', $reference)->exists());

            $registration = RegistrationRequest::create([
                'reference' => $reference,
                'name' => trim($data['name']),
                'username' => $username,
                'email' => $email,
                'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                'reason' => trim($data['motivation'] ?? '') ?: 'Membership application and member portal registration',
                'application_details' => [
                    'address' => trim($data['address'] ?? ''),
                    'occupation' => $data['occupation'] ?? null,
                    'organization' => trim($data['organization'] ?? ''),
                    'interests' => array_values(array_unique($data['interests'] ?? [])),
                ],
                'password' => $data['password'],
                'consent_at' => now(),
                'status' => 'pending',
            ]);
            ChapterService::audit('registration_requested', $registration);
            return $registration;
        });

        $admins = User::where('role', 'admin')->where('status', 'active')->pluck('id');
        ChapterService::notify($admins, 'New member registration', $registration->name.' registered for a JCISTEM account. Review it before activation.', route('registrations.show', $registration));

        return redirect()->route('register.thanks')->with('registration_reference', $registration->reference);
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('registration_reference')) return redirect()->route('register');
        return view('auth.register-thanks');
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $status = $request->validate(['status' => 'nullable|in:pending,approved,rejected'])['status'] ?? 'pending';
        return view('chapter.registrations', [
            'requests' => RegistrationRequest::with('reviewer')->where('status', $status)->latest()->paginate(20)->withQueryString(),
            'status' => $status,
            'pendingCount' => RegistrationRequest::where('status', 'pending')->count(),
        ]);
    }

    public function show(Request $request, RegistrationRequest $registration): View
    {
        $this->authorizeAdmin($request);
        return view('chapter.registration', compact('registration'));
    }

    public function review(Request $request, RegistrationRequest $registration): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'review_notes' => 'required_if:decision,reject|nullable|string|max:2000',
            'verified_identity' => 'accepted_if:decision,approve',
        ]);

        DB::transaction(function () use ($request, $registration, $data) {
            $registration = RegistrationRequest::lockForUpdate()->findOrFail($registration->id);
            if ($registration->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'This request has already been reviewed.']);
            }

            $before = $registration->toArray();
            if ($data['decision'] === 'approve') {
                if (User::whereRaw('LOWER(email) = ?', [$registration->email])->exists()
                    || User::whereRaw('LOWER(username) = ?', [$registration->username])->exists()) {
                    throw ValidationException::withMessages(['decision' => 'This email or username is already in use. Resolve the duplicate before approval.']);
                }
                $member = User::create([
                    'name' => $registration->name,
                    'username' => $registration->username,
                    'email' => $registration->email,
                    'password' => $registration->password,
                    'role' => 'member',
                    'status' => 'active',
                    'profile' => [
                        'phone' => $registration->phone,
                        'address' => $registration->application_details['address'] ?? '',
                        'occupation' => $registration->application_details['occupation'] ?? null,
                        'organization' => $registration->application_details['organization'] ?? '',
                        'interests' => $registration->application_details['interests'] ?? [],
                    ],
                ]);
                $registration->user_id = $member->id;
                $registration->status = 'approved';
                ChapterService::audit('member_account_approved', $member);
            } else {
                $registration->status = 'rejected';
            }

            // The password is needed only until the decision; never retain it in the review archive.
            $registration->password = null;
            $registration->reviewed_by = $request->user()->id;
            $registration->reviewed_at = now();
            $registration->review_notes = trim($data['review_notes'] ?? '') ?: null;
            $registration->save();
            ChapterService::audit('registration_'.$registration->status, $registration, $before);
        });

        return redirect()->route('registrations.show', $registration)->with('success', $data['decision'] === 'approve' ? 'Member account approved and activated.' : 'Registration not approved.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(Permissions::allows($request->user(), Permissions::MEMBERS_MANAGE), 403);
    }
}
