<?php
namespace App\Http\Controllers;

use App\Models\{Project, Loi, ProjectReport, LedgerEntry, User};
use App\Services\ChapterService;
use App\Support\ChapterForms;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Storage};
use Illuminate\Validation\{Rule, Rules\Password, ValidationException};

class RecordController extends Controller
{
    private function model(string $kind): string { abort_unless(in_array($kind, ['letters', 'reports']), 404); return $kind === 'letters' ? Loi::class : ProjectReport::class; }

    private function editable($record, Request $request): bool
    {
        return in_array($record->status, ['Draft', 'Returned'], true) && ($record->project_id ? ($record->project->canManage($request->user()) || $record->project->canReport($request->user())) : Permissions::allows($request->user(), Permissions::FINANCE_MANAGE));
    }

    private function visible($record, Request $request): bool
    {
        if (!$record->project_id) return Permissions::allows($request->user(), Permissions::FINANCE_MANAGE);
        if ($record instanceof ProjectReport && $record->type === 'Liquidation') return $record->project->isChair($request->user()) || Permissions::allows($request->user(), Permissions::FINANCE_MANAGE);
        return ChapterService::canView($record->project, $request->user()) && ($record->project->chair_id === $request->user()->id || in_array($request->user()->role, ['admin', 'bod']) || in_array($record->status, ['Reviewed', 'Approved for Sending', 'Sent', 'Archived']));
    }

    public function index(Request $request, string $kind)
    {
        $class = $this->model($kind);
        $request->validate(['q' => 'nullable|string|max:200', 'status' => 'nullable|string|max:80', 'type' => ['nullable', Rule::in(['Progress', 'Completion', 'Liquidation', 'Overall Financial'])]]);
        $user = $request->user();
        $query = $class::with(['project', 'author'])->where(function ($q) use ($user, $kind) {
            if ($user->role === 'treasurer') $q->whereNull('project_id')->orWhereNotNull('project_id');
            else $q->whereNotNull('project_id');
        })->where(function ($q) use ($user) {
            $q->whereNull('project_id')->orWhereHas('project', function ($p) use ($user) {
                if ($user->role === 'member') $p->where(fn ($v) => $v->whereIn('status', Project::APPROVED)->orWhere('created_by', $user->id));
            });
        })->where(function ($q) use ($user, $kind) {
            if (in_array($user->role, ['admin', 'bod'])) {
                if ($kind === 'reports') $q->where('type', '!=', 'Liquidation')->orWhereHas('project', fn ($p) => $p->where('chair_id', $user->id));
            } elseif ($user->role !== 'treasurer') {
                $q->whereHas('project', fn ($p) => $p->where('chair_id', $user->id))->orWhere(function ($v) use ($kind) {
                    $v->whereIn('status', ['Reviewed', 'Approved for Sending', 'Sent', 'Archived']);
                    if ($kind === 'reports') $v->where('type', '!=', 'Liquidation');
                });
            } elseif ($kind === 'reports') {
                $q->whereNull('project_id')->orWhere('type', 'Liquidation')->orWhereHas('project', fn ($p) => $p->where('chair_id', $user->id))->orWhereIn('status', ['Reviewed', 'Archived']);
            } elseif ($kind === 'letters') {
                $q->whereHas('project', fn ($p) => $p->where('chair_id', $user->id))->orWhereIn('status', ['Reviewed', 'Approved for Sending', 'Sent', 'Archived']);
            }
        });
        $records = $query->when($request->boolean('mine'), fn ($q) => $q->whereHas('project', fn ($p) => $p->where('chair_id', $user->id)))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%')->orWhereHas('project', fn ($p) => $p->where('title', 'like', '%'.$request->string('q').'%'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($kind === 'reports' && $request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()->paginate(25)->withQueryString();
        $projects = Project::where('chair_id', $request->user()->id)->whereIn('status', ['Plan Locked', 'Ongoing', 'Completion Review'])->orderBy('title')->get();
        return view('chapter.records', compact('records', 'projects', 'kind'));
    }

