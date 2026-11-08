<?php
namespace App\Http\Controllers;

use App\Models\{Project, Loi, ProjectReport, LedgerEntry, Partner, User, ProjectDocument};
use App\Services\ChapterService;
use App\Services\OfficePreviewService;
use App\Support\ChapterForms;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Storage};
use Illuminate\Validation\{Rule, Rules\Password, ValidationException};

class RecordController extends Controller
{
    private function projectSnapshot(Project $project): array
    {
        return [
            'reference' => $project->reference, 'title' => $project->title, 'area' => $project->area,
            'description' => $project->proposal['description'] ?? '',
            'date' => $project->starts_on?->format('Y-m-d'), 'end_date' => $project->ends_on?->format('Y-m-d'),
            'venue' => $project->venue, 'chair_name' => $project->chair?->name,
            'allocated' => $project->allocated, 'expenses' => $project->spent, 'remaining' => $project->remaining,
        ];
    }
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
        $request->validate(['q' => 'nullable|string|max:200', 'status' => 'nullable|string|max:80', 'type' => ['nullable', Rule::in(['Completion', 'Liquidation', 'Overall Financial'])], 'from' => 'nullable|date', 'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : '')]);
        $user = $request->user();
        $query = $class::with(['project', 'author'])->where(function ($q) use ($user, $kind) {
            if ($user->role === 'treasurer') $q->whereNull('project_id')->orWhereNotNull('project_id');
            else $q->whereNotNull('project_id');
        })->where(function ($q) use ($user) {
            $q->whereNull('project_id')->orWhereHas('project', function ($p) use ($user) {
                if ($user->role === 'member') $p->where(fn ($v) => $v->whereIn('status', Project::APPROVED)->orWhere('created_by', $user->id)->orWhere('chair_id', $user->id));
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
            ->when($kind === 'reports', fn ($q) => $q->where('type', '!=', 'Progress'))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%')->orWhereHas('project', fn ($p) => $p->where('title', 'like', '%'.$request->string('q').'%'))->orWhereHas('author', fn ($author) => $author->where('name', 'like', '%'.$request->string('q').'%'))))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('to')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($kind === 'reports' && $request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()->paginate(25)->withQueryString();
        $projects = Project::where('chair_id', $request->user()->id)->whereIn('status', ['Plan Locked', 'Ongoing', 'Completion Review'])->orderBy('title')->get();
        return view('chapter.records', compact('records', 'projects', 'kind'));
    }

    public function create(Request $request, string $kind)
    {
        $class = $this->model($kind);
        $request->validate(['project_id' => 'nullable|integer|exists:projects,id', 'type' => ['nullable', Rule::in(['Completion', 'Liquidation'])]]);
        $project = $request->filled('project_id') ? Project::findOrFail($request->project_id) : null;
        if ($project) abort_unless($project->canManage($request->user()) || $project->canReport($request->user()), 403);
        else abort_unless($kind === 'reports' && Permissions::allows($request->user(), Permissions::FINANCE_MANAGE), 403);
        $reportType = $kind === 'reports' && $project ? ($request->query('type') ?: 'Completion') : null;
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
        $partners = $kind === 'letters' ? Partner::orderBy('name')->get(['id', 'name']) : collect();
        return view('chapter.record-form', compact('record', 'project', 'kind', 'partners'));
    }

    public function edit(Request $request, string $kind, int $id)
    {
        $class = $this->model($kind); $record = $class::findOrFail($id);
        abort_unless($this->editable($record, $request), 403);
        $project = $record->project;
        $partners = $kind === 'letters' ? Partner::orderBy('name')->get(['id', 'name']) : collect();
        return view('chapter.record-form', compact('record', 'project', 'kind', 'partners'));
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
        $reportType = $record?->type ?? $request->input('type');
        $labels = $kind === 'letters' ? ChapterForms::LETTER : ($project ? ($reportType === 'Liquidation' ? ChapterForms::LIQUIDATION : ChapterForms::REPORT) : ChapterForms::FINANCIAL_REPORT);
        $rules = ['title' => 'required|string|max:200', 'type' => ['required', Rule::in($kind === 'letters' ? ['JCI LOI', 'External Partner Letter'] : ($project ? ['Completion', 'Liquidation'] : ['Overall Financial']))], 'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png|max:10240'];
        if ($record) $rules['type'][] = Rule::in([$record->type]);
        foreach ($labels as $key => $label) $rules['data.'.$key] = 'nullable|string|max:20000';
        if ($kind === 'letters') $rules['data.letter_date'] = 'nullable|date_format:Y-m-d';
        if ($kind === 'letters') $rules['partner_id'] = 'nullable|integer|exists:partners,id';
        if ($kind === 'reports' && $reportType === 'Liquidation') {
            $rules['data.report_date'] = 'nullable|date_format:Y-m-d';
            $rules['data.cash_received'] = 'nullable|numeric|min:0|max:999999999999.99';
        }
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
                if ($record instanceof Loi && !empty($data['partner_id'])) {
                    $partner = Partner::findOrFail($data['partner_id']);
                    $record->partner_id = $partner->id;
                    $body['organization'] = $partner->name;
                }
                if ($project) $body['project_snapshot'] = $this->projectSnapshot($project);
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
            abort_if($record->project?->trashed() || in_array($record->project?->status, ['Archived', 'Completed', 'Cancelled'], true), 403);
            $before = $record->toArray();
            $manager = $record->project_id ? ($record->project->canManage($request->user()) || $record->project->canReport($request->user())) : Permissions::allows($request->user(), Permissions::FINANCE_MANAGE);
            $action = $data['action'];
            if ($action === 'submit') {
                abort_unless($this->editable($record, $request), 403);
                if ($kind === 'letters' && !$record->partner_id) {
                    $organization = trim($record->data['organization'] ?? '');
                    if ($organization !== '') $record->partner_id = Partner::firstOrCreate(['name' => $organization], ['address' => $record->data['address'] ?? null])->id;
                }
                abort_if($kind === 'reports' && $record->type === 'Progress', 403);
                if ($kind === 'reports' && in_array($record->type, ['Completion', 'Liquidation'])) {
                    abort_unless($record->project?->canReport($request->user()), 403);
                    if ($record->project->tasks()->where('status', '!=', 'Completed')->exists()) throw ValidationException::withMessages(['action' => 'Complete project tasks before submitting reports.']);
                    if ($record->type === 'Liquidation' && $record->project->transactions()->where('status', 'Posted')->where('direction', 'debit')->whereNull('document_path')->exists()) throw ValidationException::withMessages(['action' => 'Every posted expense needs a receipt before liquidation submission.']);
                }
                ChapterService::requireFields($record->data ?? [], $kind === 'letters' ? ChapterForms::LETTER_REQUIRED : ($record->project_id ? ($record->type === 'Liquidation' ? ['report_date' => 'Report date', 'voucher_reference' => 'Voucher reference', 'cash_received' => 'Cash advanced or received'] : ChapterForms::REPORT) : ChapterForms::FINANCIAL_REPORT), 'data');
                if (!$record->project_id) {
                    $body = $record->data;
                    $entries = LedgerEntry::where('status', 'Posted')->whereBetween('transaction_date', [$body['period_start'], $body['period_end']])->get();
                    $body['financial_snapshot'] = ['income' => $entries->where('direction', 'credit')->sum('amount'), 'expenses' => $entries->where('direction', 'debit')->sum('amount'), 'transactions' => $entries->map(fn ($e) => $e->only(['reference', 'transaction_date', 'type', 'direction', 'amount', 'description']))->all(), 'captured_at' => now()->toIso8601String()];
                    $record->data = $body;
                } elseif ($kind === 'reports') {
                    $body = $record->data;
                    $project = $record->project;
                    $body['project_snapshot'] = $this->projectSnapshot($project);
                    $posted = $project->transactions()->where('status', 'Posted')->get();
                    $debits = $posted->where('direction', 'debit');
                    $body['financial_snapshot'] = [
                        'projected_income' => $project->proposal['projected_income'] ?? null,
                        'projected_expenses' => $project->proposed_budget,
                        'actual_income' => $posted->where('direction', 'credit')->sum('amount'),
                        'actual_expenses' => $debits->sum('amount'),
                        'pending_liquidation' => $debits->where('liquidation_status', '!=', 'Liquidated')->count(),
                        'missing_evidence' => $debits->filter(fn ($entry) => !$entry->document_path)->count(),
                        'expense_lines' => $debits->map(fn ($entry) => ['date' => $entry->transaction_date?->format('Y-m-d'), 'reference' => $entry->reference, 'receipt' => $entry->document_name, 'description' => $entry->description, 'quantity' => 1, 'amount' => (float) $entry->amount])->values()->all(),
                        'captured_at' => now()->toIso8601String(),
                    ];
                    $record->data = $body;
                }
                $record->status = $kind === 'letters' ? 'For Review' : 'Submitted';
                ChapterService::notify(User::where('role', $kind === 'reports' && in_array($record->type, ['Liquidation', 'Overall Financial']) ? 'treasurer' : 'admin')->where('status', 'active')->pluck('id'), $kind === 'letters' ? 'JCI LOI for review' : 'Report submitted', $record->title, route('records.show', [$kind, $id], false), true);
            } elseif (in_array($action, ['return', 'approve'])) {
                $reviewRole = $kind === 'reports' && in_array($record->type, ['Liquidation', 'Overall Financial']) ? 'treasurer' : 'admin';
                abort_unless($request->user()->role === $reviewRole && in_array($record->status, ['For Review', 'Submitted']), 403);
                abort_if($record->created_by === $request->user()->id || $record->project?->chair_id === $request->user()->id, 403, 'An independent authorized officer must review this submission.');
                if (!trim($data['comments'] ?? '')) throw \Illuminate\Validation\ValidationException::withMessages(['comments' => 'Review comments are required.']);
                $record->status = $action === 'return' ? 'Returned' : ($kind === 'letters' ? 'Approved for Sending' : 'Reviewed');
                $record->review_comments = $data['comments'];
                if ($kind === 'reports') { $record->reviewed_by = $action === 'approve' ? $request->user()->id : null; $record->reviewed_at = $action === 'approve' ? now() : null; }
                ChapterService::notify([$record->created_by, $record->project?->chair_id], 'Document review: '.$record->status, $record->title, route('records.show', [$kind, $id], false), true);
            } elseif ($action === 'sent') {
                abort_unless($kind === 'letters' && $manager && $record->status === 'Approved for Sending', 403);
                $delivery = $request->validate(['delivered_on' => 'required|date', 'delivery_notes' => 'nullable|string|max:2000']);
                $body = $record->data ?? [];
                $body['delivered_on'] = $delivery['delivered_on'];
                $body['delivery_notes'] = $delivery['delivery_notes'] ?? null;
                $record->data = $body;
                $record->status = 'Sent';
            } elseif ($action === 'revise') {
                abort_unless($manager && in_array($record->status, ['Reviewed', 'Sent', 'Approved for Sending']), 403);
                $versions = $record->versions ?? [];
                $versions[] = ['version' => $record->version, 'saved_at' => now()->toIso8601String(), 'title' => $record->title, 'status' => $record->status, 'data' => $record->data];
                $record->versions = $versions; $record->version++; $record->status = 'Draft';
                if ($kind === 'reports') {
                    $body = $record->data ?? [];
                    unset($body['financial_snapshot']);
                    $record->data = $body;
                }
            } else {
                abort_unless($kind === 'letters' && ($manager || Permissions::allows($request->user(), Permissions::RECORDS_REVIEW)) && $record->status === 'Sent', 403);
                $record->status = 'Archived';
            }
            if ($kind === 'reports' && in_array($action, ['submit', 'revise'])) { $record->reviewed_by = null; $record->reviewed_at = null; }
            $record->save();
            if ($kind === 'reports' && $record->project_id) \App\Services\ReportWorkflowService::completeIfReady($record->project);
            ChapterService::audit('document_'.$action, $record, $before, $data['comments'] ?? null);
        });
        return back()->with('success', 'Document status updated.');
    }

    public function attachment(Request $request, string $kind, int $id, OfficePreviewService $previews)
    {
        $class = $this->model($kind); $record = $class::findOrFail($id);
        abort_unless($this->visible($record, $request), 403);
        $path = $record->data['attachment_path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        $name = $record->data['attachment_name'] ?? basename($path);
        if ($request->boolean('preview')) {
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'pdf' => 'application/pdf', 'jpg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                default => null,
            };
            if ($mime) return response()->file(Storage::disk('local')->path($path), ['Content-Type' => $mime, 'Content-Disposition' => 'inline', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
            if ($extension === 'docx') {
                try {
                    $preview = $previews->render(new ProjectDocument(['path' => $path, 'original_name' => $name]));
                    return response()->file($preview, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
                } catch (\Throwable $e) {
                    report($e);
                    abort(422, 'This Word file could not be previewed. Download the original file instead.');
                }
            }
            abort(422, 'This file type cannot be previewed in the browser. Download the original file instead.');
        }
        return Storage::disk('local')->download($path, $name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function members(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:200', 'role' => ['nullable', Rule::in(['admin', 'bod', 'treasurer', 'member'])], 'state' => 'nullable|in:active,inactive,archived', 'from' => 'nullable|date', 'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : '')]);
        $admin = Permissions::allows($request->user(), Permissions::ACCOUNTS_MANAGE);
        abort_if($request->query('state') === 'archived' && !$admin, 403);
        $members = User::query()->when($request->query('state') === 'archived', fn ($q) => $q->onlyTrashed())
            ->when(!Permissions::allows($request->user(), Permissions::MEMBERS_MANAGE), fn ($q) => $q->where('status', 'active'))
            ->when(in_array($request->query('state'), ['active', 'inactive'], true), fn ($q) => $q->where('status', $request->query('state')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('member_no', 'like', '%'.$request->string('q').'%')->when($admin, fn ($search) => $search->orWhere('email', 'like', '%'.$request->string('q').'%'))))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('to')))
            ->orderBy('name')->paginate(30)->withQueryString();
        return view('chapter.members', compact('members'));
    }

    public function memberDelete(Request $request, User $member)
    {
        abort_unless(Permissions::allows($request->user(), Permissions::ACCOUNTS_MANAGE), 403);
        $data = $request->validate(['reason' => 'required|string|max:2000']);
        abort_if($member->id === $request->user()->id, 403, 'You cannot remove your own account.');
        DB::transaction(function () use ($request, $member, $data) {
            $member = User::lockForUpdate()->findOrFail($member->id);
            if ($member->role === 'admin' && $member->status === 'active'
                && User::where('role', 'admin')->where('status', 'active')->count() <= 1) {
                throw ValidationException::withMessages(['reason' => 'Keep at least one active administrator.']);
            }
            if (Project::where('chair_id', $member->id)->whereIn('status', ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review'])->exists()) {
                throw ValidationException::withMessages(['reason' => 'This person chairs an active project. Reassign or close that project before removing the account.']);
            }
            $before = $member->toArray();
            $member->status = 'inactive';
            $member->active_login_token = null;
            $member->active_login_seen_at = null;
            $member->save();
            $member->delete();
            ChapterService::audit('member_account_deleted', $member, $before, $data['reason']);
        });
        return redirect()->route('accounts')->with('success', 'Account removed from active use. Its chapter history is preserved.');
    }

    public function memberRestore(Request $request, int $memberId)
    {
        abort_unless(Permissions::allows($request->user(), Permissions::ACCOUNTS_MANAGE), 403);
        DB::transaction(function () use ($memberId) {
            $member = User::onlyTrashed()->lockForUpdate()->findOrFail($memberId);
            $before = $member->toArray();
            $member->restore();
            $member->status = 'inactive';
            $member->inactive_since = now();
            $member->save();
            ChapterService::audit('member_account_restored', $member, $before);
        });
        return redirect()->route('accounts', ['state' => 'inactive'])->with('success', 'Account restored as inactive. Review access before activating it.');
    }

    public function memberPhoto(Request $request, User $member)
    {
        abort_unless($member->status === 'active' || Permissions::allows($request->user(), Permissions::MEMBERS_MANAGE), 403);
        $path = $member->profile['photo_path'] ?? null;
        abort_unless($path && str_starts_with($path, 'profile-photos/'.$member->id.'/') && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
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
        if ($request->filled('profile.given_name') || $request->filled('profile.family_name')) {
            $request->merge(['name' => trim(($request->input('profile.given_name') ?? '').' '.($request->input('profile.family_name') ?? ''))]);
        }
        $membershipRules = [
            'profile.title' => 'nullable|string|max:30',
            'profile.given_name' => 'nullable|required_with:profile.family_name|string|max:100',
            'profile.family_name' => 'nullable|required_with:profile.given_name|string|max:100',
            'profile.date_of_birth' => 'nullable|date|before:today',
            'profile.city' => 'nullable|string|max:100',
            'profile.home_phone' => 'nullable|string|max:40',
            'profile.occupation' => ['nullable', Rule::in(['Student', 'Employed', 'Self-employed', 'Other'])],
            'profile.organization' => 'nullable|string|max:200',
            'profile.position' => 'nullable|string|max:160',
            'profile.business_sector' => 'nullable|string|max:160',
            'profile.university_major' => 'nullable|string|max:160',
            'profile.interests' => 'nullable|array|max:4',
            'profile.interests.*' => [Rule::in(ChapterForms::AREAS)],
            'profile.interest_topics' => 'nullable|array|max:10',
            'profile.interest_topics.*' => [Rule::in(ChapterForms::MEMBERSHIP_INTERESTS)],
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
        if (!Permissions::allows($request->user(), Permissions::ACCOUNTS_MANAGE)) {
            abort_unless($member, 403);
            // Profile management never grants access to credentials or account activation.
            foreach (['role', 'status', 'username', 'email', 'password', 'password_confirmation', 'concept_reviewer', 'proposal_reviewer', 'member_no'] as $field) {
                abort_if($request->exists($field), 403);
            }
            $profileData = $request->validate(array_merge([
                'name' => 'required|string|max:200',
                'profile.nickname' => 'nullable|string|max:100',
                'profile.phone' => 'nullable|string|max:40',
                'profile.address' => 'nullable|string|max:500',
                'profile.joined_on' => 'nullable|date',
                'profile.emergency_contact' => 'nullable|string|max:500',
            ], $membershipRules));
            $photoFile = $profileData['photo'] ?? null;
            unset($profileData['photo']);
            if ($request->boolean('membership_interests_present')) {
                $profileData['profile']['interests'] ??= [];
                $profileData['profile']['interest_topics'] ??= [];
            }
            $newPhoto = null;
            $oldPhoto = $member->profile['photo_path'] ?? null;
            try {
            DB::transaction(function () use ($member, $profileData, $photoFile, &$newPhoto) {
                $member = User::lockForUpdate()->findOrFail($member->id);
                $before = $member->toArray();
                $profileData['profile'] = array_merge($member->profile ?? [], $profileData['profile'] ?? []);
                if ($photoFile) {
                    $newPhoto = $photoFile->store('profile-photos/'.$member->id, 'local');
                    if (!$newPhoto) throw ValidationException::withMessages(['photo' => 'The photo could not be saved.']);
                    $profileData['profile']['photo_path'] = $newPhoto;
                }
                $member->fill($profileData)->save();
                \App\Support\MemberId::assign($member);
                ChapterService::audit('member_profile_saved', $member, $before);
            });
            } catch (\Throwable $exception) {
                if ($newPhoto) Storage::disk('local')->delete($newPhoto);
                throw $exception;
            }
            if ($newPhoto && $oldPhoto && str_starts_with($oldPhoto, 'profile-photos/'.$member->id.'/')) Storage::disk('local')->delete($oldPhoto);
            return redirect()->route('members')->with('success', 'Member details saved.');
        }
        $data = $request->validate(array_merge(['name' => 'required|string|max:200', 'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->ignore($member?->id)], 'email' => ['required', 'email', 'max:200', Rule::unique('users')->ignore($member?->id)], 'password' => [$member ? 'nullable' : 'required', 'confirmed', Password::min(10)], 'role' => ['required', Rule::in(['admin', 'bod', 'treasurer', 'member'])], 'status' => ['required', Rule::in(['active', 'inactive'])], 'concept_reviewer' => 'sometimes|boolean', 'proposal_reviewer' => 'sometimes|boolean', 'profile.nickname' => 'nullable|string|max:100', 'profile.phone' => 'nullable|string|max:40', 'profile.address' => 'nullable|string|max:500', 'profile.joined_on' => 'nullable|date', 'profile.emergency_contact' => 'nullable|string|max:500'], $membershipRules));
        abort_if($request->exists('member_no'), 403, 'Member IDs are assigned by the chapter system.');
        $photoFile = $data['photo'] ?? null;
        unset($data['photo']);
        if ($request->boolean('membership_interests_present')) {
            $data['profile']['interests'] ??= [];
            $data['profile']['interest_topics'] ??= [];
        }
        $newPhoto = null;
        $oldPhoto = $member?->profile['photo_path'] ?? null;
        $data['username'] = strtolower($data['username']);
        $data['concept_reviewer'] = in_array($data['role'], ['admin', 'bod']) && $request->boolean('concept_reviewer');
        $data['proposal_reviewer'] = in_array($data['role'], ['admin', 'bod']) && $request->boolean('proposal_reviewer');
        if (empty($data['password'])) unset($data['password']);
        try {
        DB::transaction(function () use ($member, $data, $photoFile, &$newPhoto) {
            $admins = User::where('role', 'admin')->where('status', 'active')->lockForUpdate()->get();
            if ($member && $member->role === 'admin' && ($data['role'] !== 'admin' || $data['status'] !== 'active') && $admins->count() <= 1) throw \Illuminate\Validation\ValidationException::withMessages(['role' => 'Keep at least one active administrator.']);
            $before = $member?->toArray() ?? [];
            $member ??= new User;
            if ($member->exists) $data['profile'] = array_merge($member->profile ?? [], $data['profile'] ?? []);
            $member->fill($data)->save();
            if ($photoFile) {
                $newPhoto = $photoFile->store('profile-photos/'.$member->id, 'local');
                if (!$newPhoto) throw ValidationException::withMessages(['photo' => 'The photo could not be saved.']);
                $profile = $member->profile ?? [];
                $profile['photo_path'] = $newPhoto;
                $member->profile = $profile;
                $member->save();
            }
            \App\Support\MemberId::assign($member);
            ChapterService::audit('member_account_saved', $member, $before);
        });
        } catch (\Throwable $exception) {
            if ($newPhoto) Storage::disk('local')->delete($newPhoto);
            throw $exception;
        }
        if ($newPhoto && $oldPhoto && $member && str_starts_with($oldPhoto, 'profile-photos/'.$member->id.'/')) Storage::disk('local')->delete($oldPhoto);
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
        $data = $request->validate(['name' => 'required|string|max:200', 'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->ignore($request->user()->id)], 'email' => ['required', 'email', 'max:200', Rule::unique('users')->ignore($request->user()->id)], 'profile.title' => 'nullable|string|max:30', 'profile.nickname' => 'nullable|string|max:100', 'profile.phone' => 'nullable|string|max:40', 'profile.address' => 'nullable|string|max:500', 'profile.date_of_birth' => 'nullable|date|before:today', 'profile.occupation' => ['nullable', Rule::in(['Student', 'Employed', 'Self-employed', 'Other'])], 'profile.organization' => 'nullable|string|max:200', 'profile.position' => 'nullable|string|max:160', 'profile.business_sector' => 'nullable|string|max:160', 'profile.university_major' => 'nullable|string|max:160', 'profile.emergency_contact' => 'nullable|string|max:500', 'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 'remove_photo' => 'nullable|boolean']);
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

