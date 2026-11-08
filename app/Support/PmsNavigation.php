<?php

namespace App\Support;

use App\Models\User;

final class PmsNavigation
{
    public static function for(User $user): array
    {
        $canReviewProposals = in_array($user->role, ['admin', 'treasurer', 'bod'], true);
        $canManageMembers = Permissions::allows($user, Permissions::MEMBERS_MANAGE);
        $canManageSystem = Permissions::allows($user, Permissions::ACCOUNTS_MANAGE);

        $menus = [
            'PROJECTS' => [
                ['projects', 'My Projects', 'user', ['filter' => 'mine']],
                ['projects', 'All Projects', 'briefcase', []],
                ['projects.create', 'Create Project', 'filePlus', []],
                ...($canReviewProposals ? [['projects', 'Project Reviews', 'checklist', ['filter' => 'review']]] : []),
            ],
            'TASKS' => [
                ['tasks', 'My Tasks', 'checklist', ['mine' => 1]],
                ['tasks', 'Task Monitoring', 'users', ['mine' => 0]],
            ],
            'FINANCE' => [
                ['finance', 'Project Finances', 'chart', []],
                ['expense-claims.index', $user->role === 'treasurer' ? 'Claims to Review' : 'My Expense Claims', 'receipt', []],
                [$user->role === 'treasurer' ? 'dues.manage' : 'dues', $user->role === 'treasurer' ? 'Member Dues' : 'My Dues', 'receipt', []],
                ...($user->role === 'treasurer' ? [['ledger', 'Treasurer Ledger', 'wallet', []]] : []),
            ],
            'MEMBERS' => [
                ...($canManageMembers ? [
                    ['members.manage', 'Manage Member Records', 'users', []],
                    ['registrations', 'Registration Requests', 'userPlus', []],
                ] : [['members', 'Chapter Directory', 'users', []]]),
            ],
            'REPORTS & DOCUMENTS' => [
                ['records', 'Project Reports', 'report', ['kind' => 'reports']],
                ['reports.generate', 'Budget Summary', 'chart', []],
                ['documents', 'File Library', 'folder', []],
                ['templates', 'Forms & Templates', 'folder', []],
                ...($user->role === 'admin' ? [['records', 'Completion Report Review', 'checklist', ['kind' => 'reports', 'type' => 'Completion', 'status' => 'Submitted']]] : []),
                ...($user->role === 'treasurer' ? [['records', 'Liquidation Report Review', 'checklist', ['kind' => 'reports', 'type' => 'Liquidation', 'status' => 'Submitted']]] : []),
            ],
            'PARTNERSHIPS' => [
                ['partners.index', 'Partner Directory', 'users', []],
                ['records', $user->role === 'admin' ? 'External LOI Reviews' : 'External LOIs', 'fileSignature', $user->role === 'admin' ? ['kind' => 'letters', 'status' => 'For Review'] : ['kind' => 'letters']],
                ['agreements.index', 'Partnership Agreements', 'fileSignature', []],
            ],
            'CALENDAR' => [
                ['calendar', 'Calendar', 'calendar', []],
            ],
            'NOTIFICATIONS' => [
                ['notifications', 'Notifications', 'bell', []],
            ],
            'SETTINGS' => [
                ['settings', 'Workspace Settings', 'settings', []],
                ...($canManageSystem ? [['pms.settings', 'System Controls', 'shieldCheck', []]] : []),
                ...(Permissions::allows($user, Permissions::AUDIT_VIEW) ? [['activity-log', 'Activity Log', 'clock', []]] : []),
            ],
        ];

        return $menus;
    }

    public static function label(string $menu): string
    {
        return match ($menu) {
            'REPORTS & DOCUMENTS' => 'Reports & Documents',
            default => ucfirst(strtolower($menu)),
        };
    }

    public static function icon(string $menu): string
    {
        return match ($menu) {
            'PROJECTS' => 'briefcase',
            'TASKS' => 'checklist',
            'FINANCE' => 'wallet',
            'MEMBERS' => 'users',
            'REPORTS & DOCUMENTS' => 'report',
            'CALENDAR' => 'calendar',
            'NOTIFICATIONS' => 'bell',
            'PARTNERSHIPS' => 'users',
            'SETTINGS' => 'settings',
            default => 'folder',
        };
    }

    public static function direct(string $menu): bool
    {
        return in_array($menu, ['CALENDAR', 'NOTIFICATIONS'], true);
    }

    public static function url(array $item): string
    {
        [$route, , , $params] = $item;
        abort_unless($route, 500);
        $fragment = $params['section'] ?? null;

        return route($route, $params).($fragment ? '#'.$fragment : '');
    }

    public static function active(array $item): bool
    {
        [$route, , , $params] = $item;
        if (!$route) return false;
        // Keep project folders and individual project pages under All Projects.
        if ($route === 'projects' && !$params && request()->routeIs('projects.*')
            && !request()->routeIs('projects.create')) return true;
        if ($route === 'tasks' && request()->routeIs('tasks') && request()->query('mine') === null) {
            // The task page defaults to personal work for members and monitoring for other roles.
            return (int) ($params['mine'] ?? -1) === (request()->user()?->role === 'member' ? 1 : 0);
        }
        if ($route === 'partners.index' && request()->routeIs('partners.*')) return true;
        if ($route === 'agreements.index' && request()->routeIs('agreements.*')) return true;
        if (!request()->routeIs($route)) return false;
        foreach ($params as $key => $value) {
            if ((string) (request()->route($key) ?? request()->query($key)) !== (string) $value) return false;
        }
        foreach (['filter', 'mine', 'kind', 'type', 'status'] as $key) {
            if ((request()->route($key) !== null || request()->filled($key)) && !array_key_exists($key, $params)) return false;
        }

        return true;
    }
}