    public function create(Request $request, string $kind)
    {
        $class = $this->model($kind);
        $request->validate(['project_id' => 'nullable|integer|exists:projects,id', 'type' => ['nullable', Rule::in(['Progress', 'Completion', 'Liquidation'])]]);
        $project = $request->filled('project_id') ? Project::findOrFail($request->project_id) : null;
        if ($project) abort_unless($project->canManage($request->user()) || $project->canReport($request->user()), 403);
        else abort_unless($kind === 'reports' && Permissions::allows($request->user(), Permissions::FINANCE_MANAGE), 403);
        $reportType = $kind === 'reports' && $project ? ($request->query('type') ?: 'Progress') : null;
        $record = new $class([
            'project_id' => $project?->id,
            'title' => $kind === 'letters' ? 'Project Proposal and Invitation for Partnership – '.$project->title : null,
            'type' => $project ? ($kind === 'letters' ? 'JCI LOI' : $reportType) : 'Overall Financial',
            'data' => $kind === 'letters' ? [
                'letter_date' => now()->format('Y-m-d'),
                'purpose' => $project->proposal['rationale'] ?? '',
                'project_action' => $project->proposal['description'] ?? '',
                'beneficiary_count' => $project->concept['participants'] ?? '',
                'signatory' => $project->chair?->name ?? '',
            ] : [],
        ]);
        return view('chapter.record-form', compact('record', 'project', 'kind'));
    }

    public function edit(Request $request, string $kind, int $id)
    {
        $class = $this->model($kind); $record = $class::findOrFail($id);
        abort_unless($this->editable($record, $request), 403);
        $project = $record->project;
        return view('chapter.record-form', compact('record', 'project', 'kind'));
    }

    public function save(Request $request, string $kind, ?int $id = null)
    {
        $class = $this->model($kind);
        $record = $id ? $class::findOrFail($id) : null;
        if ($record) abort_unless($this->editable($record, $request), 403);
        $request->validate(['project_id' => 'nullable|integer|exists:projects,id']);
        $project = $record ? $record->project : ($request->filled('project_id') ? Project::findOrFail($request->project_id) : null);
        if ($project) abort_unless($project->canManage($request->user()) || $project->canReport($request->user()), 403);
        else abort_unless($kind === 'reports' && Permissions::allows($request->user(), Permissions::FINANCE_MANAGE), 403);
        $labels = $kind === 'letters' ? ChapterForms::LETTER : ($project ? ChapterForms::REPORT : ChapterForms::FINANCIAL_REPORT);
        $rules = ['title' => 'required|string|max:200', 'type' => ['required', Rule::in($kind === 'letters' ? ['JCI LOI', 'External Partner Letter'] : ($project ? ['Progress', 'Completion', 'Liquidation'] : ['Overall Financial']))], 'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png|max:10240'];
        foreach ($labels as $key => $label) $rules['data.'.$key] = 'nullable|string|max:20000';
        if ($kind === 'letters') $rules['data.letter_date'] = 'nullable|date_format:Y-m-d';
        if (!$project) {
            $rules['data.period_start'] = 'required|date_format:Y-m-d';
            $rules['data.period_end'] = 'required|date_format:Y-m-d|after_or_equal:data.period_start';
        }
        $data = $request->validate($rules); unset($data['attachment']);
        $path = $request->hasFile('attachment') ? $request->file('attachment')->store('report-attachments', 'local') : null;
        try {
            $record = DB::transaction(function () use ($record, $class, $project, $request, $data, $path) {
                if ($project) {
                    $project = Project::lockForUpdate()->findOrFail($project->id);
                    abort_unless($project->canManage($request->user()) || $project->canReport($request->user()), 403);
                }
                if ($record) { $record = $class::lockForUpdate()->findOrFail($record->id); abort_unless($this->editable($record, $request), 403); }
                $before = $record?->toArray() ?? [];
                $versions = $record?->versions ?? [];
                if ($record) $versions[] = ['version' => $record->version, 'saved_at' => now()->toIso8601String(), 'title' => $record->title, 'status' => $record->status, 'data' => $record->data];
                $record ??= new $class(['project_id' => $project?->id, 'created_by' => $request->user()->id, 'status' => 'Draft', 'version' => 0]);
                $body = $data['data'] ?? [];
                if ($project) $body['project_snapshot'] = ['reference' => $project->reference, 'title' => $project->title, 'area' => $project->area, 'description' => $project->proposal['description'] ?? '', 'date' => $project->starts_on?->format('Y-m-d'), 'end_date' => $project->ends_on?->format('Y-m-d'), 'venue' => $project->venue, 'allocated' => $project->allocated, 'expenses' => $project->spent, 'remaining' => $project->remaining];
                if ($path) { $body['attachment_path'] = $path; $body['attachment_name'] = $request->file('attachment')->getClientOriginalName(); }
                elseif (isset($record->data['attachment_path'])) { $body['attachment_path'] = $record->data['attachment_path']; $body['attachment_name'] = $record->data['attachment_name']; }
                $record->fill(['title' => $data['title'], 'type' => $data['type'], 'data' => $body, 'versions' => $versions, 'version' => $record->version + 1])->save();
                ChapterService::audit('document_draft_saved', $record, $before);
                return $record;
            });
        } catch (\Throwable $e) { if ($path) Storage::disk('local')->delete($path); throw $e; }
        return redirect()->route('records.show', [$kind, $record->id])->with('success', 'Draft saved with version history.');
    }

