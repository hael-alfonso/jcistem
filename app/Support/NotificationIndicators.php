<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

final class NotificationIndicators
{
    /** @return array<string, array<int|string, bool|int>> */
    public static function for(User $user): array
    {
        return self::from(self::unreadFor($user), $user);
    }

    /** @return Collection<int, Notification> */
    public static function unreadFor(User $user): Collection
    {
        $key = 'unread_notifications_for_'.$user->id;
        if (request()->attributes->has($key)) return request()->attributes->get($key);

        $notifications = Notification::where('user_id', $user->id)->whereNull('read_at')
            ->get(['id', 'title', 'body', 'url', 'read_at', 'requires_open']);
        request()->attributes->set($key, $notifications);

        return $notifications;
    }

    /** @return array<string, array<int|string, bool|int>> */
    public static function from(iterable $notifications, ?User $user = null): array
    {
        $targets = [
            'projects' => [], 'tasks' => [], 'task_projects' => [], 'dues' => [], 'due_periods' => [],
            'project_notices' => [], 'task_notices' => [], 'task_project_notices' => [], 'due_notices' => [], 'period_notices' => [],
            'review_reports' => [], 'review_letters' => [], 'review_agreements' => [], 'review_registrations' => [], 'review_claims' => [],
        ];

        foreach ($notifications as $notification) {
            if (! $notification instanceof Notification || $notification->read_at || ! $notification->hasDestination()) continue;

            $path = parse_url($notification->url, PHP_URL_PATH);
            $fragment = parse_url($notification->url, PHP_URL_FRAGMENT);
            if (! is_string($path)) continue;

            if (preg_match('~^/(?:workspaces/[^/]+/)?projects/(\d+)$~', $path, $match)) {
                $projectId = (int) $match[1];
                if (preg_match('/^Task (?:updated|removed|restored|completed|assignment)\b/i', (string) $notification->title)) {
                    if (is_string($fragment) && preg_match('/^task-(\d+)$/', $fragment, $task)) {
                        $targets['tasks'][(int) $task[1]] = true;
                        $targets['task_notices'][(int) $task[1]] ??= $notification->id;
                    } else {
                        $targets['task_projects'][$projectId] = true;
                        $targets['task_project_notices'][$projectId] ??= $notification->id;
                    }
                } else {
                    $targets['projects'][$projectId] = true;
                    $targets['project_notices'][$projectId] ??= $notification->id;
                }
            } elseif (preg_match('~^/(?:workspaces/[^/]+/)?dues$~', $path)) {
                if (is_string($fragment) && preg_match('/^due-(\d+)$/', $fragment, $due)) {
                    $targets['dues'][(int) $due[1]] = true;
                    $targets['due_notices'][(int) $due[1]] ??= $notification->id;
                } elseif (preg_match('/\b\d{4}-(?:0[1-9]|1[0-2])\b/', (string) $notification->title.' '.$notification->body, $period)) {
                    // Earlier dues notifications had no record anchor; the period still identifies the member's assessment.
                    $targets['due_periods'][$period[0]] = true;
                    $targets['period_notices'][$period[0]] ??= $notification->id;
                }
            }

            if (! $user || ! PmsNavigation::reviewItem($notification, $user)) continue;
            if (preg_match('~^/(?:workspaces/[^/]+/)?records/(reports|letters)/(\d+)$~', $path, $match)) {
                $targets['review_'.$match[1]][(int) $match[2]] ??= $notification->id;
            } elseif (preg_match('~^/(?:workspaces/[^/]+/)?partnership-agreements/(\d+)$~', $path, $match)) {
                $targets['review_agreements'][(int) $match[1]] ??= $notification->id;
            } elseif (preg_match('~^/(?:workspaces/[^/]+/)?registrations/(\d+)$~', $path, $match)) {
                $targets['review_registrations'][(int) $match[1]] ??= $notification->id;
            } elseif (preg_match('~^/(?:workspaces/[^/]+/)?expense-claims$~', $path)) {
                parse_str((string) parse_url($notification->url, PHP_URL_QUERY), $query);
                if (isset($query['claim']) && ctype_digit((string) $query['claim'])) $targets['review_claims'][(int) $query['claim']] ??= $notification->id;
            }
        }

        return $targets;
    }

    public static function destinationKey(Notification $notification): ?string
    {
        if (! $notification->hasDestination()) return null;
        $path = parse_url($notification->url, PHP_URL_PATH);
        if (! is_string($path)) return null;
        $fragment = parse_url($notification->url, PHP_URL_FRAGMENT);
        if (preg_match('~^/(?:workspaces/[^/]+/)?projects/(\d+)$~', $path, $match)) {
            if (is_string($fragment) && preg_match('/^task-(\d+)$/', $fragment, $task)) return 'task:'.$task[1];
            if ($fragment === 'tasks') return 'task-project:'.$match[1];
            return 'project:'.$match[1].(is_string($fragment) && $fragment !== '' ? '#'.$fragment : '');
        }
        if (preg_match('~^/(?:workspaces/[^/]+/)?dues$~', $path)) {
            if (is_string($fragment) && preg_match('/^due-(\d+)$/', $fragment, $due)) return 'due:'.$due[1];
            parse_str((string) parse_url($notification->url, PHP_URL_QUERY), $query);
            if (isset($query['due']) && ctype_digit((string) $query['due'])) return 'due:'.$query['due'];
            if (preg_match('/\b\d{4}-(?:0[1-9]|1[0-2])\b/', (string) $notification->title.' '.$notification->body, $period)) return 'due-period:'.$period[0];
        }
        foreach (['records/reports' => 'report', 'records/letters' => 'letter', 'partnership-agreements' => 'agreement', 'registrations' => 'registration'] as $route => $kind) {
            if (preg_match('~^/(?:workspaces/[^/]+/)?'.$route.'/(\d+)$~', $path, $match)) return $kind.':'.$match[1];
        }
        if (preg_match('~^/(?:workspaces/[^/]+/)?expense-claims$~', $path)) {
            parse_str((string) parse_url($notification->url, PHP_URL_QUERY), $query);
            if (isset($query['claim']) && ctype_digit((string) $query['claim'])) return 'claim:'.$query['claim'];
        }
        return null;
    }
}
