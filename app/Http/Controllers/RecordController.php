<?php
namespace App\Http\Controllers;

use App\Models\{Project, Loi, ProjectReport, LedgerEntry, User};
use App\Services\ChapterService;
use App\Support\ChapterForms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Storage};
use Illuminate\Validation\{Rule, Rules\Password};

class RecordController extends Controller
{
    private function model(string $kind): string { abort_unless(in_array($kind, ['letters', 'reports']), 404); return $kind === 'letters' ? Loi::class : ProjectReport::class; }

    private function editable($record, Request $request): bool
    {
        return in_array($record->status, ['Draft', 'Returned'], true) && ($record->project_id ? $record->project->canManage($request->user()) : $request->user()->role === 'treasurer');
    }

    private function visible($record, Request $request): bool
    {
        if (!$record->project_id) return $request->user()->role === 'treasurer' || (in_array($request->user()->role, ['admin', 'bod']) && in_array($record->status, ['Submitted', 'Reviewed']));
        return ChapterService::canView($record->project, $request->user()) && ($record->project->chair_id === $request->user()->id || in_array($request->user()->role, ['admin', 'bod']) || in_array($record->status, ['Reviewed', 'Approved for Sending', 'Sent', 'Archived']));
    }

    public function index(Request $request, string $kind)
    {
        $class = $this->model($kind);
        $request->validate(['q' => 'nullable|string|max:200', 'status' => 'nullable|string|max:80', 'type' => ['nullable', Rule::in(['Progress', 'Completion', 'Overall Financial'])]]);
        $records = $class::with(['project', 'author'])->latest()->get()
            ->filter(fn ($r) => $this->visible($r, $request))
            ->when($request->filled('q'), fn ($items) => $items->filter(fn ($r) => str_contains(mb_strtolower($r->title.' '.$r->project?->title), mb_strtolower($request->string('q')))))
            ->when($request->filled('status'), fn ($items) => $items->where('status', $request->status))
            ->when($kind === 'reports' && $request->filled('type'), fn ($items) => $items->where('type', $request->type));
        $projects = Project::where('chair_id', $request->user()->id)->whereIn('status', ['Approved', 'Ongoing'])->orderBy('title')->get();
        return view('chapter.records', compact('records', 'projects', 'kind'));
    }