    public function show(Request $request, string $kind, int $id)
    {
        $class = $this->model($kind); $record = $class::with(['project', 'author'])->findOrFail($id);
        abort_unless($this->visible($record, $request), 403);
        $editable = $this->editable($record, $request);
        return view('chapter.record', compact('record', 'kind', 'editable'));
    }

    public function transition(Request $request, string $kind, int $id)
    {
        $class = $this->model($kind);
        $data = $request->validate(['action' => ['required', Rule::in(['submit', 'return', 'approve', 'sent', 'archive', 'revise'])], 'comments' => 'nullable|string|max:5000']);
        DB::transaction(function () use ($request, $class, $id, $data, $kind) {
            $initial = $class::findOrFail($id);
            if ($initial->project_id) Project::lockForUpdate()->findOrFail($initial->project_id);
            $record = $class::lockForUpdate()->findOrFail($id);
            abort_if($record->project?->status === 'Archived', 403);
            $before = $record->toArray();
            $manager = $record->project_id ? ($record->project->canManage($request->user()) || $record->project->canReport($request->user())) : Permissions::allows($request->user(), Permissions::FINANCE_MANAGE);
            $action = $data['action'];
            if ($action === 'submit') {
                abort_unless($this->editable($record, $request), 403);
                if ($kind === 'reports' && in_array($record->type, ['Completion', 'Liquidation'])) {
                    abort_unless($record->project?->canReport($request->user()), 403);
                    if ($record->project->tasks()->where('status', '!=', 'Completed')->exists()) throw ValidationException::withMessages(['action' => 'Complete project tasks before submitting reports.']);
                    if ($record->type === 'Liquidation' && $record->project->transactions()->where('status', 'Posted')->where('direction', 'debit')->whereNull('document_path')->exists()) throw ValidationException::withMessages(['action' => 'Every posted expense needs a receipt before liquidation submission.']);
                }
                ChapterService::requireFields($record->data ?? [], $kind === 'letters' ? ChapterForms::LETTER_REQUIRED : ($record->project_id ? ChapterForms::REPORT : ChapterForms::FINANCIAL_REPORT), 'data');
                if (!$record->project_id) {
                    $body = $record->data;
                    $entries = LedgerEntry::where('status', 'Posted')->whereBetween('transaction_date', [$body['period_start'], $body['period_end']])->get();
                    $body['financial_snapshot'] = ['income' => $entries->where('direction', 'credit')->sum('amount'), 'expenses' => $entries->where('direction', 'debit')->sum('amount'), 'transactions' => $entries->map(fn ($e) => $e->only(['reference', 'transaction_date', 'type', 'direction', 'amount', 'description']))->all(), 'captured_at' => now()->toIso8601String()];
                    $record->data = $body;
                } elseif ($kind === 'reports') {
                    $body = $record->data;
                    $project = $record->project;
                    $posted = $project->transactions()->where('status', 'Posted')->get();
                    $debits = $posted->where('direction', 'debit');
                    $body['financial_snapshot'] = [
                        'projected_income' => $project->proposal['projected_income'] ?? null,
                        'projected_expenses' => $project->proposed_budget,
                        'actual_income' => $posted->where('direction', 'credit')->sum('amount'),
                        'actual_expenses' => $debits->sum('amount'),
                        'pending_liquidation' => $debits->where('liquidation_status', '!=', 'Liquidated')->count(),
                        'missing_evidence' => $debits->filter(fn ($entry) => !$entry->document_path)->count(),
                        'captured_at' => now()->toIso8601String(),
                    ];
                    $record->data = $body;
                }
                $record->status = $kind === 'letters' ? 'For Review' : 'Submitted';
                ChapterService::notify(User::where('role', $kind === 'reports' && in_array($record->type, ['Liquidation', 'Overall Financial']) ? 'treasurer' : 'admin')->where('status', 'active')->pluck('id'), $kind === 'letters' ? 'JCI LOI for review' : 'Report submitted', $record->title, route('records.show', [$kind, $id], false));
            } elseif (in_array($action, ['return', 'approve'])) {
                $reviewRole = $kind === 'reports' && in_array($record->type, ['Liquidation', 'Overall Financial']) ? 'treasurer' : 'admin';
                abort_unless($request->user()->role === $reviewRole && in_array($record->status, ['For Review', 'Submitted']), 403);
                abort_if($record->created_by === $request->user()->id || $record->project?->chair_id === $request->user()->id, 403, 'An independent authorized officer must review this submission.');
                if (!trim($data['comments'] ?? '')) throw \Illuminate\Validation\ValidationException::withMessages(['comments' => 'Review comments are required.']);
                $record->status = $action === 'return' ? 'Returned' : ($kind === 'letters' ? 'Approved for Sending' : 'Reviewed');
                $record->review_comments = $data['comments'];
                if ($kind === 'reports') { $record->reviewed_by = $action === 'approve' ? $request->user()->id : null; $record->reviewed_at = $action === 'approve' ? now() : null; }
                ChapterService::notify([$record->created_by, $record->project?->chair_id], 'Document review: '.$record->status, $record->title, route('records.show', [$kind, $id], false));
            } elseif ($action === 'sent') {
                abort_unless($kind === 'letters' && $manager && $record->status === 'Approved for Sending', 403);
                $record->status = 'Sent';
            } elseif ($action === 'revise') {
                abort_unless($manager && in_array($record->status, ['Reviewed', 'Sent', 'Approved for Sending']), 403);
                $versions = $record->versions ?? [];
                $versions[] = ['version' => $record->version, 'saved_at' => now()->toIso8601String(), 'title' => $record->title, 'status' => $record->status, 'data' => $record->data];
                $record->versions = $versions; $record->version++; $record->status = 'Draft';
            } else {
                abort_unless($kind === 'letters' && ($manager || Permissions::allows($request->user(), Permissions::RECORDS_REVIEW)) && $record->status === 'Sent', 403);
                $record->status = 'Archived';
            }
            if ($kind === 'reports' && in_array($action, ['submit', 'revise'])) { $record->reviewed_by = null; $record->reviewed_at = null; }
            $record->save();
            if ($kind === 'reports' && $record->project_id) \App\Services\ReportWorkflowService::archiveIfReady($record->project);
            ChapterService::audit('document_'.$action, $record, $before, $data['comments'] ?? null);
        });
        return back()->with('success', 'Document status updated.');
    }

