<?php
namespace App\Http\Controllers;

use App\Models\{Project, Task, User, CalendarEvent, ProjectDocument, Notification, AuditLog, MemberDue, ProjectReport};
use Carbon\CarbonImmutable;
use App\Services\ChapterService;
use App\Support\{ChapterCharts, ChapterForms};
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
        $reviewStatuses = [];
        if ($request->user()->role === 'bod' && $request->user()->concept_reviewer) $reviewStatuses[] = 'Submitted for President Review';
        if ($request->user()->role === 'bod' && $request->user()->proposal_reviewer) $reviewStatuses = array_merge($reviewStatuses, ['Submitted for Formal Approval', 'Completion Review']);
        if ($request->user()->role === 'admin') $reviewStatuses = ['Submitted for President Review', 'Submitted for Formal Approval', 'Completion Review'];
        $pending = $projects->whereIn('status', $reviewStatuses);
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
        elseif ($request->filter === 'concepts') $query->whereIn('status', ['Draft Concept', 'Submitted for President Review', 'Needs Revision', 'Endorsed for Development', 'Declined']);
        elseif ($request->filter === 'review') $query->whereIn('status', ['Submitted for President Review', 'Submitted for Formal Approval', 'Completion Review']);
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
        return view('chapter.project-form', ['project' => $project, 'proposal' => in_array($project->status, ['Full Proposal Draft', 'Returned for Revision'])]);
    }

    public function save(Request $request, ?Project $project = null)
    {
        if ($project) abort_unless($project->canEdit($request->user()) && $project->status !== 'Endorsed for Development', 403);
        $proposal = $project && in_array($project->status, ['Full Proposal Draft', 'Returned for Revision']);
        $section = $proposal ? 'proposal' : 'concept';
        $rules = ['title' => 'required|string|max:200', 'area' => ['required', Rule::in(ChapterForms::AREAS)], 'starts_on' => 'nullable|date', 'ends_on' => 'nullable|date|after_or_equal:starts_on', 'venue' => 'nullable|string|max:255', 'proposed_budget' => 'required|numeric|min:0|max:999999999.99'];
        foreach ($proposal ? ChapterForms::PROPOSAL : ChapterForms::CONCEPT as $key => $label) $rules[$section.'.'.$key] = 'nullable|string|max:15000';
        $data = $request->validate($rules);
        $project = DB::transaction(function () use ($project, $request, $data) {
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
            if ($before) \App\Models\ProjectReview::create(['project_id' => $project->id, 'user_id' => $request->user()->id, 'action' => 'revision_saved', 'from_status' => $before['status'], 'to_status' => $project->status, 'snapshot' => $before]);
            ChapterService::audit('project_saved', $project, $before);
            return $project;
        });
        return redirect()->route('projects.show', $project)->with('success', 'Draft saved. Review the letter and submit when ready.');
    }

    public function show(Request $request, Project $project)
    {
        abort_unless(ChapterService::canView($project, $request->user()), 403);
        $project->load(['owner', 'chair', 'tasks', 'allocations', 'transactions', 'documents', 'letters', 'reports', 'events', 'reviews.user']);
        $members = User::where('status', 'active')->orderBy('name')->get();
        $assignableMembers = $members->where('role', '!=', 'admin');
        return view('chapter.project', compact('project', 'members', 'assignableMembers'));
    }

    public function transition(Request $request, Project $project, ChapterService $service)
    {
        $data = $request->validate(['action' => 'required|string', 'comments' => 'nullable|string|max:5000', 'chair_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('status', 'active')->where('role', '!=', 'admin')]]);
        $service->transition($project, $request->user(), $data['action'], $data['comments'] ?? null, $data['chair_id'] ?? null);
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
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')->paginate(18)->withQueryString();
        $assigneeNames = User::whereIn('id', $tasks->getCollection()
            ->flatMap(fn ($task) => $task->assignees ?? [])->unique())->pluck('name', 'id');

        return view('chapter.tasks', compact(
            'tasks', 'assigneeNames', 'visibleProjects', 'statusCounts',
            'taskTotal', 'doneTasks', 'progress', 'overdueTasks'
        ));
    }

    public function taskSave(Request $request, Project $project, ?Task $task = null)
    {
        if ($task) abort_unless($task->project_id === $project->id, 404);
        $manager = $project->canManage($request->user());
        abort_unless($manager || ($task && in_array($request->user()->id, $task->assignees ?? [], true) && in_array($project->status, ['Approved', 'Ongoing'])), 403);
        $rules = ['status' => ['required', Rule::in(['To Do', 'In Progress', 'Blocked', 'Completed'])], 'notes' => 'nullable|string|max:10000', 'evidence' => 'nullable|string|max:10000'];
        if ($manager) $rules += ['title' => 'required|string|max:200', 'description' => 'nullable|string|max:10000', 'assignees' => 'required|array|min:1', 'assignees.*' => ['integer', Rule::exists('users', 'id')->where('status', 'active')->where('role', '!=', 'admin')], 'priority' => ['required', Rule::in(['Low', 'Medium', 'High', 'Urgent'])], 'starts_on' => 'nullable|date', 'deadline' => 'required|date|after_or_equal:starts_on', 'milestone' => 'nullable|string|max:255', 'dependencies' => 'nullable|array', 'dependencies.*' => ['integer', Rule::exists('tasks', 'id')->where('project_id', $project->id)]];
        $data = $request->validate($rules);
        if ($manager) {
            $data['assignees'] = array_map('intval', $data['assignees']);
            $data['dependencies'] = array_map('intval', $data['dependencies'] ?? []);
            foreach ($data['dependencies'] as $id) {
                if ($task && $this->dependsOn($id, $task->id)) throw ValidationException::withMessages(['dependencies' => 'Task dependencies cannot contain a cycle.']);
            }
        }
        if ($data['status'] === 'Completed' && Task::whereIn('id', $data['dependencies'] ?? $task?->dependencies ?? [])->where('status', '!=', 'Completed')->exists()) throw ValidationException::withMessages(['status' => 'Complete prerequisite tasks first.']);
        DB::transaction(function () use ($task, $project, $data) {
            $before = $task?->toArray() ?? [];
            $task ??= new Task(['project_id' => $project->id]);
            $task->fill($data)->save();
            ChapterService::audit('task_saved', $task, $before);
            ChapterService::notify(array_merge($task->assignees ?? [], [$project->chair_id]), 'Task updated: '.$task->title, $task->status, route('projects.show', $project, false).'#tasks');
        });
        return back()->with('success', 'Task saved.');
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
        $query = $request->validate(['month' => 'nullable|date_format:Y-m', 'day' => 'nullable|date_format:Y-m-d']);
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
        foreach ($dues as $due) if ($due->balance > 0) $add($due->due_date, 'Member dues ? '.$due->period, 'Dues', '?'.number_format($due->balance, 2).' outstanding', route('dues'));
        $weeks = [];
        for ($week = $gridStart; $week->lessThanOrEqualTo($gridEnd); $week = $week->addWeek()) {
            $days = [];
            for ($i = 0; $i < 7; $i++) $days[] = $week->addDays($i);
            $weeks[] = $days;
        }
        $defaultDay = $month->isSameMonth(now()) ? now()->toDateString() : $month->toDateString();
        $selectedDay = isset($query['day']) && str_starts_with($query['day'], $month->format('Y-m')) ? $query['day'] : $defaultDay;
        return view('chapter.calendar', compact('month', 'weeks', 'calendarItems', 'selectedDay'));
    }

    public function eventSave(Request $request)
    {
        $data = $request->validate(['project_id' => 'nullable|integer|exists:projects,id', 'title' => 'required|string|max:200', 'type' => ['required', Rule::in(['Activity', 'Milestone', 'Meeting', 'Review'])], 'starts_on' => 'required|date', 'ends_on' => 'nullable|date|after_or_equal:starts_on', 'venue' => 'nullable|string|max:255', 'description' => 'nullable|string|max:10000']);
        if (!empty($data['project_id'])) abort_unless(Project::findOrFail($data['project_id'])->canManage($request->user()), 403);
        else abort_unless($request->user()->role === 'admin', 403);
        DB::transaction(function () use ($data, $request) {
            $event = CalendarEvent::create($data + ['created_by' => $request->user()->id]);
            ChapterService::audit('calendar_event_created', $event);
        });
        return back()->with('success', 'Calendar activity saved.');
    }

    public function upload(Request $request, Project $project)
    {
        abort_unless($project->canManage($request->user()) || $project->canEdit($request->user()), 403);
        $data = $request->validate(['title' => 'required|string|max:200', 'category' => 'required|string|max:100', 'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,txt|max:10240']);
        $path = $request->file('file')->store('project-documents', 'local');
        try {
            DB::transaction(function () use ($request, $project, $data, $path) {
                $doc = ProjectDocument::create(['project_id' => $project->id, 'uploaded_by' => $request->user()->id, 'title' => $data['title'], 'category' => $data['category'], 'path' => $path, 'original_name' => $request->file('file')->getClientOriginalName()]);
                ChapterService::audit('document_uploaded', $doc);
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Document uploaded securely.');
    }

    public function download(Request $request, ProjectDocument $document)
    {
        abort_unless(ChapterService::canView($document->project, $request->user()), 403);
        abort_unless($document->path && Storage::disk('local')->exists($document->path), 404);
        return Storage::disk('local')->download($document->path, $document->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function documents(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:200']);

        $documents = ProjectDocument::query()
            ->with('project')
            ->whereHas('project', fn ($query) => $query->when($request->user()->role === 'member', fn ($query) => $query->where(fn ($query) => $query
                ->whereIn('status', Project::APPROVED)
                ->orWhere('created_by', $request->user()->id))))
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
        abort_unless($request->user()->role === 'admin', 403);
        $action = $request->validate(['action' => 'nullable|string|max:100'])['action'] ?? null;
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');
        $records = AuditLog::with('actor')->when($action, fn ($q) => $q->where('action', $action))
            ->latest()->paginate(25)->withQueryString();
        return view('chapter.activity-log', compact('records', 'actions', 'action'));
    }

}

