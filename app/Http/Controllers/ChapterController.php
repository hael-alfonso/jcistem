<?php
namespace App\Http\Controllers;

use App\Models\{Project, Task, User, CalendarEvent, ProjectDocument, Notification, AuditLog, MemberDue, ProjectReport};
use App\Services\ChapterService;
use App\Support\ChapterForms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\{Rule, ValidationException};

class ChapterController extends Controller
{
    public function dashboard(Request $request)
    {
        $projects = ChapterService::visibleProjects($request->user())->with('chair')->latest()->get();
        $tasks = Task::whereJsonContains('assignees', $request->user()->id)->where('status', '!=', 'Completed')->orderBy('deadline')->get();
        $dues = MemberDue::where('member_id', $request->user()->id)->get();
        return view('chapter.dashboard', compact('projects', 'tasks', 'dues'));
    }

    public function projects(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:200', 'filter' => 'nullable|string|max:80']);
        $query = ChapterService::visibleProjects($request->user())->with(['chair', 'owner']);
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
        return view('chapter.project', compact('project', 'members'));
    }

    public function transition(Request $request, Project $project, ChapterService $service)
    {
        $data = $request->validate(['action' => 'required|string', 'comments' => 'nullable|string|max:5000', 'chair_id' => 'nullable|integer|exists:users,id']);
        $service->transition($project, $request->user(), $data['action'], $data['comments'] ?? null, $data['chair_id'] ?? null);
        return back()->with('success', 'Project updated. The decision has been recorded in its history.');
    }

    public function tasks(Request $request)
    {
        $projects = ChapterService::visibleProjects($request->user())->pluck('id');
        $tasks = Task::with('project')->whereIn('project_id', $projects)->when($request->boolean('mine'), fn ($q) => $q->whereJsonContains('assignees', $request->user()->id))->orderBy('deadline')->paginate(30);
        return view('chapter.tasks', compact('tasks'));
    }

    public function taskSave(Request $request, Project $project, ?Task $task = null)
    {
        if ($task) abort_unless($task->project_id === $project->id, 404);
        $manager = $project->canManage($request->user());
        abort_unless($manager || ($task && in_array($request->user()->id, $task->assignees ?? [], true) && in_array($project->status, ['Approved', 'Ongoing'])), 403);
        $rules = ['status' => ['required', Rule::in(['To Do', 'In Progress', 'Blocked', 'Completed'])], 'notes' => 'nullable|string|max:10000', 'evidence' => 'nullable|string|max:10000'];
        if ($manager) $rules += ['title' => 'required|string|max:200', 'description' => 'nullable|string|max:10000', 'assignees' => 'required|array|min:1', 'assignees.*' => ['integer', Rule::exists('users', 'id')->where('status', 'active')], 'priority' => ['required', Rule::in(['Low', 'Medium', 'High', 'Urgent'])], 'starts_on' => 'nullable|date', 'deadline' => 'required|date|after_or_equal:starts_on', 'milestone' => 'nullable|string|max:255', 'dependencies' => 'nullable|array', 'dependencies.*' => ['integer', Rule::exists('tasks', 'id')->where('project_id', $project->id)]];
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
        $projects = ChapterService::visibleProjects($request->user())->get();
        $events = CalendarEvent::with('project')->where(fn ($q) => $q->whereIn('project_id', $projects->pluck('id'))->orWhereNull('project_id'))->orderBy('starts_on')->get();
        $tasks = Task::with('project')->whereIn('project_id', $projects->pluck('id'))->orderBy('deadline')->get();
        return view('chapter.calendar', compact('projects', 'events', 'tasks'));
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

    public function notifications(Request $request)
    {
        return view('chapter.notifications', ['items' => Notification::where('user_id', $request->user()->id)->latest()->paginate(30)]);
    }

    public function readNotifications(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'Notifications marked as read.');
    }

    public function audit(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);
        return view('chapter.audit', ['records' => AuditLog::with('actor')->latest()->paginate(50)]);
    }

    public function about() { return view('chapter.about'); }
}

