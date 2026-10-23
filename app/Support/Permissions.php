<?php

namespace App\Support;

use App\Models\User;

final class Permissions
{
    public const AUDIT_VIEW = 'audit.view';
    public const MEMBERS_MANAGE = 'members.manage';
    public const FINANCE_MANAGE = 'finance.manage';
    public const CONCEPT_REVIEW = 'projects.review-concept';
    public const PROPOSAL_REVIEW = 'projects.review-proposal';
    public const PROJECT_COMPLETE = 'projects.complete';
    public const PROJECT_ARCHIVE = 'projects.archive';
    public const CALENDAR_GLOBAL = 'calendar.global';
    public const RECORDS_REVIEW = 'records.review';
    public const INQUIRIES_MANAGE = 'inquiries.manage';

    private const ROLE_PERMISSIONS = [
        'admin' => [self::AUDIT_VIEW, self::MEMBERS_MANAGE, self::PROJECT_COMPLETE, self::PROJECT_ARCHIVE, self::CALENDAR_GLOBAL, self::RECORDS_REVIEW, self::INQUIRIES_MANAGE],
        'bod' => [],
        'treasurer' => [self::FINANCE_MANAGE],
        'member' => [],
    ];

    public static function allows(User $user, string $permission): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        if ($permission === self::CONCEPT_REVIEW) {
            return in_array($user->role, ['admin', 'bod'], true) && $user->concept_reviewer;
        }

        if ($permission === self::PROPOSAL_REVIEW) {
            return in_array($user->role, ['admin', 'bod'], true) && $user->proposal_reviewer;
        }

        return in_array($permission, self::ROLE_PERMISSIONS[$user->role] ?? [], true);
    }
}
