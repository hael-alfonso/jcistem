<?php
namespace App\Http\Controllers;

use App\Models\{Project, Task, User, CalendarEvent, ProjectDocument, Notification, AuditLog, MemberDue, ProjectReport, LedgerEntry, ExpenseClaim, ProjectFundRelease, BodDecision, RegistrationRequest};
use Carbon\CarbonImmutable;
use App\Services\{ChapterService, OfficePreviewService, ProjectArchiveService, ProjectWordDocumentService};
use App\Support\{ChapterCharts, ChapterFunds, ChapterForms, Money, Permissions, ProjectBudget, ProjectReviewQueue};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\{Rule, ValidationException};

class ChapterController extends Controller
{
    public function dashboard(Request $request)
    {
        ProjectArchiveService::archiveDue();
        $projects = ChapterService::visibleProjects($request->user())->with('chair')->latest()->get();
        $assignedTasks = Task::with('project.tasks')->whereIn('project_id', $projects->pluck('id'))
            ->whereJsonContains('assignees', $request->user()->id)
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')->orderBy('deadline')->get();
        $tasks = $assignedTasks->where('status', '!=', 'Completed');
        $pendingAssignments = $tasks->filter(fn ($task) => in_array($task->project?->status, ['Plan Locked', 'Ongoing'], true)
            && !in_array($task->assignment_responses[$request->user()->id]['response'] ?? null, ['accepted', 'declined'], true));
        $readyTasks = $tasks->filter(fn ($task) => $task->status !== 'Blocked' && $task->project?->status === 'Ongoing'
            && ($task->assignment_responses[$request->user()->id]['response'] ?? null) === 'accepted'
            && $task->project->tasks->whereIn('id', $task->dependencies ?? [])->where('status', 'Completed')->count() === count($task->dependencies ?? []));
        $personalProjects = $projects->filter(fn ($project) => $project->created_by === $request->user()->id
            || $project->chair_id === $request->user()->id || $assignedTasks->contains('project_id', $project->id));
        $dashboardProjects = $request->user()->role === 'member' ? $personalProjects : $projects;
        $chairProjects = $request->user()->role === 'member'
            ? $projects->where('chair_id', $request->user()->id)->whereIn('status', ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review']) : collect();
        $pendingIds = ProjectReviewQueue::apply(ChapterService::visibleProjects($request->user()), $request->user())->pluck('id');
        $pending = $projects->whereIn('id', $pendingIds);
        $financeActionProjects = $request->user()->role === 'treasurer'
            ? $projects->filter(fn ($project) => $project->chair_id !== $request->user()->id && in_array($project->status, ['Approved', 'Plan Locked', 'Ongoing'], true)
                && ($project->proposal_reviewed_at || $project->plan_locked_at) && !empty($project->budget_items) && (float) $project->allocated === 0.0) : collect();
        $pendingClaims = $request->user()->role === 'treasurer'
            ? ExpenseClaim::with('submitter')->where('status', 'Pending')->whereIn('project_id', $projects->filter(fn ($project) => $project->chair_id !== $request->user()->id)->pluck('id'))->latest()->get() : collect();
        $pendingFundReleases = match ($request->user()->role) {
            'treasurer' => ProjectFundRelease::with(['project', 'chair'])->where('status', 'Pending')->whereIn('project_id', $projects->filter(fn ($project) => $project->chair_id !== $request->user()->id)->pluck('id'))->latest()->get(),
            'member' => ProjectFundRelease::with(['project', 'treasurer'])->where('status', 'Pending')->where('chair_id', $request->user()->id)->latest()->get(),
            default => collect(),
        };
        $recentDecisions = $request->user()->role === 'bod'
            ? BodDecision::with('project')->whereIn('project_id', $projects->pluck('id'))->orderByDesc('meeting_date')->orderByDesc('id')->limit(4)->get() : collect();
        $adminActions = $request->user()->role === 'admin' ? [
            'registrations' => RegistrationRequest::where('status', 'pending')->count(),
            'reports' => ProjectReport::where('status', 'Submitted')->where('type', 'Completion')->count(),
        ] : [];
        $myDuesBalance = $request->user()->role === 'member'
            ? MemberDue::where('member_id', $request->user()->id)->with('payments')->get()->sum('balance') : 0;
        $charts = ChapterCharts::projectMix($dashboardProjects);
        $monitorStages = ChapterCharts::stages($projects);
        $taskStatuses = $assignedTasks->countBy('status');
        $financeCharts = ChapterCharts::finances($projects->whereIn('status', Project::APPROVED));
        $chapterFunds = ChapterFunds::postedBalance();
        $recentProjects = $dashboardProjects->sortByDesc('updated_at')->take(4);
        $recentTransactions = LedgerEntry::with('project')->whereIn('project_id', $dashboardProjects->whereIn('status', Project::APPROVED)->pluck('id'))
            ->where('status', 'Posted')->orderByDesc('transaction_date')->orderByDesc('id')->limit(4)->get();
        return view('chapter.dashboard', compact('projects', 'dashboardProjects', 'pending', 'myDuesBalance', 'tasks', 'pendingAssignments', 'readyTasks', 'chairProjects', 'financeActionProjects', 'pendingClaims', 'pendingFundReleases', 'recentDecisions', 'adminActions', 'charts', 'monitorStages', 'taskStatuses', 'financeCharts', 'chapterFunds', 'recentProjects', 'recentTransactions'));
    }

    public function projects(Request $request)
    {
        $legacyFolderRoutes = [
            'completed' => ['projects.completed', []],
            'Archived' => ['projects.archived', ['archive' => 'completed']],
            'archived' => ['projects.archived', ['archive' => 'completed']],
            'cancelled' => ['projects.archived', ['archive' => 'cancelled']],
            'closed' => ['projects.archived', ['archive' => 'cancelled']],
            'removed' => ['projects.removed', []],
        ];
        if (isset($legacyFolderRoutes[$request->query('filter')])) {
            [$route, $parameters] = $legacyFolderRoutes[$request->query('filter')];

            return redirect()->route($route, array_merge($request->except('filter'), $parameters));
        }

        return $this->projectCollection($request, 'active');
    }

    public function completedProjects(Request $request)
    {
        return $this->projectCollection($request, 'completed');
    }

    public function legacyCompletedProjects(Request $request)
    {
        return redirect()->to(str_replace('/projects/completed', '/projects/library', $request->fullUrl()));
    }

    public function archivedProjects(Request $request)
    {
        return $this->projectCollection(
            $request,
            $request->query('archive', 'completed') === 'cancelled' ? 'cancelled' : 'archived'
        );
    }

    public function cancelledProjects(Request $request)
    {
        return redirect()->route(
            'projects.archived',
            array_merge($request->query(), ['archive' => 'cancelled'])
        );
    }

    public function removedProjects(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        return $this->projectCollection($request, 'removed');
    }

    private function projectCollection(Request $request, string $folder)
    {
        ProjectArchiveService::archiveDue();
        $request->validate([
            'q' => 'nullable|string|max:200',
            'filter' => 'nullable|string|max:80',
            'from' => 'nullable|date',
            'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : ''),
            'sort' => 'nullable|in:newest,oldest,title',
            'archive' => 'nullable|in:completed,cancelled',
        ]);
        abort_unless(in_array($folder, ['active', 'completed', 'archived', 'cancelled', 'removed'], true), 404);
        abort_if($folder === 'removed' && $request->user()->role !== 'admin', 403);

        $folderQuery = ChapterService::visibleProjects($request->user());
        $projectFolderCounts = [
            'active' => (clone $folderQuery)->whereNotIn('status', ['Completed', 'Archived', 'Declined', 'Not Approved', 'Cancelled'])->count(),
            'completed' => (clone $folderQuery)->where('status', 'Completed')->whereNull('archived_at')->count(),
            'archived' => (clone $folderQuery)->where('status', 'Completed')->whereNotNull('archived_at')->count(),
            'cancelled' => (clone $folderQuery)->whereIn('status', ['Declined', 'Not Approved', 'Cancelled'])->whereNotNull('archived_at')->count(),
        ];
        if ($request->user()->role === 'admin') {
            $projectFolderCounts['removed'] = (clone $folderQuery)->onlyTrashed()->count();
        }
        $query = ChapterService::visibleProjects($request->user())
            ->with(['chair', 'owner', 'reports'])
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'Completed')])
            ->withSum('allocations as allocated_amount', 'amount');
        $statusFilter = $request->query('filter');
        match ($folder) {
            'active' => $statusFilter === 'completed'
                ? $query->where('status', 'Completed')
                : ($statusFilter === 'cancelled'
                    ? $query->whereIn('status', ['Declined', 'Not Approved', 'Cancelled'])
                    : $query->whereNotIn('status', ['Completed', 'Archived', 'Declined', 'Not Approved', 'Cancelled'])),
            'completed' => $query->where('status', 'Completed')->whereNull('archived_at'),
            'archived' => $query->where('status', 'Completed')->whereNotNull('archived_at'),
            'cancelled' => $query->whereIn('status', ['Declined', 'Not Approved', 'Cancelled'])->whereNotNull('archived_at'),
            'removed' => $query->onlyTrashed(),
        };
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%')
            ->orWhere('reference', 'like', '%'.$request->string('q').'%')
            ->orWhereHas('chair', fn ($chair) => $chair->where('name', 'like', '%'.$request->string('q').'%')));
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->query('from'));
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->query('to'));
        if ($folder === 'active' && $request->filter === 'mine') $query->where(fn ($q) => $q->where('created_by', $request->user()->id)->orWhere('chair_id', $request->user()->id)->orWhereHas('tasks', fn ($q) => $q->whereJsonContains('assignees', $request->user()->id)));
        elseif ($folder === 'active' && $request->filter === 'proposals') $query->where('created_by', $request->user()->id);
        elseif ($folder === 'active' && $request->filter === 'chairing') $query->where('chair_id', $request->user()->id)->whereIn('status', Project::APPROVED);
        elseif ($folder === 'active' && $request->filter === 'board') {
            abort_unless(in_array($request->user()->role, ['admin', 'bod', 'treasurer'], true), 403);
            $query->where(function ($items) use ($request) {
                $items->where('status', 'Submitted for Formal Approval')->whereNotNull('proposal_budget_reviewed_at');
                if ($request->user()->role === 'admin') $items->orWhere('status', 'BOD Agenda');
            });
        }
        // Keep the legacy concepts view available for saved links and existing navigation.
        // The visible status filter now uses the clearer status names below, but a
        // concepts request must still show only pre-approval concept work.
        elseif ($folder === 'active' && $request->filter === 'concepts') $query->whereIn('status', [
            'Draft Concept',
            'Needs Revision',
            'Submitted for President Review',
            'Admin Review',
            'Treasurer Review',
            'BOD Agenda',
            'Endorsed for Development',
        ]);
        elseif ($folder === 'active' && $request->filter === 'review') {
            abort_unless(in_array($request->user()->role, ['admin', 'treasurer', 'bod'], true), 403);
            ProjectReviewQueue::apply($query, $request->user());
        }
        elseif ($folder === 'active' && $request->filter === 'approved') $query->whereIn('status', ['Approved', 'Plan Locked']);
        elseif ($folder === 'active' && $request->filter === 'ongoing') $query->where('status', 'Ongoing');
        elseif ($folder === 'active' && $request->filter === 'launching') $query
            ->where('status', 'Ongoing')
            ->whereDate('starts_on', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereDate('ends_on', '>=', now()->toDateString())->orWhereNull('ends_on'));
        elseif ($folder === 'active' && $request->filter === 'completed') { /* applied with the folder constraint above */ }
        elseif ($folder === 'active' && $request->filter === 'cancelled') { /* applied with the folder constraint above */ }
        elseif ($folder === 'active' && $request->filled('filter')) $query->where('status', $request->filter);
        $orderedQuery = match ($request->query('sort', 'newest')) {
            'oldest' => $query->oldest('created_at')->orderBy('id'),
            'title' => $query->orderBy('title')->orderBy('id'),
            default => $query->latest('created_at')->latest('id'),
        };
        $projectFolder = [
            'key' => $folder,
            'route' => match ($folder) {
                'completed' => 'projects.completed',
                'archived' => 'projects.archived',
                'cancelled' => 'projects.archived',
                'removed' => 'projects.removed',
                default => 'projects',
            },
            'routeParams' => match ($folder) {
                'archived' => ['archive' => 'completed'],
                'cancelled' => ['archive' => 'cancelled'],
                default => [],
            },
            'title' => match ($folder) {
                'completed' => 'Completed Projects',
                'archived', 'cancelled' => 'Project Archive',
                'removed' => 'Deleted Projects',
                default => $request->filter === 'review' ? 'Project Review Queue' : ($request->filter === 'mine' ? 'My Current Projects' : 'Current Projects'),
            },
            'eyebrow' => $request->filter === 'review'
                ? 'REVIEW WORKFLOW'
                : ($folder === 'active' ? 'PROJECT WORK' : (in_array($folder, ['archived', 'cancelled'], true) ? 'PROJECT ARCHIVE' : 'PROJECT LIBRARY')),
            'description' => match ($folder) {
                'completed' => 'Finished projects remain here for seven days before moving to the archive. Admin can archive them sooner.',
                'archived', 'cancelled' => 'Completed and cancelled or closed project records are kept together here, with separate sections for clear reference.',
                'removed' => 'Admin-only deleted-project folder. Records can be restored without losing finance or audit history.',
                default => $request->filter === 'review'
                    ? match ($request->user()->role) { 'admin' => 'Review concepts, record signed Board decisions, and follow submitted completion reports.', 'treasurer' => 'Verify independent concept and proposal budgets, then review submitted liquidations.', default => 'Open projects ready for Board deliberation and recorded decisions.' }
                    : 'Find current concepts, reviews, approved plans, and ongoing project work.',
            },
        ];

        if ($request->boolean('print')) {
            abort_unless($request->user()->role === 'admin', 403);
            $printProjects = $orderedQuery->get();

            return view('chapter.project-report', [
                'projects' => $printProjects,
                'projectFolder' => $projectFolder,
                'generatedAt' => now(),
            ]);
        }

        $projects = $orderedQuery->paginate(12)->withQueryString();

        return view('chapter.projects', compact('projects', 'projectFolderCounts', 'projectFolder'));
    }

    public function create()
    {
        return view('chapter.project-form', ['project' => new Project, 'proposal' => false]);
    }

    public function edit(Request $request, Project $project)
    {
        abort_unless($project->canEdit($request->user()), 403);
        return view('chapter.project-form', ['project' => $project, 'proposal' => in_array($project->status, ['Endorsed for Development', 'Full Proposal Draft', 'Returned for Revision', 'Approved'], true)]);
    }

    public function save(Request $request, ?Project $project = null)
    {
        if ($project) abort_unless($project->canEdit($request->user()), 403);
        $proposal = $project && in_array($project->status, ['Endorsed for Development', 'Full Proposal Draft', 'Returned for Revision', 'Approved'], true);
        $section = $proposal ? 'proposal' : 'concept';
        $methodField = $section.'_submission_method';
        $request->merge([$methodField => $request->input($methodField, 'online')]);
        $rules = ['title' => 'required|string|max:200', 'area' => ['required', Rule::in(ChapterForms::AREAS)], 'starts_on' => 'nullable|date', 'ends_on' => 'nullable|date|after_or_equal:starts_on', 'venue' => 'nullable|string|max:255', 'no_budget' => 'sometimes|boolean', 'proposed_budget' => 'required|numeric|decimal:0,2|min:0|max:999999999.99', 'budget_items' => 'nullable|array|max:50', 'budget_items.*.id' => 'nullable|uuid|distinct', 'budget_items.*.category' => ['required', Rule::in(array_keys(ProjectBudget::CATEGORIES))], 'budget_items.*.description' => 'required|string|max:255', 'budget_items.*.quantity' => 'required|numeric|decimal:0,2|min:0.01|max:999999.99', 'budget_items.*.unit_cost' => 'required|numeric|decimal:0,2|min:0|max:999999999.99', $methodField => ['required', Rule::in(['online', 'upload'])], 'submission_file' => 'nullable|prohibited_unless:'.$methodField.',upload|file|mimes:pdf,doc,docx|max:10240', 'revision_note' => 'nullable|string|max:2000'];
        $sectionKeys = array_keys($proposal ? ChapterForms::PROPOSAL : ChapterForms::CONCEPT);
        if ($proposal) $sectionKeys = array_merge($sectionKeys, ['committee_director', 'president_name', 'board_meeting_date', 'projected_income', 'income_lines', 'expense_lines']);
        $rules[$section] = 'sometimes|array:'.implode(',', $sectionKeys);
        $rules['budget_items.*'] = 'required|array:id,category,description,quantity,unit_cost,amount';
        foreach ($proposal ? ChapterForms::PROPOSAL : ChapterForms::CONCEPT as $key => $label) $rules[$section.'.'.$key] = 'nullable|string|max:15000';
        if (!$proposal) $rules['concept.sdg_number'] = ['nullable', Rule::in(array_keys(ChapterForms::SDGS))];
        if ($proposal) {
            $rules['proposal.committee_director'] = 'nullable|string|max:200';
            $rules['proposal.president_name'] = 'nullable|string|max:200';
            $rules['proposal.board_meeting_date'] = 'nullable|date_format:Y-m-d';
            $rules['proposal.projected_income'] = 'nullable|numeric|min:0|max:999999999.99';
            $rules['proposal.income_lines'] = 'nullable|array:'.implode(',', array_keys(ChapterForms::PROPOSAL_INCOME_LINES));
            $rules['proposal.expense_lines'] = 'nullable|array:'.implode(',', array_keys(ChapterForms::PROPOSAL_EXPENSE_LINES));
            foreach (array_keys(ChapterForms::PROPOSAL_INCOME_LINES) as $key) $rules['proposal.income_lines.'.$key] = 'nullable|numeric|decimal:0,2|min:0|max:999999999.99';
            foreach (array_keys(ChapterForms::PROPOSAL_EXPENSE_LINES) as $key) $rules['proposal.expense_lines.'.$key] = 'nullable|numeric|decimal:0,2|min:0|max:999999999.99';
        }
        $data = $request->validate($rules);
        if (!$proposal && array_key_exists('sdg_number', $data['concept'] ?? [])) {
            $data['concept']['sdg_name'] = ChapterForms::SDGS[$data['concept']['sdg_number']] ?? null;
        }
        $noBudget = (bool) ($data['no_budget'] ?? false);
        unset($data['no_budget']);
        $submittedBudgetItems = array_key_exists('budget_items', $data) || $noBudget;
        $budgetItems = $noBudget ? [] : ProjectBudget::normalize($data['budget_items'] ?? []);
        if ($noBudget) {
            $data['budget_items'] = [];
            $data['proposed_budget'] = '0.00';
        } elseif ($budgetItems) {
            $data['budget_items'] = $budgetItems;
            $data['proposed_budget'] = ProjectBudget::total($budgetItems);
        } elseif ($submittedBudgetItems) {
            $data['budget_items'] = [];
            $data['proposed_budget'] = '0.00';
        } elseif ($project?->budget_items) {
            $data['budget_items'] = $project->budget_items;
            $data['proposed_budget'] = ProjectBudget::total($project->budget_items);
        } else {
            unset($data['budget_items']);
        }
        if (Money::cents($data['proposed_budget']) > Money::cents('999999999.99')) {
            throw ValidationException::withMessages(['budget_items' => 'The itemized project budget exceeds the allowed maximum. Reduce the quantities or unit costs.']);
        }
        if ($proposal && $project->allocations()->whereNotNull('budget_item_id')->exists()) {
            $originalLines = collect($project->budget_items ?? [])->mapWithKeys(fn ($item) => [$item['id'] ?? '' => [$item['category'] ?? '', $item['description'] ?? '', (string) ($item['amount'] ?? '')]])->all();
            $updatedLines = collect($data['budget_items'] ?? [])->mapWithKeys(fn ($item) => [$item['id'] ?? '' => [$item['category'] ?? '', $item['description'] ?? '', (string) ($item['amount'] ?? '')]])->all();
            if ($originalLines !== $updatedLines) throw ValidationException::withMessages(['budget_items' => 'Expense lines with approved allocations are locked. Ask the Treasurer to review an amendment first.']);
        }
        if ($proposal && $data[$methodField] === 'online') {
            $incomeLines = array_filter($data['proposal']['income_lines'] ?? [], fn ($value) => $value !== null && $value !== '');
            if ($incomeLines) {
                $incomeCents = array_sum(array_map(Money::cents(...), $incomeLines));
                if ($incomeCents > Money::cents('999999999.99')) {
                    throw ValidationException::withMessages(['proposal.income_lines' => 'The combined estimated income exceeds the allowed maximum.']);
                }
                $data['proposal']['projected_income'] = Money::decimal($incomeCents);
            } elseif (array_key_exists('income_lines', $data['proposal'] ?? [])) {
                $data['proposal']['projected_income'] = null;
            } elseif (isset($project->proposal['projected_income'])) {
                $data['proposal']['projected_income'] = $project->proposal['projected_income'];
            }
            if ($budgetItems) $data['proposal']['expense_lines'] = ProjectBudget::officialExpenseLines($budgetItems);
        }
        if ($proposal && $project->proposal_reviewed_at && \App\Support\Money::cents($data['proposed_budget']) !== \App\Support\Money::cents($project->proposed_budget)) {
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
                abort_unless($project->canEdit($request->user()), 403);
            }
            $before = $project?->toArray() ?? [];
            $project ??= new Project(['created_by' => $request->user()->id, 'status' => 'Draft Concept']);
            $project->fill($data);
            $changed = !$project->exists || $project->isDirty() || (bool) $path;
            if ($changed) $project->revision = ($project->revision ?? 0) + 1;
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
                    'project_revision' => $project->revision,
                    'replaces_document_id' => $previous?->id,
                    'submitted_at' => now(),
                    'path' => $path,
                    'original_name' => $originalName,
                ]);
                ChapterService::audit('project_submission_uploaded', $document, [], $revisionNote);
            }
            if ($before && $changed) \App\Models\ProjectReview::create(['project_id' => $project->id, 'user_id' => $request->user()->id, 'action' => 'revision_saved', 'from_status' => $before['status'], 'to_status' => $project->status, 'snapshot' => $before]);
            if ($changed) ChapterService::audit('project_saved', $project, $before);
            return $project;
        });
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        }
        return redirect()->route('projects.show', $project)->with('success', 'Draft saved. Review the document and submit when ready.');
    }

    public function projectReuse(Request $request, Project $project)
    {
        abort_unless(ChapterService::canView($project, $request->user()), 403);
        abort_unless(in_array($project->status, ['Completed', 'Archived'], true), 403);

        $copy = DB::transaction(function () use ($project, $request) {
            $source = Project::lockForUpdate()->findOrFail($project->id);
            abort_unless(in_array($source->status, ['Completed', 'Archived'], true), 403);

            $copy = Project::create([
                'created_by' => $request->user()->id,
                'chair_id' => null,
                'title' => Str::limit($source->title, 188, '').' (New Cycle)',
                'area' => $source->area,
                'status' => 'Draft Concept',
                'venue' => $source->venue,
                'starts_on' => null,
                'ends_on' => null,
                'proposed_budget' => $source->proposed_budget,
                'budget_items' => $source->budget_items,
                'concept' => $source->concept,
                'proposal' => null,
                'concept_submission_method' => 'online',
                'proposal_submission_method' => null,
                'budget_reviewed_at' => null,
                'budget_reviewed_by' => null,
                'proposal_submitted_revision' => null,
                'proposal_budget_reviewed_at' => null,
                'proposal_budget_reviewed_by' => null,
                'proposal_reviewed_at' => null,
                'proposal_reviewed_by' => null,
                'plan_locked_at' => null,
                'revision' => 1,
            ]);
            $copy->update(['reference' => 'JCI-'.now()->year.'-'.str_pad((string) $copy->id, 5, '0', STR_PAD_LEFT)]);
            ChapterService::audit('project_reused', $copy, [], 'Source project: '.($source->reference ?: '#'.$source->id));

            return $copy;
        });

        return redirect()->route('projects.edit', $copy)
            ->with('success', 'A new draft was created from the stored project. Review its dates, venue, plan, and budget before submitting it.');
    }

    public function projectDelete(Request $request, Project $project)
    {
        abort_unless($request->user()->role === 'admin', 403);
        DB::transaction(function () use ($project) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            abort_unless($project->archived_at && in_array($project->status, ['Completed', 'Cancelled', 'Declined', 'Not Approved'], true), 403);
            if ($project->expenseClaims()->where('status', 'Pending')->exists()) {
                throw ValidationException::withMessages(['project_removal' => 'Resolve pending expense claims before removing this project.']);
            }
            if ($project->fundReleases()->where('status', 'Pending')->exists()) {
                throw ValidationException::withMessages(['project_removal' => 'Resolve pending fund releases before removing this project.']);
            }
            $before = $project->toArray();
            $project->delete();
            ChapterService::audit('project_removed', $project, $before, 'Removed by Admin from Deleted Projects.');
        });
        return redirect()->route('projects.removed')->with('success', 'Project moved to Deleted Projects. Its finance and audit history is preserved.');
    }

    public function projectArchive(Request $request, Project $project)
    {
        abort_unless($request->user()->role === 'admin', 403);
        DB::transaction(function () use ($project): void {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            abort_unless($project->status === 'Completed' && !$project->archived_at, 403);
            $before = $project->toArray();
            $project->update(['archived_at' => now()]);
            ChapterService::audit('project_archived_by_admin', $project, $before, 'Admin archived the completed project before the automatic seven-day move.');
        });

        return redirect()->route('projects.archived', ['archive' => 'completed'])->with('success', 'Completed project moved to the archive. Its status remains Completed.');
    }

    public function projectRestore(Request $request, int $projectId)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $project = DB::transaction(function () use ($projectId) {
            $project = Project::onlyTrashed()->lockForUpdate()->findOrFail($projectId);
            $before = $project->toArray();
            $project->restore();
            ChapterService::audit('project_restored', $project, $before);

            return $project;
        });
        if ($project->status === 'Completed') {
            $route = $project->archived_at ? 'projects.archived' : 'projects.completed';
            $routeParameters = $project->archived_at ? ['archive' => 'completed'] : [];
        } else {
            $route = 'projects.archived';
            $routeParameters = ['archive' => 'cancelled'];
        }

        return redirect()->route($route, $routeParameters)->with('success', 'Project restored to its chapter collection.');
    }

    public function show(Request $request, Project $project)
    {
        abort_unless(ChapterService::canView($project, $request->user()), 403);
        $project->load(['owner', 'chair', 'tasks', 'allocations', 'transactions', 'expenseClaims.submitter', 'fundReleases.chair', 'fundReleases.treasurer', 'fundReleases.ledgerEntry', 'documents', 'letters', 'reports', 'events', 'reviews.user']);
        $removedTasks = $request->user()->role === 'admin'
            ? $project->tasks()->onlyTrashed()->latest('deleted_at')->get()
            : collect();
        $members = User::where('status', 'active')->orderBy('name')->get();
        $assignableMembers = $members->where('role', '!=', 'admin');
        $evidencePhotoLimit = (int) (\App\Models\ChapterSetting::where('key', 'evidence_photo_limit')->value('value') ?? 8);
        return view('chapter.project', compact('project', 'members', 'assignableMembers', 'evidencePhotoLimit', 'removedTasks'));
    }

    public function proposalPacket(Request $request, Project $project)
    {
        abort_unless(ChapterService::canView($project, $request->user()), 403);
        abort_unless($project->proposal_submission_method !== 'upload' && $project->proposal, 404);
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
            'meeting_date' => 'required_if:action,approve_full_proposal,reject_full_proposal,defer_full_proposal|nullable|date|before_or_equal:today',
            'evidence' => 'required_if:action,approve_full_proposal,reject_full_proposal,defer_full_proposal|nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);
        $recordsBoardVerdict = in_array($data['action'], ['approve_full_proposal', 'reject_full_proposal', 'defer_full_proposal'], true);
        $path = $recordsBoardVerdict && $request->hasFile('evidence') ? $request->file('evidence')->store('bod-evidence', 'local') : null;
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
            'assignee' => 'nullable|integer|exists:users,id',
            'from' => 'nullable|date', 'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : ''),
        ]);
        $visibleProjects = ChapterService::visibleProjects($request->user())
            ->orderBy('title')->get(['id', 'title']);
        $projectIds = $visibleProjects->pluck('id');
        $chairProjects = Project::whereIn('id', $projectIds)
            ->where('chair_id', $request->user()->id)
            ->with('tasks')->orderBy('title')->get();
        $managedProjects = $chairProjects->filter(fn (Project $project) => $project->canManage($request->user()));
        $taskSetupProjects = $chairProjects->filter(fn (Project $project) => in_array($project->status, ['Approved', 'Submitted for Formal Approval'], true));
        $selectedProject = $visibleProjects->firstWhere('id', $request->integer('project'));
        $assignableMembers = User::where('status', 'active')->where('role', '!=', 'admin')->orderBy('name')->get();

        $taskQuery = Task::query()->whereIn('project_id', $projectIds)
            ->when($request->boolean('mine'), fn ($q) => $q->whereJsonContains('assignees', $request->user()->id))
            ->when($request->filled('project'), fn ($q) => $q->where('project_id', $request->integer('project')))
            ->when($request->filled('assignee'), fn ($q) => $q->whereJsonContains('assignees', $request->integer('assignee')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('deadline', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('deadline', '<=', $request->query('to')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($match) => $match->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhereHas('project', fn ($project) => $project->where('title', 'like', '%'.$request->string('q').'%'))));

        $statusCounts = (clone $taskQuery)->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');
        $taskTotal = (int) $statusCounts->sum();
        $doneTasks = (int) ($statusCounts['Completed'] ?? 0);
        $progress = $taskTotal ? (int) round(100 * $doneTasks / $taskTotal) : 0;
        $overdueTasks = (clone $taskQuery)->whereDate('deadline', '<', today())
            ->where('status', '!=', 'Completed')->count();

        $tasks = $taskQuery->with(['project.chair', 'project.tasks'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByRaw("CASE WHEN status = 'Completed' THEN 1 ELSE 0 END")
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')->paginate(18)->withQueryString();
        $assignedMembers = User::whereIn('id', $tasks->getCollection()
            ->flatMap(fn ($task) => $task->assignees ?? [])->unique())
            ->get(['id', 'name', 'status', 'profile', 'updated_at'])->keyBy('id');

        return view('chapter.tasks', compact(
            'tasks', 'assignedMembers', 'assignableMembers', 'managedProjects', 'taskSetupProjects', 'visibleProjects', 'statusCounts',
            'taskTotal', 'doneTasks', 'progress', 'overdueTasks', 'selectedProject'
        ));
    }

    public function taskSave(Request $request, Project $project, ?Task $task = null)
    {
        DB::transaction(function () use ($request, $project, $task) {
            // Serialize task changes with project transitions and other task changes.
            $project = Project::lockForUpdate()->findOrFail($project->id);
            if ($task) $task = Task::lockForUpdate()->findOrFail($task->id);
            if ($task) abort_unless($task->project_id === $project->id, 404);
            abort_unless($request->user()->status === 'active' && $project->plan_locked_at && in_array($project->status, ['Plan Locked', 'Ongoing'], true), 403);
            $manager = $project->canManage($request->user());
            abort_unless($manager || ($task && in_array($request->user()->id, $task->assignees ?? [], true) && $project->status === 'Ongoing'), 403);
            if (!$manager) abort_unless(($task->assignment_responses[$request->user()->id]['response'] ?? null) === 'accepted', 403, 'Accept this assignment before updating progress.');
            $rules = ['status' => ['required', Rule::in(['To Do', 'In Progress', 'Blocked', 'Completed'])], 'notes' => 'nullable|string|max:10000', 'evidence' => 'nullable|string|max:10000'];
        if ($manager) $rules += ['title' => 'required|string|max:200', 'description' => 'nullable|string|max:10000', 'assignees' => 'required|array|min:1', 'assignees.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('status', 'active')->where('role', '!=', 'admin')], 'priority' => ['required', Rule::in(['Low', 'Medium', 'High', 'Urgent'])], 'starts_on' => 'nullable|date', 'deadline' => ['required', 'date', Rule::when($request->filled('starts_on'), 'after_or_equal:starts_on')], 'milestone' => 'nullable|string|max:255', 'dependencies' => 'nullable|array', 'dependencies.*' => ['integer', 'distinct', Rule::exists('tasks', 'id')->where('project_id', $project->id)->whereNull('deleted_at')]];
            $data = $request->validate($rules);
            if ($project->status === 'Plan Locked' && $data['status'] !== 'To Do') throw ValidationException::withMessages(['status' => 'Launch the project before recording task progress.']);
            if ($data['status'] === 'Completed' && !trim($data['evidence'] ?? '')) throw ValidationException::withMessages(['evidence' => 'Completion evidence is required.']);
            if ($manager) {
                $data['assignees'] = array_map('intval', $data['assignees']);
                $data['dependencies'] = array_map('intval', $data['dependencies'] ?? []);
                foreach ($data['dependencies'] as $id) {
                    if ($task && $this->dependsOn($id, $task->id)) throw ValidationException::withMessages(['dependencies' => 'Task dependencies cannot contain a cycle.']);
                }
            }
            $dependencyIds = $data['dependencies'] ?? $task?->dependencies ?? [];
            $completedDependencies = $project->tasks()->whereIn('id', $dependencyIds)->where('status', 'Completed')->count();
            if (in_array($data['status'], ['In Progress', 'Completed'], true) && $completedDependencies !== count($dependencyIds)) throw ValidationException::withMessages(['status' => 'Complete prerequisite tasks before starting or completing this task.']);
            if ($task?->status === 'Completed' && $data['status'] !== 'Completed'
                && $project->tasks()->whereIn('status', ['In Progress', 'Completed'])->get()->contains(fn ($dependent) => in_array($task->id, $dependent->dependencies ?? [], true))) {
                throw ValidationException::withMessages(['status' => 'Move dependent tasks back to To Do or Blocked before reopening this prerequisite.']);
            }
            $before = $task?->toArray() ?? [];
            $task ??= new Task(['project_id' => $project->id]);
            if (isset($data['assignees'])) $data['assignment_responses'] = array_intersect_key($task->assignment_responses ?? [], array_flip($data['assignees']));
            $task->fill($data)->save();
            $project->touch();
            ChapterService::audit('task_saved', $task, $before);
            ChapterService::notify(array_merge($task->assignees ?? [], [$project->chair_id]), 'Task updated: '.$task->title, $task->status, route('projects.show', $project, false).'#task-'.$task->id);
        });
        return back()->with('success', 'Task saved.');
    }

    public function taskRemove(Request $request, Project $project, Task $task)
    {
        abort_unless($request->user()->status === 'active' && $request->user()->role === 'admin', 403);
        DB::transaction(function () use ($project, $task) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $task = Task::lockForUpdate()->findOrFail($task->id);
            abort_unless($task->project_id === $project->id, 404);
            abort_unless($project->plan_locked_at && in_array($project->status, ['Plan Locked', 'Ongoing'], true), 403);

            $dependent = $project->tasks()->where('id', '!=', $task->id)->get(['title', 'dependencies'])
                ->first(fn (Task $other) => in_array($task->id, array_map('intval', $other->dependencies ?? []), true));
            if ($dependent) {
                throw ValidationException::withMessages(['task' => 'Remove this task from the prerequisites of "'.$dependent->title.'" first.']);
            }

            $before = $task->toArray();
            $task->delete();
            $project->touch();
            ChapterService::audit('task_removed', $task, $before);
            ChapterService::notify(array_merge($task->assignees ?? [], [$project->chair_id]), 'Task removed: '.$task->title,
                'The task was removed from '.$project->title.' by an administrator.', route('projects.show', $project, false).'#tasks');
        });

        return back()->with('success', 'Task removed. An administrator can restore it while the project is active.');
    }

    public function taskRestore(Request $request, Project $project, int $taskId)
    {
        abort_unless($request->user()->status === 'active' && $request->user()->role === 'admin', 403);
        DB::transaction(function () use ($project, $taskId) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $task = Task::onlyTrashed()->lockForUpdate()->findOrFail($taskId);
            abort_unless($task->project_id === $project->id, 404);
            abort_unless($project->plan_locked_at && in_array($project->status, ['Plan Locked', 'Ongoing'], true), 403);

            $dependencies = array_map('intval', $task->dependencies ?? []);
            if ($project->tasks()->whereIn('id', $dependencies)->count() !== count($dependencies)) {
                throw ValidationException::withMessages(['task' => 'Restore this task’s prerequisites before restoring the task.']);
            }

            $before = $task->toArray();
            $task->restore();
            $project->touch();
            ChapterService::audit('task_restored', $task, $before);
            ChapterService::notify(array_merge($task->assignees ?? [], [$project->chair_id]), 'Task restored: '.$task->title,
                'The task is active again in '.$project->title.'.', route('projects.show', $project, false).'#task-'.$task->id);
        });

        return back()->with('success', 'Task restored to the project.');
    }

    public function taskComplete(Request $request, Project $project, Task $task)
    {
        $data = $request->validate(['evidence' => 'required|string|max:10000']);
        if (!trim($data['evidence'])) throw ValidationException::withMessages(['evidence' => 'Describe the completed work.']);
        DB::transaction(function () use ($request, $project, $task, $data) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $task = Task::lockForUpdate()->findOrFail($task->id);
            abort_unless($task->project_id === $project->id, 404);
            $memberId = $request->user()->id;
            abort_unless($request->user()->status === 'active' && $project->status === 'Ongoing' && $project->plan_locked_at
                && $task->status !== 'Completed' && in_array($memberId, $task->assignees ?? [], true)
                && ($task->assignment_responses[$memberId]['response'] ?? null) === 'accepted', 403);
            $dependencyIds = $task->dependencies ?? [];
            if ($project->tasks()->whereIn('id', $dependencyIds)->where('status', 'Completed')->count() !== count($dependencyIds)) {
                throw ValidationException::withMessages(['evidence' => 'Complete prerequisite tasks first.']);
            }
            $before = $task->toArray();
            $task->update(['status' => 'Completed', 'evidence' => trim($data['evidence'])]);
            $project->touch();
            ChapterService::audit('task_completed', $task, $before);
            ChapterService::notify(array_merge($task->assignees ?? [], [$project->chair_id]), 'Task completed: '.$task->title, $request->user()->name.' completed this task.', route('projects.show', $project, false).'#task-'.$task->id);
        });
        return back()->with('success', 'Task marked complete.');
    }

    public function taskRespond(Request $request, Project $project, Task $task)
    {
        $data = $request->validate(['response' => 'required|in:accepted,declined', 'note' => 'required_if:response,declined|nullable|string|max:2000']);
        DB::transaction(function () use ($request, $project, $task, $data) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $task = Task::lockForUpdate()->findOrFail($task->id);
            abort_unless($task->project_id === $project->id, 404);
            abort_unless($request->user()->status === 'active' && $project->plan_locked_at && in_array($project->status, ['Plan Locked', 'Ongoing']) && in_array($request->user()->id, $task->assignees ?? [], true) && $task->status !== 'Completed', 403);
            $before = $task->toArray();
            $responses = $task->assignment_responses ?? [];
            $responses[$request->user()->id] = $data + ['at' => now()->toIso8601String()];
            $task->update(['assignment_responses' => $responses]);
            $project->touch();
            ChapterService::audit('task_assignment_'.$data['response'], $task, $before, $data['note'] ?? null);
            ChapterService::notify([$project->chair_id], 'Task assignment '.$data['response'], $request->user()->name.': '.$task->title, route('projects.show', $project, false).'#task-'.$task->id);
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
        $start = $gridStart->toDateString();
        $end = $gridEnd->toDateString();
        $visibleProjects = ChapterService::visibleProjects($request->user());
        $projectIds = (clone $visibleProjects)->pluck('id');
        $projects = $visibleProjects->where(fn ($q) => $q->whereBetween('starts_on', [$start, $end])->orWhereBetween('ends_on', [$start, $end]))->get();
        $events = CalendarEvent::with('project')
            ->where(fn ($q) => $q->whereIn('project_id', $projectIds)->orWhereNull('project_id'))
            ->where('starts_on', '<=', $end)
            ->where(fn ($q) => $q->where('ends_on', '>=', $start)
                ->orWhere(fn ($q) => $q->whereNull('ends_on')->where('starts_on', '>=', $start)))
            ->get();
        $tasks = Task::with('project')->whereIn('project_id', $projectIds)->whereBetween('deadline', [$start, $end])->get();
        $dues = MemberDue::with('payments')->where('member_id', $request->user()->id)->whereBetween('due_date', [$start, $end])->get();
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
        $data = $request->validate(['title' => 'required|string|max:200', 'category' => 'required|string|max:100', 'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,pptx,csv,jpg,jpeg,png,txt,mp4|max:10240']);
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
        abort_unless($user->status === 'active' && !$project->archived_at && ($user->role === 'admin' || $project->created_by === $user->id || $project->isChair($user)), 403);
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
        abort_unless(!$project->archived_at && $project->isChair($request->user()) && in_array($project->status, ['Approved', 'Plan Locked', 'Ongoing', 'Completion Review', 'Completed'], true), 403);

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

        if (in_array($document->extension(), ['xls', 'xlsx', 'pptx'], true)) {
            return redirect()->route('documents.original-preview', $document);
        }

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
        abort_unless(in_array($document->extension(), ['docx', 'xls', 'xlsx', 'pptx'], true), 415);
        abort_unless($document->path && Storage::disk('local')->exists($document->path), 404);

        $view = $document->extension() === 'docx' ? 'chapter.document-original-preview' : 'chapter.document-office-preview';
        return response()->view($view, compact('document'))
            ->header('Content-Security-Policy', "default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data: blob:; connect-src 'self'; worker-src 'self' blob:; frame-ancestors 'self'; base-uri 'none'; object-src 'none'; form-action 'none'");
    }

    public function documents(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:200', 'type' => 'nullable|in:all,evidence,attendance,evaluation,documents', 'from' => 'nullable|date', 'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : '')]);

        $documents = ProjectDocument::query()
            ->with('project')
            ->whereHas('project', fn ($query) => $query->when($request->user()->role === 'member', fn ($query) => $query->where(fn ($query) => $query
                ->whereIn('status', Project::APPROVED)
                ->orWhere('created_by', $request->user()->id)
                ->orWhere('chair_id', $request->user()->id))))
            ->when($request->query('type') === 'evidence', fn ($query) => $query->whereIn('submission_stage', ['attendance', 'evaluation']))
            ->when(in_array($request->query('type'), ['attendance', 'evaluation'], true), fn ($query) => $query->where('submission_stage', $request->query('type')))
            ->when($request->query('type') === 'documents', fn ($query) => $query->where(fn ($query) => $query->whereNull('submission_stage')->orWhereNotIn('submission_stage', ['attendance', 'evaluation'])))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->query('to')))
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
        abort_unless(in_array($filter, [null, 'unread', 'important'], true), 404);
        $query = Notification::where('user_id', $request->user()->id);
        $unreadCount = (clone $query)->whereNull('read_at')->count();
        $importantCount = (clone $query)->where('requires_open', true)->whereNull('read_at')->count();
        $routineUnreadCount = $unreadCount - $importantCount;
        $items = $query->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($filter === 'important', fn ($q) => $q->where('requires_open', true))
            ->orderByRaw('CASE WHEN requires_open = 1 AND read_at IS NULL THEN 0 ELSE 1 END')
            ->latest()->paginate(20)->withQueryString();
        return view('chapter.notifications', compact('items', 'unreadCount', 'importantCount', 'routineUnreadCount', 'filter'));
    }

    public function readNotifications(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', $count.' '.($count === 1 ? 'notification' : 'notifications').' marked as read.');
    }

    public function openNotification(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        if (!$notification->read_at) $notification->update(['read_at' => now()]);
        return $notification->hasDestination()
            ? redirect()->to($notification->url)
            : redirect()->route('notifications');
    }

    public function deleteNotification(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->delete();

        return back()->with('success', 'Notification removed.');
    }

    public function audit(Request $request)
    {
        abort_unless(Permissions::allows($request->user(), Permissions::AUDIT_VIEW), 403);
        $filters = $request->validate(['action' => 'nullable|string|max:100', 'actor' => 'nullable|integer|exists:users,id', 'q' => 'nullable|string|max:100', 'from' => 'nullable|date', 'to' => 'nullable|date'.($request->filled('from') ? '|after_or_equal:from' : '')]);
        $action = $filters['action'] ?? null;
        $search = trim($filters['q'] ?? '');
        $actor = $filters['actor'] ?? null;
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');
        $actors = User::whereIn('id', AuditLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))->orderBy('name')->get(['id', 'name']);
        $records = AuditLog::with('actor')->when($action, fn ($q) => $q->where('action', $action))
            ->when($actor, fn ($q) => $q->where('user_id', $actor))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('to')))
            ->when($search !== '', fn ($q) => $q->where(fn ($match) => $match->where('action', 'like', '%'.$search.'%')->orWhere('object_type', 'like', '%'.$search.'%')->orWhere('remarks', 'like', '%'.$search.'%')->orWhereHas('actor', fn ($actor) => $actor->where('name', 'like', '%'.$search.'%'))))
            ->latest()->paginate(25)->withQueryString();
        return view('chapter.activity-log', compact('records', 'actions', 'actors', 'action', 'actor', 'search'));
    }

}

