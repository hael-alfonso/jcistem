<?php
namespace App\Http\Controllers;

use App\Models\{Project, Task, User, CalendarEvent, ProjectDocument, Notification, AuditLog, MemberDue, ProjectReport};
use Carbon\CarbonImmutable;
use App\Services\{ChapterService, OfficePreviewService, ProjectWordDocumentService};
use App\Support\{ChapterCharts, ChapterForms, Permissions, ProjectBudget};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\{Rule, ValidationException};

class ChapterController extends Controller
{
    public function dashboard(Request $request)
    {
        $projects = ChapterService::visibleProjects($request->user())->with('chair')->latest()->get();
        $assignedTasks = Task::whereIn('project_id', $projects->pluck('id'))
            ->whereJsonContains('assignees', $request->user()->id)
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')->orderBy('deadline')->get();
        $tasks = $assignedTasks->where('status', '!=', 'Completed');
        $personalProjects = $projects->filter(fn ($project) => $project->created_by === $request->user()->id
            || $project->chair_id === $request->user()->id || $assignedTasks->contains('project_id', $project->id));
        $dashboardProjects = $request->user()->role === 'member' ? $personalProjects : $projects;
        $reviewStatuses = match ($request->user()->role) {
            'admin' => ['Admin Review', 'Completion Review'],
            'treasurer' => ['Treasurer Review'],
            'bod' => ['BOD Agenda'],
            default => [],
        };
        $pending = $projects->whereIn('status', $reviewStatuses)->filter(fn ($project) => in_array($request->user()->role, ['admin', 'bod'], true) || $project->created_by !== $request->user()->id);
        $myDuesBalance = $request->user()->role === 'member'
            ? MemberDue::where('member_id', $request->user()->id)->with('payments')->get()->sum('balance') : 0;
        $charts = ChapterCharts::projectMix($dashboardProjects);
        $taskStatuses = $assignedTasks->countBy('status');
        $financeCharts = ChapterCharts::finances($projects->whereIn('status', Project::APPROVED));
        return view('chapter.dashboard', compact('projects', 'dashboardProjects', 'pending', 'reviewStatuses', 'myDuesBalance', 'tasks', 'charts', 'taskStatuses', 'financeCharts'));
    }

