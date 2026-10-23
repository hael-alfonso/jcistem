<?php

namespace App\Http\Controllers;

use App\Models\{RegistrationRequest, User};
use App\Services\ChapterService;
use App\Support\Permissions;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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
            'reason' => ['required', 'string', 'min:20', 'max:1500'],
            'password' => ['required', 'confirmed', Password::min(10)],
            'consent' => ['accepted'],
            'website' => ['nullable', 'max:0'],
        ]);

        $email = Str::lower(trim($data['email']));
        $username = Str::lower(trim($data['username']));
        if (User::whereRaw('LOWER(email) = ?', [$email])->exists() || User::whereRaw('LOWER(username) = ?', [$username])->exists()
            || RegistrationRequest::whereRaw('LOWER(email) = ?', [$email])->orWhereRaw('LOWER(username) = ?', [$username])->exists()) {
            throw ValidationException::withMessages(['email' => 'An account or request already uses this email or username. Please sign in or contact the chapter administrator.']);
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
                'reason' => trim($data['reason']),
                'password' => $data['password'],
                'consent_at' => now(),
                'status' => 'pending',
            ]);
            ChapterService::audit('registration_requested', $registration);
            return $registration;
        });

        $admins = User::where('role', 'admin')->where('status', 'active')->pluck('id');
        ChapterService::notify($admins, 'New member registration request', $registration->name.' requested a JCISTEM account.', route('registrations.show', $registration));

        return redirect()->route('register.thanks')->with('registration_reference', $registration->reference);
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('registration_reference')) return redirect()->route('register');
        return view('auth.register-thanks', ['reference' => $request->session()->get('registration_reference')]);
    }

    public function status(): View
    {
        return view('auth.register-status');
    }

    public function checkStatus(Request $request): View
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email:rfc', 'max:200'],
        ]);
        $registration = RegistrationRequest::where('reference', Str::upper(trim($data['reference'])))
            ->whereRaw('LOWER(email) = ?', [Str::lower(trim($data['email']))])->first();
        return view('auth.register-status', ['registrationStatus' => $registration?->status]);
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
                    'profile' => ['phone' => $registration->phone],
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

        return redirect()->route('registrations.show', $registration)->with('success', $data['decision'] === 'approve' ? 'Member account approved and activated.' : 'Registration request rejected.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(Permissions::allows($request->user(), Permissions::MEMBERS_MANAGE), 403);
    }
}
