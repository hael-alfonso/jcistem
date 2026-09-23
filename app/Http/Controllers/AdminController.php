<?php

namespace App\Http\Controllers;

use App\Support\JciDemoData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $projects = JciDemoData::projects();
        $reports = JciDemoData::reports();
        $open = count(array_filter($projects, fn ($p) => in_array($p['status'], ['Ongoing', 'Approved — Chair Pending', 'Project Setup', 'Completion Review'], true)));
        $pending = count(array_filter($projects, fn ($p) => str_contains($p['status'], 'Review') || $p['status'] === 'Submitted / Pending Review'));
        $approved = array_sum(array_column($projects, 'approvedBudget'));
        $used = array_sum(array_column($projects, 'usedFunds'));
        $avg = (int) round(array_sum(array_column($projects, 'progress')) / max(1, count($projects)));

        $statusLabels = ['Ongoing', 'Approved — Chair Pending', 'Pending Review', 'Completed'];
        $statusColors = ['#0097D7', '#1F4789', '#EFC40F', '#57BCBC'];
        $statusValues = array_map(fn ($s) => JciDemoData::countBy($projects, 'status', $s), $statusLabels);

        return $this->page('admin.dashboard', 'Dashboard', [
            'open' => $open,
            'pending' => $pending,
            'approved' => $approved,
            'remaining' => max(0, $approved - $used),
            'avg' => $avg,
            'projects' => $projects,
            'statusChart' => [
                'labels' => $statusLabels,
                'values' => $statusValues,
                'colors' => $statusColors,
            ],
            'progressChart' => [
                'labels' => array_column($projects, 'title'),
                'values' => array_column($projects, 'progress'),
            ],
            'budgetChart' => [
                'labels' => array_column(array_filter($projects, fn ($p) => $p['approvedBudget'] > 0), 'title'),
                'used' => array_column(array_filter($projects, fn ($p) => $p['approvedBudget'] > 0), 'usedFunds'),
                'remaining' => array_map(fn ($p) => max(0, $p['approvedBudget'] - $p['usedFunds']), array_values(array_filter($projects, fn ($p) => $p['approvedBudget'] > 0))),
            ],
            'submittedReports' => JciDemoData::countBy($reports, 'status', 'Submitted to Admin'),
        ]);
    }

    public function projects(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $projects = collect(JciDemoData::projects())->filter(function ($p) use ($filter) {
            return match ($filter) {
                'my' => $p['createdBy'] === JciDemoData::config()['currentUser'],
                'pending' => str_contains($p['status'], 'Review') || $p['status'] === 'Submitted / Pending Review',
                'approved' => str_contains($p['status'], 'Approved') || $p['status'] === 'Project Setup',
                'ongoing' => $p['status'] === 'Ongoing',
                'completed' => $p['status'] === 'Completed',
                'archived' => $p['status'] === 'Archived',
                default => true,
            };
        })->values()->all();

        $titles = [
            'all' => 'All Projects',
            'my' => 'My Projects',
            'pending' => 'Pending Review',
            'approved' => 'Approved Projects',
            'ongoing' => 'Ongoing Projects',
            'completed' => 'Completed Projects',
            'archived' => 'Archived Projects',
        ];

        return $this->page('admin.projects.index', $titles[$filter] ?? 'Projects', [
            'projects' => $projects,
            'filter' => $filter,
        ]);
    }

    public function createProject(): View
    {
        return $this->page('admin.projects.create', 'Create Project', [
            'areas' => JciDemoData::config()['areas'],
        ]);
    }

    public function showProject(int $id): View
    {
        $project = JciDemoData::project($id);
        abort_unless($project, 404);

        return $this->page('admin.projects.show', 'Project Details', [
            'project' => $project,
            'tasks' => JciDemoData::whereProject(JciDemoData::tasks(), $id),
            'expenses' => JciDemoData::whereProject(JciDemoData::expenses(), $id),
            'docs' => JciDemoData::whereProject(JciDemoData::documents(), $id),
            'lois' => JciDemoData::whereProject(JciDemoData::lois(), $id),
            'reports' => JciDemoData::whereProject(JciDemoData::reports(), $id),
        ]);
    }

    public function tasks(): View
    {
        $tasks = JciDemoData::tasks();
        $labels = ['Completed', 'In Progress', 'Pending', 'Overdue'];
        $colors = ['#57BCBC', '#0097D7', '#1F4789', '#D66A5F'];

        return $this->page('admin.tasks', 'Tasks & Milestones', [
            'tasks' => $tasks,
            'statusChart' => [
                'labels' => $labels,
                'values' => array_map(fn ($s) => JciDemoData::countBy($tasks, 'status', $s), $labels),
                'colors' => $colors,
            ],
        ]);
    }

    public function loi(): View
    {
        return $this->page('admin.loi', 'Letters of Intent', [
            'lois' => JciDemoData::lois(),
        ]);
    }

    public function calendar(): View
    {
        return $this->page('admin.calendar', 'Calendar', [
            'events' => collect(JciDemoData::events())->sortBy('date')->values()->all(),
            'cells' => JciDemoData::calendarCells(),
        ]);
    }

    public function reports(): View
    {
        $reports = JciDemoData::reports();
        $labels = ['Submitted to Admin', 'Reviewed', 'Draft', 'Returned'];
        $colors = ['#0097D7', '#57BCBC', '#1F4789', '#EFC40F'];

        return $this->page('admin.reports', 'Project Reports', [
            'reports' => $reports,
            'statusChart' => [
                'labels' => $labels,
                'values' => array_map(fn ($s) => JciDemoData::countBy($reports, 'status', $s), $labels),
                'colors' => $colors,
            ],
        ]);
    }

    public function finance(): View
    {
        $projects = JciDemoData::projects();
        $approved = array_sum(array_column($projects, 'approvedBudget'));
        $used = array_sum(array_column($projects, 'usedFunds'));

        return $this->page('admin.finance.index', 'Financial Monitoring', [
            'approved' => $approved,
            'used' => $used,
            'remaining' => max(0, $approved - $used),
            'expenseCount' => count(JciDemoData::expenses()),
            'budgetChart' => [
                'labels' => array_column(array_filter($projects, fn ($p) => $p['approvedBudget'] > 0), 'title'),
                'used' => array_column(array_filter($projects, fn ($p) => $p['approvedBudget'] > 0), 'usedFunds'),
                'remaining' => array_map(fn ($p) => max(0, $p['approvedBudget'] - $p['usedFunds']), array_values(array_filter($projects, fn ($p) => $p['approvedBudget'] > 0))),
            ],
        ]);
    }

    public function financeBudget(): View
    {
        return $this->page('admin.finance.budget', 'Budget Allocation', [
            'allocations' => JciDemoData::allocations(),
            'chart' => [
                'labels' => array_map(fn ($a) => JciDemoData::project($a['project'])['title'].' · '.$a['category'], JciDemoData::allocations()),
                'allocated' => array_column(JciDemoData::allocations(), 'allocated'),
                'used' => array_column(JciDemoData::allocations(), 'used'),
            ],
        ]);
    }

    public function financeUtilization(): View
    {
        $projects = JciDemoData::projects();

        return $this->page('admin.finance.utilization', 'Fund Utilization', [
            'projects' => $projects,
            'chart' => [
                'labels' => array_column($projects, 'title'),
                'used' => array_column($projects, 'usedFunds'),
                'remaining' => array_map(fn ($p) => max(0, $p['approvedBudget'] - $p['usedFunds']), $projects),
            ],
        ]);
    }

    public function financeExpenses(): View
    {
        $expenses = JciDemoData::expenses();
        $labels = array_values(array_unique(array_column($expenses, 'category')));

        return $this->page('admin.finance.expenses', 'Expense Monitoring', [
            'expenses' => $expenses,
            'chart' => [
                'labels' => $labels,
                'values' => array_map(fn ($c) => array_sum(array_column(array_filter($expenses, fn ($e) => $e['category'] === $c), 'amount')), $labels),
            ],
        ]);
    }

    public function financeReports(): View
    {
        $rows = array_values(array_filter(JciDemoData::reports(), fn ($r) => str_contains($r['type'], 'Financial')));

        return $this->page('admin.finance.reports', 'Financial Reports', [
            'reports' => $rows,
        ]);
    }

    public function members(): View
    {
        $users = JciDemoData::users();
        $roles = array_values(array_unique(array_column($users, 'role')));

        return $this->page('admin.members.index', 'Members & Accounts', [
            'users' => $users,
            'chart' => [
                'labels' => $roles,
                'values' => array_map(fn ($r) => JciDemoData::countBy($users, 'role', $r), $roles),
            ],
        ]);
    }

    public function memberRegistration(): View
    {
        return $this->page('admin.members.registration', 'Member Registration');
    }

    public function dues(): View
    {
        $user = auth()->user()->name;
        $mine = array_values(array_filter(JciDemoData::dues(), fn ($d) => $d['member'] === $user));

        return $this->page('admin.dues', 'My Member Dues', [
            'current' => $mine[0] ?? null,
            'history' => $mine,
        ]);
    }

    public function notifications(): View
    {
        return $this->page('admin.notifications', 'Notifications', [
            'items' => JciDemoData::notifications(),
        ]);
    }

    public function account(): View
    {
        $user = auth()->user()->name;

        return $this->page('admin.account', 'My Account', [
            'activity' => array_values(array_filter(JciDemoData::audit(), fn ($a) => $a['actor'] === $user)),
        ]);
    }

    public function audit(): View
    {
        return $this->page('admin.audit', 'Audit Log', [
            'rows' => JciDemoData::audit(),
        ]);
    }

    private function page(string $view, string $title, array $data = []): View
    {
        $unread = count(array_filter(JciDemoData::notifications(), fn ($n) => ! $n['read']));

        return view($view, array_merge($data, [
            'pageTitle' => $title,
            'currentUser' => auth()->user()?->name ?? JciDemoData::config()['currentUser'],
            'unread' => $unread,
        ]));
    }
}
