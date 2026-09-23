<?php

namespace App\Support;

class WorkspaceNav
{
    public static function for(?string $role): array
    {
        return match ($role) {
            'admin' => [
                ['group' => 'Overview', 'items' => [
                    ['admin.dashboard', 'Dashboard', 'dashboard'],
                ]],
                ['group' => 'Projects', 'items' => [
                    ['admin.projects', 'Projects', 'briefcase', ['admin.projects', 'admin.projects.show']],
                    ['admin.projects.create', 'Create Project', 'filePlus'],
                    ['admin.tasks', 'Tasks & Milestones', 'checklist'],
                    ['admin.loi', 'Letters of Intent', 'fileSignature'],
                    ['admin.calendar', 'Calendar', 'calendar'],
                ]],
                ['group' => 'Reports & Finance', 'items' => [
                    ['admin.reports', 'Project Reports', 'report'],
                    ['admin.finance', 'Financial Monitoring', 'chart', ['admin.finance*']],
                ]],
                ['group' => 'Organization', 'items' => [
                    ['admin.members', 'Members & Accounts', 'users'],
                    ['admin.members.registration', 'Member Registration', 'userPlus'],
                    ['admin.dues', 'My Member Dues', 'receipt'],
                    ['admin.notifications', 'Notifications', 'bell'],
                ]],
                ['group' => 'Account', 'items' => [
                    ['admin.account', 'My Account', 'user'],
                    ['admin.audit', 'Audit Log', 'shieldCheck'],
                ]],
            ],
            'treasurer' => [
                ['group' => 'Overview', 'items' => [
                    ['treasurer.dashboard', 'Dashboard', 'dashboard'],
                ]],
                ['group' => 'Recording', 'items' => [
                    ['treasurer.ledger', 'Treasurer Ledger', 'wallet'],
                    ['treasurer.expenses', 'Disbursements', 'receipt'],
                    ['treasurer.liquidation', 'Liquidation', 'checklist'],
                    ['treasurer.dues', 'Member Dues', 'users'],
                ]],
                ['group' => 'Budgets & Reports', 'items' => [
                    ['treasurer.budget', 'Budget Allocation', 'chart'],
                    ['treasurer.utilization', 'Fund Utilization', 'chart'],
                    ['treasurer.report', 'Overall Financial Report', 'report'],
                ]],
                ['group' => 'Account', 'items' => [
                    ['treasurer.notifications', 'Notifications', 'bell'],
                    ['treasurer.account', 'My Account', 'user'],
                ]],
            ],
            'bod' => [
                ['group' => 'Overview', 'items' => [
                    ['bod.dashboard', 'Dashboard', 'dashboard'],
                ]],
                ['group' => 'Governance', 'items' => [
                    ['bod.projects', 'Project Review', 'briefcase'],
                    ['bod.reports', 'Reports', 'report'],
                    ['bod.calendar', 'Calendar', 'calendar'],
                ]],
                ['group' => 'Account', 'items' => [
                    ['bod.notifications', 'Notifications', 'bell'],
                    ['bod.account', 'My Account', 'user'],
                ]],
            ],
            default => [
                ['group' => 'Overview', 'items' => [
                    ['member.dashboard', 'Dashboard', 'dashboard'],
                ]],
                ['group' => 'My Workspace', 'items' => [
                    ['member.projects', 'My Projects', 'briefcase'],
                    ['member.dues', 'My Member Dues', 'receipt'],
                    ['member.calendar', 'Calendar', 'calendar'],
                    ['member.notifications', 'Notifications', 'bell'],
                ]],
                ['group' => 'Account', 'items' => [
                    ['member.account', 'My Account', 'user'],
                ]],
            ],
        };
    }

    public static function home(?string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'treasurer' => 'treasurer.dashboard',
            'bod' => 'bod.dashboard',
            default => 'member.dashboard',
        };
    }

    public static function label(?string $role): string
    {
        return match ($role) {
            'admin' => 'Admin',
            'treasurer' => 'Treasurer',
            'bod' => 'Board of Directors',
            default => 'Member',
        };
    }

    public static function context(?string $role): string
    {
        return match ($role) {
            'admin' => 'Projects • Monitoring • Reports',
            'treasurer' => 'Ledger • Dues • Disbursements',
            'bod' => 'Review • Governance',
            default => 'Projects • Dues • Calendar',
        };
    }

    public static function isActive(array $item): bool
    {
        $patterns = $item[3] ?? [$item[0]];

        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}