    public function attachment(Request $request, string $kind, int $id)
    {
        $class = $this->model($kind); $record = $class::findOrFail($id);
        abort_unless($this->visible($record, $request), 403);
        $path = $record->data['attachment_path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->download($path, $record->data['attachment_name'], ['X-Content-Type-Options' => 'nosniff']);
    }

    public function members(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:200', 'role' => ['nullable', Rule::in(['admin', 'bod', 'treasurer', 'member'])]]);
        $members = User::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('member_no', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->orderBy('name')->paginate(30)->withQueryString();
        return view('chapter.members', compact('members'));
    }

    public function memberForm(Request $request, ?User $member = null)
    {
        abort_unless(Permissions::allows($request->user(), Permissions::MEMBERS_MANAGE), 403);
        abort_unless($member || Permissions::allows($request->user(), Permissions::ACCOUNTS_MANAGE), 403);
        return view('chapter.member-form', ['member' => $member ?? new User]);
    }

    public function memberSave(Request $request, ?User $member = null)
    {
        abort_unless(Permissions::allows($request->user(), Permissions::MEMBERS_MANAGE), 403);
        if (!Permissions::allows($request->user(), Permissions::ACCOUNTS_MANAGE)) {
            abort_unless($member, 403);
            // Profile management never grants access to credentials or account activation.
            foreach (['role', 'status', 'username', 'email', 'password', 'password_confirmation', 'concept_reviewer', 'proposal_reviewer'] as $field) {
                abort_if($request->exists($field), 403);
            }
            $profileData = $request->validate([
                'name' => 'required|string|max:200',
                'member_no' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($member->id)],
                'profile.nickname' => 'nullable|string|max:100',
                'profile.phone' => 'nullable|string|max:40',
                'profile.address' => 'nullable|string|max:500',
                'profile.joined_on' => 'nullable|date',
                'profile.emergency_contact' => 'nullable|string|max:500',
            ]);
            DB::transaction(function () use ($member, $profileData) {
                $member = User::lockForUpdate()->findOrFail($member->id);
                $before = $member->toArray();
                $profileData['profile'] = array_merge($member->profile ?? [], $profileData['profile'] ?? []);
                $member->fill($profileData)->save();
                ChapterService::audit('member_profile_saved', $member, $before);
            });
            return redirect()->route('members')->with('success', 'Member details saved.');
        }
        $data = $request->validate(['name' => 'required|string|max:200', 'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->ignore($member?->id)], 'email' => ['required', 'email', 'max:200', Rule::unique('users')->ignore($member?->id)], 'password' => [$member ? 'nullable' : 'required', 'confirmed', Password::min(10)], 'role' => ['required', Rule::in(['admin', 'bod', 'treasurer', 'member'])], 'status' => ['required', Rule::in(['active', 'inactive'])], 'member_no' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($member?->id)], 'concept_reviewer' => 'sometimes|boolean', 'proposal_reviewer' => 'sometimes|boolean', 'profile.nickname' => 'nullable|string|max:100', 'profile.phone' => 'nullable|string|max:40', 'profile.address' => 'nullable|string|max:500', 'profile.joined_on' => 'nullable|date', 'profile.emergency_contact' => 'nullable|string|max:500']);
        $data['username'] = strtolower($data['username']);
        $data['concept_reviewer'] = in_array($data['role'], ['admin', 'bod']) && $request->boolean('concept_reviewer');
        $data['proposal_reviewer'] = in_array($data['role'], ['admin', 'bod']) && $request->boolean('proposal_reviewer');
        if (empty($data['password'])) unset($data['password']);
        DB::transaction(function () use ($member, $data) {
            $admins = User::where('role', 'admin')->where('status', 'active')->lockForUpdate()->get();
            if ($member && $member->role === 'admin' && ($data['role'] !== 'admin' || $data['status'] !== 'active') && $admins->count() <= 1) throw \Illuminate\Validation\ValidationException::withMessages(['role' => 'Keep at least one active administrator.']);
            $before = $member?->toArray() ?? [];
            $member ??= new User;
            if ($member->exists) $data['profile'] = array_merge($member->profile ?? [], $data['profile'] ?? []);
            $member->fill($data)->save();
            ChapterService::audit('member_account_saved', $member, $before);
        });
        return redirect()->route('members')->with('success', 'Member account saved.');
    }

    public function account(Request $request) { return view('chapter.account', ['member' => $request->user()]); }

    public function accountPhoto(Request $request)
    {
        $path = $request->user()->profile['photo_path'] ?? null;
        abort_unless($path && str_starts_with($path, 'profile-photos/'.$request->user()->id.'/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function accountSave(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:200', 'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->ignore($request->user()->id)], 'email' => ['required', 'email', 'max:200', Rule::unique('users')->ignore($request->user()->id)], 'profile.nickname' => 'nullable|string|max:100', 'profile.phone' => 'nullable|string|max:40', 'profile.address' => 'nullable|string|max:500', 'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 'remove_photo' => 'nullable|boolean']);
        $data['username'] = strtolower($data['username']);
        $user = $request->user();
        $oldPhoto = $user->profile['photo_path'] ?? null;
        $newPhoto = $request->hasFile('photo') ? $request->file('photo')->store('profile-photos/'.$user->id, 'local') : null;
        if ($request->hasFile('photo') && !$newPhoto) throw ValidationException::withMessages(['photo' => 'The profile photo could not be saved. Please try again.']);
        unset($data['photo'], $data['remove_photo']);
        try {
            DB::transaction(function () use ($user, $data, $request, $newPhoto) {
                $before = $user->toArray();
                $data['profile'] = array_merge($user->profile ?? [], $data['profile'] ?? []);
                if ($newPhoto) $data['profile']['photo_path'] = $newPhoto;
                elseif ($request->boolean('remove_photo')) unset($data['profile']['photo_path']);
                $user->fill($data)->save();
                ChapterService::audit('account_updated', $user, $before);
            });
        } catch (\Throwable $e) {
            if ($newPhoto) Storage::disk('local')->delete($newPhoto);
            throw $e;
        }
        if (($newPhoto || $request->boolean('remove_photo')) && $oldPhoto && str_starts_with($oldPhoto, 'profile-photos/'.$user->id.'/')) Storage::disk('local')->delete($oldPhoto);
        return back()->with('success', 'Profile updated.');
    }

    public function settings(Request $request)
    {
        return view('chapter.settings', ['member' => $request->user()]);
    }

    public function settingsSave(Request $request)
    {
        if ($request->boolean('reset_preferences')) {
            $user = $request->user();
            DB::transaction(function () use ($user) {
                $before = $user->toArray();
                $profile = $user->profile ?? [];
                $profile['preferences'] = [
                    'start_page' => 'dashboard',
                    'interface_density' => 'comfortable',
                    'navigation_mode' => 'accordion',
                    'reduce_motion' => false,
                    'color_theme' => 'system',
                ];
                $user->profile = $profile;
                $user->save();
                ChapterService::audit('account_settings_reset', $user, $before);
            });
            return back()->with('success', 'Workspace settings restored to default.');
        }
        $data = $request->validate([
            'profile.preferences.start_page' => ['required', Rule::in(['dashboard', 'projects', 'tasks', 'calendar'])],
            'profile.preferences.interface_density' => ['required', Rule::in(['comfortable', 'compact'])],
            'profile.preferences.navigation_mode' => ['required', Rule::in(['accordion', 'expanded'])],
            'profile.preferences.reduce_motion' => 'required|boolean',
            'profile.preferences.color_theme' => ['nullable', Rule::in(['system', 'jci', 'teal', 'navy'])],
        ]);
        $user = $request->user();

        DB::transaction(function () use ($user, $data) {
            $before = $user->toArray();
            $data['profile'] = array_merge($user->profile ?? [], $data['profile'] ?? []);
            $user->fill($data)->save();
            ChapterService::audit('account_settings_updated', $user, $before);
        });

        return back()->with('success', 'Settings saved.');
    }
}