    public function projects(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:200', 'filter' => 'nullable|string|max:80']);
        $query = ChapterService::visibleProjects($request->user())
            ->with(['chair', 'owner'])
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'Completed')])
            ->withSum('allocations as allocated_amount', 'amount');
        if ($request->filled('q')) $query->where('title', 'like', '%'.$request->string('q').'%');
        if ($request->filter === 'mine') $query->where(fn ($q) => $q->where('created_by', $request->user()->id)->orWhere('chair_id', $request->user()->id)->orWhereHas('tasks', fn ($q) => $q->whereJsonContains('assignees', $request->user()->id)));
        elseif ($request->filter === 'proposals') $query->where('created_by', $request->user()->id);
        elseif ($request->filter === 'chairing') $query->where('chair_id', $request->user()->id)->whereIn('status', Project::APPROVED);
        elseif ($request->filter === 'board') {
            abort_unless(in_array($request->user()->role, ['admin', 'bod', 'treasurer'], true), 403);
            $query->where(fn ($q) => $q->where('status', 'BOD Agenda')->orWhereHas('decisions'));
        }
        elseif ($request->filter === 'concepts') $query->whereIn('status', ['Draft Concept', 'Submitted for President Review', 'Needs Revision', 'Endorsed for Development', 'Declined']);
        elseif ($request->filter === 'active') $query->whereIn('status', ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review']);
        elseif ($request->filter === 'closed') $query->whereIn('status', ['Completed', 'Archived', 'Declined', 'Not Approved']);
        elseif ($request->filter === 'approved') $query->whereIn('status', ['Approved', 'Plan Locked']);
        elseif ($request->filter === 'ongoing') $query->whereIn('status', ['Ongoing', 'Completion Review']);
        elseif ($request->filter === 'completed') $query->whereIn('status', ['Completed', 'Archived']);
        elseif ($request->filter === 'cancelled') $query->whereIn('status', ['Declined', 'Not Approved']);
        elseif ($request->filter === 'review') {
            $statuses = match ($request->user()->role) {
                'admin' => ['Admin Review', 'BOD Agenda'],
                'treasurer' => ['Treasurer Review'],
                'bod' => ['BOD Agenda'],
                default => null,
            };
            abort_unless($statuses, 403);
            $query->whereIn('status', $statuses);
        }
        elseif ($request->filled('filter')) $query->where('status', $request->filter);
        $projects = $query->latest()->paginate(12)->withQueryString();
        return view('chapter.projects', compact('projects'));
    }

    public function create()
    {
        return view('chapter.project-form', ['project' => new Project, 'proposal' => false]);
    }

    public function edit(Request $request, Project $project)
    {
        abort_unless($project->canEdit($request->user()) && $project->status !== 'Endorsed for Development', 403);
        return view('chapter.project-form', ['project' => $project, 'proposal' => $project->status === 'Approved']);
    }

    public function save(Request $request, ?Project $project = null)
    {
        if ($project) abort_unless($project->canEdit($request->user()) && $project->status !== 'Endorsed for Development', 403);
        $proposal = $project && $project->status === 'Approved';
        $section = $proposal ? 'proposal' : 'concept';
        $methodField = $section.'_submission_method';
        $request->merge([$methodField => $request->input($methodField, 'online')]);
        $rules = ['title' => 'required|string|max:200', 'area' => ['required', Rule::in(ChapterForms::AREAS)], 'starts_on' => 'nullable|date', 'ends_on' => 'nullable|date|after_or_equal:starts_on', 'venue' => 'nullable|string|max:255', 'proposed_budget' => 'required|numeric|decimal:0,2|min:0|max:999999999.99', 'budget_items' => 'nullable|array|max:50', 'budget_items.*.category' => ['required', Rule::in(array_keys(ProjectBudget::CATEGORIES))], 'budget_items.*.description' => 'required|string|max:255', 'budget_items.*.quantity' => 'required|numeric|decimal:0,2|min:0.01|max:999999.99', 'budget_items.*.unit_cost' => 'required|numeric|decimal:0,2|min:0|max:999999999.99', $methodField => ['required', Rule::in(['online', 'upload'])], 'submission_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240', 'revision_note' => 'nullable|string|max:2000'];
        foreach ($proposal ? ChapterForms::PROPOSAL : ChapterForms::CONCEPT as $key => $label) $rules[$section.'.'.$key] = 'nullable|string|max:15000';
        if ($proposal) {
            $rules['proposal.committee_director'] = 'nullable|string|max:200';
            $rules['proposal.president_name'] = 'nullable|string|max:200';
            $rules['proposal.board_meeting_date'] = 'nullable|date_format:Y-m-d';
            $rules['proposal.projected_income'] = 'nullable|numeric|min:0|max:999999999.99';
            $rules['proposal.income_lines'] = 'nullable|array';
            $rules['proposal.expense_lines'] = 'nullable|array';
            foreach (array_keys(ChapterForms::PROPOSAL_INCOME_LINES) as $key) $rules['proposal.income_lines.'.$key] = 'nullable|numeric|decimal:0,2|min:0|max:999999999.99';
            foreach (array_keys(ChapterForms::PROPOSAL_EXPENSE_LINES) as $key) $rules['proposal.expense_lines.'.$key] = 'nullable|numeric|decimal:0,2|min:0|max:999999999.99';
        }
        $data = $request->validate($rules);
        $budgetItems = ProjectBudget::normalize($data['budget_items'] ?? []);
        if ($budgetItems) {
            $data['budget_items'] = $budgetItems;
            $data['proposed_budget'] = ProjectBudget::total($budgetItems);
        } elseif ($project?->budget_items) {
            $data['budget_items'] = $project->budget_items;
        } else {
            unset($data['budget_items']);
        }
        if ($proposal && $data[$methodField] === 'online') {
            $incomeLines = array_filter($data['proposal']['income_lines'] ?? [], fn ($value) => $value !== null && $value !== '');
            if ($incomeLines) {
                $data['proposal']['projected_income'] = \App\Support\Money::decimal(array_sum(array_map(\App\Support\Money::cents(...), $incomeLines)));
            } elseif (array_key_exists('income_lines', $data['proposal'] ?? [])) {
                $data['proposal']['projected_income'] = null;
            } elseif (isset($project->proposal['projected_income'])) {
                $data['proposal']['projected_income'] = $project->proposal['projected_income'];
            }
            if ($budgetItems) $data['proposal']['expense_lines'] = ProjectBudget::officialExpenseLines($budgetItems);
        }
        $method = $data[$methodField];
        $hasPriorUpload = $project?->documents()->where('submission_stage', $section)->exists() ?? false;
        if ($method === 'upload' && !$request->hasFile('submission_file') && !$hasPriorUpload) {
            throw ValidationException::withMessages(['submission_file' => 'Upload the completed DOCX or PDF template before saving this submission method.']);
        }
        if ($proposal && \App\Support\Money::cents($data['proposed_budget']) !== \App\Support\Money::cents($project->proposed_budget)) {
            throw ValidationException::withMessages(['proposed_budget' => 'The BOD-approved proposed budget is locked. An authorized amendment is required to change it.']);
        }
        $path = $request->hasFile('submission_file') ? $request->file('submission_file')->store('project-submissions', 'local') : null;
        $originalName = $request->file('submission_file')?->getClientOriginalName();
        $revisionNote = $data['revision_note'] ?? null;
        unset($data['submission_file'], $data['revision_note']);
        try {
        $project = DB::transaction(function () use ($project, $request, $data, $path, $originalName, $section, $revisionNote) {
            if ($project) {
                $project = Project::lockForUpdate()->findOrFail($project->id);
                abort_unless($project->canEdit($request->user()) && $project->status !== 'Endorsed for Development', 403);
            }
            $before = $project?->toArray() ?? [];
            $project ??= new Project(['created_by' => $request->user()->id, 'status' => 'Draft Concept']);
            $project->fill($data);
            $project->revision = ($project->revision ?? 0) + 1;
            $project->save();
            if (!$project->reference) $project->update(['reference' => 'JCI-'.now()->year.'-'.str_pad((string) $project->id, 5, '0', STR_PAD_LEFT)]);
            if ($path) {
                $previous = $project->documents()->where('submission_stage', $section)->lockForUpdate()->latest('version')->first();
                $document = ProjectDocument::create([
                    'project_id' => $project->id,
                    'uploaded_by' => $request->user()->id,
                    'title' => $section === 'concept' ? 'Uploaded concept proposal' : 'Uploaded project proposal form',
                    'category' => $section === 'concept' ? 'Concept Proposal Submission' : 'Project Proposal Form Submission',
                    'submission_stage' => $section,
                    'version' => ($previous?->version ?? 0) + 1,
                    'replaces_document_id' => $previous?->id,
                    'submitted_at' => now(),
                    'path' => $path,
                    'original_name' => $originalName,
                ]);
                ChapterService::audit('project_submission_uploaded', $document, [], $revisionNote);
            }
            if ($before) \App\Models\ProjectReview::create(['project_id' => $project->id, 'user_id' => $request->user()->id, 'action' => 'revision_saved', 'from_status' => $before['status'], 'to_status' => $project->status, 'snapshot' => $before]);
            ChapterService::audit('project_saved', $project, $before);
            return $project;
        });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        }
        return redirect()->route('projects.show', $project)->with('success', 'Draft saved. Review the document and submit when ready.');
    }

    public function show(Request $request, Project $project)
    {
        abort_unless(ChapterService::canView($project, $request->user()), 403);
        $project->load(['owner', 'chair', 'tasks', 'allocations', 'transactions', 'documents', 'letters', 'reports', 'events', 'reviews.user']);
        $members = User::where('status', 'active')->orderBy('name')->get();
        $assignableMembers = $members->where('role', '!=', 'admin');
        $evidencePhotoLimit = (int) (\App\Models\ChapterSetting::where('key', 'evidence_photo_limit')->value('value') ?? 8);
        return view('chapter.project', compact('project', 'members', 'assignableMembers', 'evidencePhotoLimit'));
    }

    public function proposalPacket(Request $request, Project $project)
    {
        abort_unless(ChapterService::canView($project, $request->user()), 403);
        abort_unless($project->proposal, 404);
        $project->load(['owner', 'chair', 'documents', 'reviews']);
        return view('chapter.proposal-packet', compact('project'));
    }

    public function projectWordDocument(Request $request, Project $project, string $stage, ProjectWordDocumentService $documents)
    {
        abort_unless(ChapterService::canView($project, $request->user()), 403);
        abort_unless(in_array($stage, ['concept', 'proposal'], true), 404);
        abort_unless($stage === 'concept' ? filled($project->concept) : filled($project->proposal), 404);
        abort_unless(($stage === 'concept' ? $project->concept_submission_method : $project->proposal_submission_method) !== 'upload', 404);
        $project->loadMissing(['owner', 'chair']);
        [$path, $filename] = $documents->create($project, $stage);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }

    public function projectMemberPhoto(Request $request, Project $project, User $member)
    {
        abort_unless(ChapterService::canView($project, $request->user()), 403);
        $isChair = $project->chair_id === $member->id;
        $isAssignee = Task::where('project_id', $project->id)
            ->whereJsonContains('assignees', $member->id)->exists();
        abort_unless($isChair || $isAssignee, 404);
        $path = $member->profile['photo_path'] ?? null;
        abort_unless($path && str_starts_with($path, 'profile-photos/'.$member->id.'/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function transition(Request $request, Project $project, ChapterService $service)
    {
        $data = $request->validate([
            'action' => 'required|string', 'comments' => 'nullable|string|max:5000',
            'meeting_date' => 'required_if:action,approve,reject,defer|nullable|date|before_or_equal:today',
            'resolution_number' => 'required_if:action,approve|nullable|string|max:255|unique:bod_decisions,resolution_number',
            'evidence' => 'required_if:action,approve,reject,defer|nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        $path = $request->hasFile('evidence') ? $request->file('evidence')->store('bod-evidence', 'local') : null;
        try {
            app(\App\Services\ProposalWorkflowService::class)->transition($project, $request->user(), $data['action'], $data['comments'] ?? null, $data + ['evidence_path' => $path]);
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        }
        return back()->with('success', 'Project updated. The decision has been recorded in its history.');
    }

    public function tasks(Request $request)
    {
        if (!$request->has('mine') && $request->user()->role === 'member') $request->merge(['mine' => '1']);
        $request->validate([
            'mine' => 'nullable|boolean',
            'q' => 'nullable|string|max:200',
            'status' => ['nullable', Rule::in(['To Do', 'In Progress', 'Blocked', 'Completed'])],
            'project' => 'nullable|integer',
        ]);
        $visibleProjects = ChapterService::visibleProjects($request->user())
            ->orderBy('title')->get(['id', 'title']);
        $projectIds = $visibleProjects->pluck('id');
        $chairProjects = Project::whereIn('id', $projectIds)
            ->where('chair_id', $request->user()->id)
            ->with('tasks')->orderBy('title')->get();
        $managedProjects = $chairProjects->filter(fn (Project $project) => $project->canManage($request->user()));
        $taskSetupProjects = $chairProjects->reject(fn (Project $project) => $project->canManage($request->user()));
        $assignableMembers = User::where('status', 'active')->where('role', '!=', 'admin')->orderBy('name')->get();

        $taskQuery = Task::query()->whereIn('project_id', $projectIds)
            ->when($request->boolean('mine'), fn ($q) => $q->whereJsonContains('assignees', $request->user()->id))
            ->when($request->filled('project'), fn ($q) => $q->where('project_id', $request->integer('project')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'));

        $statusCounts = (clone $taskQuery)->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');
        $taskTotal = (int) $statusCounts->sum();
        $doneTasks = (int) ($statusCounts['Completed'] ?? 0);
        $progress = $taskTotal ? (int) round(100 * $doneTasks / $taskTotal) : 0;
        $overdueTasks = (clone $taskQuery)->whereDate('deadline', '<', today())
            ->where('status', '!=', 'Completed')->count();

        $tasks = $taskQuery->with('project.chair')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByRaw("CASE WHEN status = 'Completed' THEN 1 ELSE 0 END")
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')->paginate(18)->withQueryString();
        $assignedMembers = User::whereIn('id', $tasks->getCollection()
            ->flatMap(fn ($task) => $task->assignees ?? [])->unique())
            ->get(['id', 'name', 'profile'])->keyBy('id');

        return view('chapter.tasks', compact(
            'tasks', 'assignedMembers', 'assignableMembers', 'managedProjects', 'taskSetupProjects', 'visibleProjects', 'statusCounts',
            'taskTotal', 'doneTasks', 'progress', 'overdueTasks'
        ));
    }

    public function taskSave(Request $request, Project $project, ?Task $task = null)
    {
        if ($task) abort_unless($task->project_id === $project->id, 404);
        $manager = $project->canManage($request->user());
        abort_unless($manager || ($task && in_array($request->user()->id, $task->assignees ?? [], true) && in_array($project->status, ['Plan Locked', 'Ongoing'])), 403);
        if (!$manager) abort_unless(($task->assignment_responses[$request->user()->id]['response'] ?? null) === 'accepted', 403, 'Accept this assignment before updating progress.');
        $rules = ['status' => ['required', Rule::in(['To Do', 'In Progress', 'Blocked', 'Completed'])], 'notes' => 'nullable|string|max:10000', 'evidence' => 'nullable|string|max:10000'];
        if ($manager) $rules += ['title' => 'required|string|max:200', 'description' => 'nullable|string|max:10000', 'assignees' => 'required|array|min:1', 'assignees.*' => ['integer', Rule::exists('users', 'id')->where('status', 'active')->where('role', '!=', 'admin')], 'priority' => ['required', Rule::in(['Low', 'Medium', 'High', 'Urgent'])], 'starts_on' => 'nullable|date', 'deadline' => 'required|date|after_or_equal:starts_on', 'milestone' => 'nullable|string|max:255', 'dependencies' => 'nullable|array', 'dependencies.*' => ['integer', Rule::exists('tasks', 'id')->where('project_id', $project->id)]];
        $data = $request->validate($rules);
        if ($data['status'] === 'Completed' && !trim($data['evidence'] ?? '')) throw ValidationException::withMessages(['evidence' => 'Completion evidence is required.']);
        if ($manager) {
            $data['assignees'] = array_map('intval', $data['assignees']);
            $data['dependencies'] = array_map('intval', $data['dependencies'] ?? []);
            foreach ($data['dependencies'] as $id) {
                if ($task && $this->dependsOn($id, $task->id)) throw ValidationException::withMessages(['dependencies' => 'Task dependencies cannot contain a cycle.']);
            }
        }
        if ($data['status'] === 'Completed' && Task::whereIn('id', $data['dependencies'] ?? $task?->dependencies ?? [])->where('status', '!=', 'Completed')->exists()) throw ValidationException::withMessages(['status' => 'Complete prerequisite tasks first.']);
        DB::transaction(function () use ($task, $project, $data, $request) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            abort_unless(in_array($project->status, ['Plan Locked', 'Ongoing']) && $project->plan_locked_at, 403);
            if ($task) $task = Task::lockForUpdate()->findOrFail($task->id);
            if (!$project->canManage($request->user())) {
                abort_unless($task && in_array($request->user()->id, $task->assignees ?? [], true) && ($task->assignment_responses[$request->user()->id]['response'] ?? null) === 'accepted', 403);
            }
            $before = $task?->toArray() ?? [];
            $task ??= new Task(['project_id' => $project->id]);
            if (isset($data['assignees'])) $data['assignment_responses'] = array_intersect_key($task->assignment_responses ?? [], array_flip($data['assignees']));
            $task->fill($data)->save();
            ChapterService::audit('task_saved', $task, $before);
            ChapterService::notify(array_merge($task->assignees ?? [], [$project->chair_id]), 'Task updated: '.$task->title, $task->status, route('projects.show', $project, false).'#tasks');
        });
        return back()->with('success', 'Task saved.');
    }

    public function taskRespond(Request $request, Project $project, Task $task)
    {
        $data = $request->validate(['response' => 'required|in:accepted,declined', 'note' => 'required_if:response,declined|nullable|string|max:2000']);
        DB::transaction(function () use ($request, $project, $task, $data) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $task = Task::lockForUpdate()->findOrFail($task->id);
            abort_unless($task->project_id === $project->id, 404);
            abort_unless(in_array($project->status, ['Plan Locked', 'Ongoing']) && in_array($request->user()->id, $task->assignees ?? [], true) && $task->status !== 'Completed', 403);
            $before = $task->toArray();
            $responses = $task->assignment_responses ?? [];
            $responses[$request->user()->id] = $data + ['at' => now()->toIso8601String()];
            $task->update(['assignment_responses' => $responses]);
            ChapterService::audit('task_assignment_'.$data['response'], $task, $before, $data['note'] ?? null);
            ChapterService::notify([$project->chair_id], 'Task assignment '.$data['response'], $request->user()->name.': '.$task->title, route('projects.show', $project, false).'#tasks');
        });
        return back()->with('success', 'Assignment response recorded.');
    }

    private function dependsOn(int $id, int $target, array $seen = []): bool
    {
        if ($id === $target) return true;
        if (in_array($id, $seen)) return false;
        $seen[] = $id;
        foreach (Task::find($id)?->dependencies ?? [] as $dependency) if ($this->dependsOn((int) $dependency, $target, $seen)) return true;
        return false;
    }

    public function calendar(Request $request)
    {
        $query = $request->validate(['month' => 'nullable|date_format:Y-m', 'day' => 'nullable|date_format:Y-m-d', 'type' => ['nullable', Rule::in(['projects', 'activities', 'tasks', 'dues'])]]);
        $month = CarbonImmutable::createFromFormat('!Y-m', $query['month'] ?? now()->format('Y-m'))->startOfMonth();
        $gridStart = $month->startOfWeek(\Carbon\CarbonInterface::SUNDAY);
        $gridEnd = $month->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SATURDAY);
        $projects = ChapterService::visibleProjects($request->user())->get();
        $events = CalendarEvent::with('project')->where(fn ($q) => $q->whereIn('project_id', $projects->pluck('id'))->orWhereNull('project_id'))->get();
        $tasks = Task::with('project')->whereIn('project_id', $projects->pluck('id'))->get();
        $dues = MemberDue::with('payments')->where('member_id', $request->user()->id)->get();
        $calendarItems = [];
        $add = function ($date, string $title, string $type, ?string $context, ?string $url) use (&$calendarItems, $gridStart, $gridEnd): void {
            if (!$date) return;
            $key = CarbonImmutable::parse($date)->toDateString();
            if ($key < $gridStart->toDateString() || $key > $gridEnd->toDateString()) return;
            $calendarItems[$key][] = compact('title', 'type', 'context', 'url');
        };
        foreach ($events as $event) {
            $start = CarbonImmutable::parse($event->starts_on);
            $finish = CarbonImmutable::parse($event->ends_on ?? $event->starts_on);
            $cursor = $start->lessThan($gridStart) ? $gridStart : $start;
            $last = $finish->greaterThan($gridEnd) ? $gridEnd : $finish;
            for (; $cursor->lessThanOrEqualTo($last); $cursor = $cursor->addDay()) {
                $add($cursor, $event->title, $event->type, $event->project?->title ?? $event->venue ?? 'Chapter activity', $event->project_id ? route('projects.show', $event->project_id).'#timeline' : null);
            }
        }
        foreach ($projects as $project) {
            $add($project->starts_on, $project->title, 'Project start', $project->venue, route('projects.show', $project));
            if ($project->ends_on && $project->ends_on->toDateString() !== $project->starts_on?->toDateString()) {
                $add($project->ends_on, $project->title, 'Project end', $project->venue, route('projects.show', $project));
            }
        }
        foreach ($tasks as $task) $add($task->deadline, $task->title, 'Task deadline', $task->project?->title, route('projects.show', $task->project_id).'#tasks');
        foreach ($dues as $due) if ($due->balance > 0) $add($due->due_date, 'Member dues · '.$due->period, 'Dues', 'PHP '.number_format($due->balance, 2).' outstanding', route('dues'));
        if (!empty($query['type'])) {
            $calendarItems = collect($calendarItems)->map(fn ($items) => array_values(array_filter($items, fn ($item) => match ($query['type']) {
                'projects' => str_starts_with($item['type'], 'Project '),
                'activities' => in_array($item['type'], ['Activity', 'Milestone', 'Meeting', 'Review'], true),
                'tasks' => $item['type'] === 'Task deadline',
                'dues' => $item['type'] === 'Dues',
            })))->filter()->all();
        }
        $weeks = [];
        for ($week = $gridStart; $week->lessThanOrEqualTo($gridEnd); $week = $week->addWeek()) {
            $days = [];
            for ($i = 0; $i < 7; $i++) $days[] = $week->addDays($i);
            $weeks[] = $days;
        }
        $defaultDay = $month->isSameMonth(now()) ? now()->toDateString() : $month->toDateString();
        $selectedDay = isset($query['day']) && str_starts_with($query['day'], $month->format('Y-m')) ? $query['day'] : $defaultDay;
        $calendarFilter = $query['type'] ?? null;
        return view('chapter.calendar', compact('month', 'weeks', 'calendarItems', 'selectedDay', 'calendarFilter'));
    }

    public function eventSave(Request $request)
    {
        $data = $request->validate(['project_id' => 'nullable|integer|exists:projects,id', 'title' => 'required|string|max:200', 'type' => ['required', Rule::in(['Activity', 'Milestone', 'Meeting', 'Review'])], 'starts_on' => 'required|date', 'ends_on' => 'nullable|date|after_or_equal:starts_on', 'venue' => 'nullable|string|max:255', 'description' => 'nullable|string|max:10000']);
        if (!empty($data['project_id'])) abort_unless(Project::findOrFail($data['project_id'])->canManage($request->user()), 403);
        else abort_unless(Permissions::allows($request->user(), Permissions::CALENDAR_GLOBAL), 403);
        DB::transaction(function () use ($data, $request) {
            $event = CalendarEvent::create($data + ['created_by' => $request->user()->id]);
            ChapterService::audit('calendar_event_created', $event);
        });
        return back()->with('success', 'Calendar activity saved.');
    }

    public function upload(Request $request, Project $project)
    {
        abort_unless($project->canManage($request->user()) || $project->canEdit($request->user()), 403);
        $data = $request->validate(['title' => 'required|string|max:200', 'category' => 'required|string|max:100', 'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,txt,mp4|max:10240']);
        $path = $request->file('file')->store('project-documents', 'local');
        try {
            DB::transaction(function () use ($request, $project, $data, $path) {
                $doc = ProjectDocument::create(['project_id' => $project->id, 'uploaded_by' => $request->user()->id, 'title' => $data['title'], 'category' => $data['category'], 'path' => $path, 'original_name' => $request->file('file')->getClientOriginalName()]);
                ChapterService::audit('document_uploaded', $doc);
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Document uploaded securely.');
    }

    public function uploadPartnership(Request $request, Project $project)
    {
        $user = $request->user();
        abort_unless($user->status === 'active' && ($user->role === 'admin' || $project->created_by === $user->id || $project->isChair($user)), 403);
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);
        $path = $request->file('file')->store('project-partnerships', 'local');
        try {
            DB::transaction(function () use ($request, $project, $data, $path) {
                $previous = $project->documents()->where('submission_stage', 'partnership')->lockForUpdate()->latest('version')->first();
                $document = ProjectDocument::create([
                    'project_id' => $project->id,
                    'uploaded_by' => $request->user()->id,
                    'title' => $data['title'],
                    'category' => 'External Partnership Letter',
                    'submission_stage' => 'partnership',
                    'version' => ($previous?->version ?? 0) + 1,
                    'replaces_document_id' => $previous?->id,
                    'submitted_at' => now(),
                    'path' => $path,
                    'original_name' => $request->file('file')->getClientOriginalName(),
                ]);
                ChapterService::audit('partnership_letter_uploaded', $document);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->to(route('projects.show', $project).'#partnerships')->with('success', 'Partnership letter uploaded securely.');
    }

    public function storeEvidence(Request $request, Project $project)
    {
        abort_unless($project->isChair($request->user()) && in_array($project->status, ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review', 'Completed'], true), 403);

        $evidenceLimit = (int) (\App\Models\ChapterSetting::where('key', 'evidence_photo_limit')->value('value') ?? 8);
        $data = $request->validate([
            'type' => ['required', Rule::in(['attendance', 'evaluation'])],
            'title' => 'required|string|max:160',
            'activity_on' => 'required|date',
            'notes' => 'nullable|string|max:5000',
            'photos' => 'nullable|array|max:'.$evidenceLimit,
            'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);
        $files = $request->file('photos') ?? [];
        if ($data['type'] === 'attendance' && !$files) {
            throw ValidationException::withMessages(['photos' => 'Add at least one attendance photo.']);
        }
        if ($data['type'] === 'evaluation' && !$files && blank($data['notes'] ?? null)) {
            throw ValidationException::withMessages(['notes' => 'Write an evaluation or add a photo.']);
        }

        $paths = [];
        try {
            foreach ($files as $file) $paths[] = $file->store('project-evidence', 'local');
            DB::transaction(function () use ($request, $project, $data, $files, $paths) {
                $entries = $paths ?: [null];
                foreach ($entries as $index => $path) {
                    $document = ProjectDocument::create([
                        'project_id' => $project->id,
                        'uploaded_by' => $request->user()->id,
                        'title' => $data['title'].(count($entries) > 1 ? ' · Photo '.($index + 1) : ''),
                        'category' => $data['type'] === 'attendance' ? 'Attendance photo' : 'Project evaluation',
                        'submission_stage' => $data['type'],
                        'activity_on' => $data['activity_on'],
                        'notes' => $index === 0 ? ($data['notes'] ?? null) : null,
                        'path' => $path,
                        'original_name' => $path ? $files[$index]->getClientOriginalName() : null,
                        'submitted_at' => now(),
                    ]);
                    ChapterService::audit('project_evidence_added', $document);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }

        return redirect()->to(route('projects.show', $project).'#evidence')->with('success', 'Project evidence saved.');
    }

    public function download(Request $request, ProjectDocument $document)
    {
        abort_unless(ChapterService::canView($document->project, $request->user()), 403);
        abort_unless($document->path && Storage::disk('local')->exists($document->path), 404);
        return Storage::disk('local')->download($document->path, $document->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function previewDocument(Request $request, ProjectDocument $document, OfficePreviewService $previews)
    {
        abort_unless(ChapterService::canView($document->project, $request->user()), 403);
        abort_unless($document->path && Storage::disk('local')->exists($document->path), 404);
        abort_unless($document->canPreviewInline(), 415, 'This file type must be downloaded to view it.');

        if (in_array($document->extension(), ['doc', 'docx'], true)) {
            try {
                $preview = $previews->render($document);
            } catch (\Throwable $exception) {
                report($exception);
                abort(422, 'This Word file could not be previewed. Download the original file instead.');
            }

            $previewName = pathinfo($document->original_name, PATHINFO_FILENAME).'.pdf';

            return response()->file($preview, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $previewName).'"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        if ($document->extension() === 'mp4') {
            return response()->file(Storage::disk('local')->path($document->path), [
                'Content-Type' => 'video/mp4',
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return Storage::disk('local')->response(
            $document->path,
            $document->original_name,
            ['Content-Type' => $document->previewMimeType(), 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'],
            'inline'
        );
    }

    public function originalDocumentPreview(Request $request, ProjectDocument $document)
    {
        abort_unless(ChapterService::canView($document->project, $request->user()), 403);
        abort_unless($document->extension() === 'docx', 415);
        abort_unless($document->path && Storage::disk('local')->exists($document->path), 404);

        return response()->view('chapter.document-original-preview', compact('document'))
            ->header('Content-Security-Policy', "default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'self'; base-uri 'none'; object-src 'none'; form-action 'none'");
    }

    public function documents(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:200', 'type' => 'nullable|in:all,evidence,attendance,evaluation,documents']);

        $documents = ProjectDocument::query()
            ->with('project')
            ->whereHas('project', fn ($query) => $query->when($request->user()->role === 'member', fn ($query) => $query->where(fn ($query) => $query
                ->whereIn('status', Project::APPROVED)
                ->orWhere('created_by', $request->user()->id))))
            ->when($request->query('type') === 'evidence', fn ($query) => $query->whereIn('submission_stage', ['attendance', 'evaluation']))
            ->when(in_array($request->query('type'), ['attendance', 'evaluation'], true), fn ($query) => $query->where('submission_stage', $request->query('type')))
            ->when($request->query('type') === 'documents', fn ($query) => $query->where(fn ($query) => $query->whereNull('submission_stage')->orWhereNotIn('submission_stage', ['attendance', 'evaluation'])))
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('category', 'like', '%'.$request->string('q').'%')
                ->orWhereHas('project', fn ($project) => $project->where('title', 'like', '%'.$request->string('q').'%'))))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('chapter.documents', compact('documents'));
    }

    public function notifications(Request $request)
    {
        $filter = $request->query('filter');
        abort_unless(in_array($filter, [null, 'unread'], true), 404);
        $query = Notification::where('user_id', $request->user()->id);
        $unreadCount = (clone $query)->whereNull('read_at')->count();
        $items = $query->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->latest()->paginate(20)->withQueryString();
        return view('chapter.notifications', compact('items', 'unreadCount', 'filter'));
    }

    public function readNotifications(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'Notifications marked as read.');
    }

    public function openNotification(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        if (!$notification->read_at) $notification->update(['read_at' => now()]);
        $url = $notification->url;
        return is_string($url) && str_starts_with($url, '/') && !str_starts_with($url, '//')
            ? redirect()->to($url)
            : redirect()->route('notifications');
    }

    public function audit(Request $request)
    {
        abort_unless(Permissions::allows($request->user(), Permissions::AUDIT_VIEW), 403);
        $action = $request->validate(['action' => 'nullable|string|max:100'])['action'] ?? null;
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');
        $records = AuditLog::with('actor')->when($action, fn ($q) => $q->where('action', $action))
            ->latest()->paginate(25)->withQueryString();
        return view('chapter.activity-log', compact('records', 'actions', 'action'));
    }

}

