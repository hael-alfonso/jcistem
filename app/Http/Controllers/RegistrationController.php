<?php

namespace App\Http\Controllers;

use App\Models\{RegistrationRequest, User};
use App\Services\ChapterService;
use App\Support\Permissions;
use App\Support\ChapterForms;
use App\Support\MemberId;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{DB, Storage};
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
        if ($request->filled('given_name') || $request->filled('family_name')) {
            $request->merge(['name' => trim($request->string('given_name').' '.$request->string('family_name'))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:200'],
            'given_name' => ['nullable', 'required_with:family_name', 'string', 'max:100'],
            'family_name' => ['nullable', 'required_with:given_name', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
            'email' => ['required', 'email:rfc', 'max:200'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'home_phone' => ['nullable', 'string', 'max:40'],
            'title' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'position' => ['nullable', 'string', 'max:160'],
            'business_sector' => ['nullable', 'string', 'max:160'],
            'university_major' => ['nullable', 'string', 'max:160'],
            'occupation' => ['nullable', Rule::in(['Student', 'Employed', 'Self-employed', 'Other'])],
            'organization' => ['nullable', 'string', 'max:200'],
            'interests' => ['nullable', 'array', 'max:4'],
            'interests.*' => [Rule::in(ChapterForms::AREAS)],
            'interest_topics' => ['nullable', 'array', 'max:10'],
            'interest_topics.*' => [Rule::in(ChapterForms::MEMBERSHIP_INTERESTS)],
            'motivation' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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

        $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('registration-photos', 'local') : null;
        if ($request->hasFile('photo') && !$photoPath) {
            throw ValidationException::withMessages(['photo' => 'The application photo could not be saved. Please try again.']);
        }
        try {
        $registration = DB::transaction(function () use ($data, $email, $username, $photoPath) {
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
                    'city' => trim($data['city'] ?? ''),
                    'home_phone' => trim($data['home_phone'] ?? ''),
                    'given_name' => trim($data['given_name'] ?? ''),
                    'family_name' => trim($data['family_name'] ?? ''),
                    'title' => trim($data['title'] ?? ''),
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'position' => trim($data['position'] ?? ''),
                    'business_sector' => trim($data['business_sector'] ?? ''),
                    'university_major' => trim($data['university_major'] ?? ''),
                    'occupation' => $data['occupation'] ?? null,
                    'organization' => trim($data['organization'] ?? ''),
                    'interests' => array_values(array_unique($data['interests'] ?? [])),
                    'interest_topics' => array_values(array_unique($data['interest_topics'] ?? [])),
                    'photo_path' => $photoPath,
                ],
                'password' => $data['password'],
                'consent_at' => now(),
                'status' => 'pending',
            ]);
            ChapterService::audit('registration_requested', $registration);
            return $registration;
        });
        } catch (\Throwable $exception) {
            if ($photoPath) Storage::disk('local')->delete($photoPath);
            throw $exception;
        }

        $admins = User::where('role', 'admin')->where('status', 'active')->pluck('id');
        ChapterService::notify($admins, 'New member registration', $registration->name.' registered for a JCISTEM account. Review it before activation.', route('registrations.show', $registration), true);

        return redirect()->route('register.thanks')->with('registration_reference', $registration->reference);
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('registration_reference')) return redirect()->route('register');
        return view('auth.register-thanks');
    }

    public function index(Request $request): View
    {
        $this->authorizeMemberManager($request);
        $status = $request->validate(['status' => 'nullable|in:pending,approved,rejected', 'q' => 'nullable|string|max:200', 'from' => 'nullable|date', 'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : '')])['status'] ?? 'pending';
        return view('chapter.registrations', [
            'requests' => RegistrationRequest::with('reviewer')->where('status', $status)
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($match) => $match->where('name', 'like', '%'.$request->string('q').'%')->orWhere('email', 'like', '%'.$request->string('q').'%')->orWhere('reference', 'like', '%'.$request->string('q').'%')))
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('to')))
                ->latest()->latest('id')->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Request $request, RegistrationRequest $registration): View
    {
        $this->authorizeMemberManager($request);
        return view('chapter.registration', compact('registration'));
    }

    public function photo(Request $request, RegistrationRequest $registration)
    {
        $this->authorizeMemberManager($request);
        $path = $registration->application_details['photo_path'] ?? null;
        abort_unless($path && str_starts_with($path, 'registration-photos/') && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function review(Request $request, RegistrationRequest $registration): RedirectResponse
    {
        abort_unless(Permissions::allows($request->user(), Permissions::ACCOUNTS_MANAGE), 403);
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
                        'city' => $registration->application_details['city'] ?? '',
                        'home_phone' => $registration->application_details['home_phone'] ?? '',
                        'given_name' => $registration->application_details['given_name'] ?? '',
                        'family_name' => $registration->application_details['family_name'] ?? '',
                        'title' => $registration->application_details['title'] ?? '',
                        'date_of_birth' => $registration->application_details['date_of_birth'] ?? null,
                        'position' => $registration->application_details['position'] ?? '',
                        'business_sector' => $registration->application_details['business_sector'] ?? '',
                        'university_major' => $registration->application_details['university_major'] ?? '',
                        'occupation' => $registration->application_details['occupation'] ?? null,
                        'organization' => $registration->application_details['organization'] ?? '',
                        'interests' => $registration->application_details['interests'] ?? [],
                        'interest_topics' => $registration->application_details['interest_topics'] ?? [],
                    ],
                ]);
                $photoPath = $registration->application_details['photo_path'] ?? null;
                if ($photoPath && str_starts_with($photoPath, 'registration-photos/') && Storage::disk('local')->exists($photoPath)) {
                    $destination = 'profile-photos/'.$member->id.'/'.basename($photoPath);
                    Storage::disk('local')->makeDirectory('profile-photos/'.$member->id);
                    if (!Storage::disk('local')->copy($photoPath, $destination)) {
                        throw ValidationException::withMessages(['decision' => 'The applicant photo could not be saved to the member record. Please retry approval.']);
                    }
                    $profile = $member->profile;
                    $profile['photo_path'] = $destination;
                    $member->profile = $profile;
                    $member->save();
                }
                MemberId::assign($member);
                $registration->user_id = $member->id;
                $registration->status = 'approved';
                ChapterService::audit('member_account_approved', $member);
                ChapterService::notify([$member->id], 'Membership approved', 'Your JCI Carmona member account is active. Welcome to the chapter workspace.', route('account', [], false));
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

    private function authorizeMemberManager(Request $request): void
    {
        abort_unless(Permissions::allows($request->user(), Permissions::MEMBERS_MANAGE), 403);
    }
}
