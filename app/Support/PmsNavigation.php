<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;

final class PmsNavigation
{
    public static function for(User $user): array
    {
        $canReviewProposals = in_array($user->role, ['admin', 'treasurer', 'bod'], true);
        $canManageMembers = Permissions::allows($user, Permissions::MEMBERS_MANAGE);
        $canManageSystem = Permissions::allows($user, Permissions::ACCOUNTS_MANAGE);
        $canManageFinance = Permissions::allows($user, Permissions::FINANCE_MANAGE);

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
                ['expense-claims.index', 'My Expense Claims', 'receipt', $canManageFinance ? ['status' => 'all', 'mine' => 1] : []],
                ['dues', 'My Member Dues', 'receipt', []],
                ...($canManageFinance ? [
                    ['expense-claims.index', 'Claims to Review', 'receipt', ['status' => 'pending']],
                    ['dues.manage', 'Member Dues', 'receipt', []],
                    ['ledger', 'Treasurer Ledger', 'wallet', []],
                ] : []),
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
                ...($canManageFinance ? [['records', 'Liquidation Report Review', 'checklist', ['kind' => 'reports', 'type' => 'Liquidation', 'status' => 'Submitted']]] : []),
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

    /** @return array<string, true> */
    public static function unreadGroups(iterable $notifications, ?User $user = null): array
    {
        $groups = [];

        foreach ($notifications as $notification) {
            if (! $notification instanceof Notification || $notification->read_at !== null || ! $notification->hasDestination()) {
                continue;
            }

            $path = parse_url($notification->url, PHP_URL_PATH);
            if (! is_string($path)) continue;

            if ($user && ($review = self::reviewItem($notification, $user))) $groups[$review[0]] = true;

            $parts = explode('/', trim($path, '/'));
            if (($parts[0] ?? null) === 'workspaces') $parts = array_slice($parts, 2);

            // Task notices open the task inside its project, but belong to the Tasks menu.
            $group = ($parts[0] ?? '') === 'projects'
                && preg_match('/^Task (updated|removed|restored|completed|assignment)\b/i', (string) $notification->title)
                ? 'TASKS' : match ($parts[0] ?? '') {
                'projects' => 'PROJECTS',
                'tasks' => 'TASKS',
                'finance', 'ledger', 'dues', 'expense-claims' => 'FINANCE',
                'members', 'registrations' => 'MEMBERS',
                'documents', 'reports' => 'REPORTS & DOCUMENTS',
                'records' => ($parts[1] ?? '') === 'letters' ? 'PARTNERSHIPS' : 'REPORTS & DOCUMENTS',
                'partner-directory', 'partnership-agreements' => 'PARTNERSHIPS',
                'calendar' => 'CALENDAR',
                'settings', 'activity-log' => 'SETTINGS',
                'admin' => match ($parts[1] ?? '') {
                    'accounts' => 'MEMBERS',
                    'settings' => 'SETTINGS',
                    default => null,
                },
                default => null,
            };

            // Other modules receive a dot only for an actionable review queue.
            if (in_array($group, ['PROJECTS', 'TASKS'], true) || ($group === 'FINANCE' && ($parts[0] ?? '') === 'dues')) {
                $groups[$group] = true;
            }
        }

        return $groups;
    }

    /** @return array<string, array<string, true>> */
    public static function unreadItems(iterable $notifications, User $user): array
    {
        $items = [];

        foreach ($notifications as $notification) {
            if (! $notification instanceof Notification || $notification->read_at !== null || ! $notification->hasDestination()) {
                continue;
            }

            if ($review = self::reviewItem($notification, $user)) {
                $items[$review[0]][$review[1]] = true;
                continue;
            }

            $group = array_key_first(self::unreadGroups([$notification], $user));
            if ($group === null || self::direct($group)) continue;

            $path = parse_url($notification->url, PHP_URL_PATH);
            if (! is_string($path)) continue;
            $parts = explode('/', trim($path, '/'));
            if (($parts[0] ?? null) === 'workspaces') $parts = array_slice($parts, 2);

            $label = match ($group) {
                'PROJECTS' => in_array($user->role, ['admin', 'treasurer', 'bod'], true)
                    && preg_match('/^Project status: (Admin Review|Treasurer Review|BOD Agenda|Submitted for Formal Approval|Completion Review)$/i', (string) $notification->body)
                    ? 'Project Reviews' : ($user->role === 'member' ? 'My Projects' : 'All Projects'),
                'TASKS' => $user->role === 'member' ? 'My Tasks' : 'Task Monitoring',
                'FINANCE' => match ($parts[0] ?? '') {
                    'dues' => ($parts[1] ?? '') === 'manage' ? 'Member Dues' : 'My Member Dues',
                    'ledger' => 'Treasurer Ledger',
                    'expense-claims' => Permissions::allows($user, Permissions::FINANCE_MANAGE)
                        && preg_match('/^Expense claim (needs review|resubmitted)$/i', (string) $notification->title)
                        ? 'Claims to Review' : 'My Expense Claims',
                    default => 'Project Finances',
                },
                'MEMBERS' => ($parts[0] ?? '') === 'registrations' ? 'Registration Requests'
                    : (Permissions::allows($user, Permissions::MEMBERS_MANAGE) ? 'Manage Member Records' : 'Chapter Directory'),
                'REPORTS & DOCUMENTS' => match ($parts[0] ?? '') {
                    'documents' => ($parts[1] ?? '') === 'templates' ? 'Forms & Templates' : 'File Library',
                    'reports' => 'Budget Summary',
                    default => $user->role === 'treasurer'
                        && str_starts_with((string) $notification->title, 'Liquidation submitted:')
                        ? 'Liquidation Report Review' : 'Project Reports',
                },
                'PARTNERSHIPS' => match ($parts[0] ?? '') {
                    'partner-directory' => 'Partner Directory',
                    'partnership-agreements' => 'Partnership Agreements',
                    default => $user->role === 'admin' ? 'External LOI Reviews' : 'External LOIs',
                },
                'SETTINGS' => match ($parts[0] ?? '') {
                    'activity-log' => 'Activity Log',
                    'admin' => 'System Controls',
                    default => 'Workspace Settings',
                },
                default => null,
            };

            if ($label !== null) $items[$group][$label] = true;
        }

        return $items;
    }

    /** @return array{string, string}|null */
    public static function reviewItem(Notification $notification, User $user): ?array
    {
        $path = parse_url($notification->url, PHP_URL_PATH);
        if (! is_string($path)) return null;
        $parts = explode('/', trim($path, '/'));
        if (($parts[0] ?? null) === 'workspaces') $parts = array_slice($parts, 2);

        $role = $user->role;
        $title = (string) $notification->title;
        $body = (string) $notification->body;

        if (($parts[0] ?? '') === 'projects' && isset($parts[1]) && ctype_digit($parts[1])) {
            if ($role === 'admin' && $body === 'Cancellation appeal needs review') return ['PROJECTS', 'Project Reviews'];
            if (preg_match('/^Project status: (.+)$/', $body, $match)) {
                $status = $match[1];
                if (($role === 'admin' && in_array($status, ['In review', 'Admin routing', 'Board review', 'Final report review', 'Admin Review', 'BOD Agenda', 'Completion Review'], true))
                    || ($role === 'treasurer' && in_array($status, ['Budget review', 'Treasurer Review'], true))
                    || ($role === 'bod' && $status === 'Board review')) {
                    return ['PROJECTS', 'Project Reviews'];
                }
            }
        }

        if ($role === 'treasurer' && ($parts[0] ?? '') === 'expense-claims'
            && in_array($title, ['Expense claim needs review', 'Expense claim resubmitted'], true)) return ['FINANCE', 'Claims to Review'];
        if (($parts[0] ?? '') === 'records' && isset($parts[2]) && ctype_digit($parts[2])) {
            if ($role === 'admin' && ($parts[1] ?? '') === 'reports' && $title === 'Report submitted for Admin review') return ['REPORTS & DOCUMENTS', 'Completion Report Review'];
            if ($role === 'treasurer' && ($parts[1] ?? '') === 'reports' && str_starts_with($title, 'Liquidation submitted:')) return ['REPORTS & DOCUMENTS', 'Liquidation Report Review'];
            if ($role === 'admin' && ($parts[1] ?? '') === 'letters' && $title === 'JCI LOI for review') return ['PARTNERSHIPS', 'External LOI Reviews'];
        }
        if ($role === 'admin' && ($parts[0] ?? '') === 'partnership-agreements' && isset($parts[1]) && ctype_digit($parts[1])
            && $title === 'Partnership agreement for review') return ['PARTNERSHIPS', 'Partnership Agreements'];
        if ($role === 'admin' && ($parts[0] ?? '') === 'registrations' && isset($parts[1]) && ctype_digit($parts[1])
            && $title === 'New member registration') return ['MEMBERS', 'Registration Requests'];

        return null;
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
