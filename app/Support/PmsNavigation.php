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
                ['projects', 'My Project Work', 'user', ['filter' => 'mine']],
                ['projects.create', 'Submit New Project', 'filePlus', []],
                ['projects', 'All Chapter Projects', 'briefcase', []],
                ...($canReviewProposals ? [['projects', 'Review Queue', 'checklist', ['filter' => 'review']]] : []),
            ],
            'TASKS' => [
                ['tasks', 'My Assigned Tasks', 'checklist', ['mine' => 1]],
                ['tasks', 'Chapter Task Monitor', 'users', ['mine' => 0]],
            ],
            'FINANCIAL' => [
                ['finance', 'Project Budget Overview', 'chart', []],
                ['expense-claims.index', $user->role === 'treasurer' ? 'Expense Claims to Review' : 'My Expense Claims', 'receipt', []],
                [$user->role === 'treasurer' ? 'dues.manage' : 'dues', $user->role === 'treasurer' ? 'Manage Member Dues' : 'My Member Dues', 'receipt', []],
                ...($user->role === 'treasurer' ? [['ledger', 'Master Ledger', 'wallet', []]] : []),
            ],
            'MEMBERS' => [
                ...($canManageMembers ? [
                    ['members.manage', 'Manage Member Records', 'users', []],
                    ['registrations', 'Registration Requests', 'userPlus', []],
                ] : [['members', 'Chapter Directory', 'users', []]]),
            ],
            'REPORTS & DOCUMENTS' => [
                ['records', 'Reports', 'report', ['kind' => 'reports']],
                ['reports.generate', 'Budget Summary', 'chart', []],
                ['templates', 'Forms & Templates', 'folder', []],
                ...($user->role === 'admin' ? [['records', 'PCR Reviews', 'checklist', ['kind' => 'reports', 'type' => 'Completion', 'status' => 'Submitted']]] : []),
                ...($user->role === 'treasurer' ? [['records', 'Liquidation Reviews', 'checklist', ['kind' => 'reports', 'type' => 'Liquidation', 'status' => 'Submitted']]] : []),
            ],
            'FILES' => [
                ['documents', 'File Library', 'folder', []],
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
            'FINANCIAL' => 'wallet',
            'MEMBERS' => 'users',
            'REPORTS & DOCUMENTS' => 'report',
            'FILES' => 'folder',
            'CALENDAR' => 'calendar',
            'NOTIFICATIONS' => 'bell',
            'PARTNERSHIPS' => 'users',
            'SETTINGS' => 'settings',
            default => 'folder',
        };
    }

    public static function direct(string $menu): bool
    {
        return in_array($menu, ['CALENDAR', 'FILES', 'NOTIFICATIONS'], true);
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
        // Keep project detail, edit, and document pages under the project collection.
        if ($route === 'projects' && !$params && request()->routeIs('projects.*') && !request()->routeIs('projects.create')) return true;
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
