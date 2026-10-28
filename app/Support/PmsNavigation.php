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
                ['reports.generate', 'Report Generator', 'report', []],
                ['templates', 'Forms & Templates', 'folder', []],
                ...($user->role === 'admin' ? [['records', 'Completion Report Reviews', 'checklist', ['kind' => 'reports', 'type' => 'Completion', 'status' => 'Submitted']]] : []),
                ...($user->role === 'treasurer' ? [['records', 'Liquidation Reviews', 'checklist', ['kind' => 'reports', 'type' => 'Liquidation', 'status' => 'Submitted']]] : []),
                ['projects', 'Project Archive', 'folder', ['filter' => 'Archived']],
            ],
            'CALENDAR' => [
                ['calendar', 'Calendar', 'calendar', []],
            ],
            'NOTIFICATIONS' => [
                ['notifications', 'Notifications', 'bell', []],
            ],
            'ACCOUNT' => [
                ['account', 'My Profile', 'user', []],
                ['settings', 'My Settings', 'settings', []],
                ...($canManageSystem ? [
                    ['pms.settings', 'System Settings', 'shieldCheck', []],
                    ['activity-log', 'Activity History', 'clock', []],
                ] : []),
                [null, 'Logout', 'logout', ['action' => 'logout']],
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
            'CALENDAR' => 'calendar',
            'NOTIFICATIONS' => 'bell',
            'ACCOUNT' => 'user',
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
        if (!$route || !request()->routeIs($route)) return false;
        foreach ($params as $key => $value) {
            if ((string) (request()->route($key) ?? request()->query($key)) !== (string) $value) return false;
        }
        foreach (['filter', 'mine', 'kind', 'type', 'status'] as $key) {
            if ((request()->route($key) !== null || request()->filled($key)) && !array_key_exists($key, $params)) return false;
        }

        return true;
    }
}