    public function create(Request $request, string $kind)
    {
        $class = $this->model($kind);
        $request->validate(['project_id' => 'nullable|integer|exists:projects,id']);
        $project = $request->filled('project_id') ? Project::findOrFail($request->project_id) : null;
        if ($project) abort_unless($project->canManage($request->user()), 403);
        else abort_unless($kind === 'reports' && $request->user()->role === 'treasurer', 403);
        $record = new $class(['project_id' => $project?->id, 'type' => $project ? ($kind === 'letters' ? 'JCI LOI' : 'Progress') : 'Overall Financial', 'data' => []]);
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
        if ($project) abort_unless($project->canManage($request->user()), 403);
        else abort_unless($kind === 'reports' && $request->user()->role === 'treasurer', 403);
        $labels = $kind === 'letters' ? ChapterForms::LETTER : ($project ? ChapterForms::REPORT : ChapterForms::FINANCIAL_REPORT);
        $rules = ['title' => 'required|string|max:200', 'type' => ['required', Rule::in($kind === 'letters' ? ['JCI LOI', 'External Partner Letter'] : ($project ? ['Progress', 'Completion'] : ['Overall Financial']))], 'attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png|max:10240'];
        foreach ($labels as $key => $label) $rules['data.'.$key] = 'nullable|string|max:20000';
        if (!$project) {
            $rules['data.period_start'] = 'required|date_format:Y-m-d';
            $rules['data.period_end'] = 'required|date_format:Y-m-d|after_or_equal:data.period_start';
        }
        $data = $request->validate($rules); unset($data['attachment']);
        $path = $request->hasFile('attachment') ? $request->file('attachment')->store('report-attachments', 'local') : null;
        try {
            $record = DB::transaction(function () use ($record, $class, $project, $request, $data, $path) {
                if ($record) { $record = $class::lockForUpdate()->findOrFail($record->id); abort_unless($this->editable($record, $request), 403); }
                $before = $record?->toArray() ?? [];
                $versions = $record?->versions ?? [];
                if ($record) $versions[] = ['version' => $record->version, 'saved_at' => now()->toIso8601String(), 'title' => $record->title, 'status' => $record->status, 'data' => $record->data];
                $record ??= new $class(['project_id' => $project?->id, 'created_by' => $request->user()->id, 'status' => 'Draft', 'version' => 0]);
                $body = $data['data'] ?? [];
                if ($project) $body['project_snapshot'] = ['reference' => $project->reference, 'title' => $project->title, 'description' => $project->proposal['description'] ?? '', 'date' => $project->starts_on?->format('Y-m-d'), 'venue' => $project->venue, 'allocated' => $project->allocated, 'expenses' => $project->spent, 'remaining' => $project->remaining];
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
            $record = $class::lockForUpdate()->findOrFail($id);
            $before = $record->toArray();
            $manager = $record->project_id ? $record->project->canManage($request->user()) : $request->user()->role === 'treasurer';
            $action = $data['action'];
            if ($action === 'submit') {
                abort_unless($this->editable($record, $request), 403);
                ChapterService::requireFields($record->data ?? [], $kind === 'letters' ? ChapterForms::LETTER : ($record->project_id ? ChapterForms::REPORT : ChapterForms::FINANCIAL_REPORT), 'data');
                if (!$record->project_id) {
                    $body = $record->data;
                    $entries = LedgerEntry::where('status', 'Posted')->whereBetween('transaction_date', [$body['period_start'], $body['period_end']])->get();
                    $body['financial_snapshot'] = ['income' => $entries->where('direction', 'credit')->sum('amount'), 'expenses' => $entries->where('direction', 'debit')->sum('amount'), 'transactions' => $entries->map(fn ($e) => $e->only(['reference', 'transaction_date', 'type', 'direction', 'amount', 'description']))->all(), 'captured_at' => now()->toIso8601String()];
                    $record->data = $body;
                }
                $record->status = $kind === 'letters' ? 'For Review' : 'Submitted';
                ChapterService::notify(User::where('role', 'admin')->pluck('id'), $kind === 'letters' ? 'JCI LOI for review' : 'Report submitted', $record->title, route('records.show', [$kind, $id], false));
            } elseif (in_array($action, ['return', 'approve'])) {
                abort_unless($request->user()->role === 'admin' && in_array($record->status, ['For Review', 'Submitted']), 403);
                if (!trim($data['comments'] ?? '')) throw \Illuminate\Validation\ValidationException::withMessages(['comments' => 'Review comments are required.']);
                $record->status = $action === 'return' ? 'Returned' : ($kind === 'letters' ? 'Approved for Sending' : 'Reviewed');
                $record->review_comments = $data['comments'];
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
                abort_unless($kind === 'letters' && ($manager || $request->user()->role === 'admin') && $record->status === 'Sent', 403);
                $record->status = 'Archived';
            }
            $record->save(); ChapterService::audit('document_'.$action, $record, $before, $data['comments'] ?? null);
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
        abort_unless($request->user()->role === 'admin', 403);
        return view('chapter.member-form', ['member' => $member ?? new User]);
    }

    public function memberSave(Request $request, ?User $member = null)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['name' => 'required|string|max:200', 'email' => ['required', 'email', 'max:200', Rule::unique('users')->ignore($member?->id)], 'password' => [$member ? 'nullable' : 'required', 'confirmed', Password::min(10)], 'role' => ['required', Rule::in(['admin', 'bod', 'treasurer', 'member'])], 'status' => ['required', Rule::in(['active', 'inactive'])], 'member_no' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($member?->id)], 'concept_reviewer' => 'sometimes|boolean', 'proposal_reviewer' => 'sometimes|boolean', 'profile.nickname' => 'nullable|string|max:100', 'profile.phone' => 'nullable|string|max:40', 'profile.address' => 'nullable|string|max:500', 'profile.joined_on' => 'nullable|date', 'profile.emergency_contact' => 'nullable|string|max:500']);
        $data['concept_reviewer'] = in_array($data['role'], ['admin', 'bod']) && $request->boolean('concept_reviewer');
        $data['proposal_reviewer'] = in_array($data['role'], ['admin', 'bod']) && $request->boolean('proposal_reviewer');
        if (empty($data['password'])) unset($data['password']);
        DB::transaction(function () use ($member, $data) {
            $admins = User::where('role', 'admin')->where('status', 'active')->lockForUpdate()->get();
            if ($member && $member->role === 'admin' && ($data['role'] !== 'admin' || $data['status'] !== 'active') && $admins->count() <= 1) throw \Illuminate\Validation\ValidationException::withMessages(['role' => 'Keep at least one active administrator.']);
            $before = $member?->toArray() ?? [];
            $member ??= new User;
            $member->fill($data)->save();
            ChapterService::audit('member_account_saved', $member, $before);
        });
        return redirect()->route('members')->with('success', 'Member account saved.');
    }

    public function account(Request $request) { return view('chapter.account', ['member' => $request->user()]); }

    public function accountSave(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:200', 'email' => ['required', 'email', 'max:200', Rule::unique('users')->ignore($request->user()->id)], 'profile.nickname' => 'nullable|string|max:100', 'profile.phone' => 'nullable|string|max:40', 'profile.address' => 'nullable|string|max:500', 'current_password' => 'nullable|required_with:password|current_password', 'password' => ['nullable', 'confirmed', Password::min(10)]]);
        unset($data['current_password']);
        if (empty($data['password'])) unset($data['password']);
        DB::transaction(function () use ($request, $data) {
            $before = $request->user()->toArray();
            $data['profile'] = array_merge($request->user()->profile ?? [], $data['profile'] ?? []);
            $request->user()->fill($data)->save();
            ChapterService::audit('account_updated', $request->user(), $before);
        });
        if (isset($data['password'])) $request->session()->regenerate();
        return back()->with('success', 'Account updated.');
    }
}

