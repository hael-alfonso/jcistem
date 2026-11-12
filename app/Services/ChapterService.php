<?php
namespace App\Services;

use App\Models\{AuditLog, Notification, Project, ProjectReview, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\{ChapterForms, Permissions};

class ChapterService
{
    public static function audit(string $action, Model $record, array $before = [], ?string $remarks = null, bool $system = false): void
    {
        AuditLog::create([
            'user_id' => $system ? null : auth()->id(), 'action' => $action,
            'object_type' => class_basename($record), 'object_id' => $record->getKey(),
            'before' => self::redact($before), 'after' => self::redact($record->toArray()), 'remarks' => $remarks,
        ]);
    }

    private static function redact(array $data): array
    {
        $data = array_diff_key($data, array_flip(['password', 'remember_token', 'active_login_token', 'profile', 'document_path', 'evidence_path', 'attachment_path', 'path']));
        foreach ($data as $key => $value) if (is_array($value)) $data[$key] = self::redact($value);
        return $data;
    }

    public static function notify(iterable $users, string $title, string $body, ?string $url, bool $important = false): void
    {
        $url = self::notificationUrl($url);
        $ids = collect($users)
            ->map(static function ($recipient) {
                if ($recipient instanceof User) return $recipient->getKey();
                if (is_object($recipient) && method_exists($recipient, 'getKey')) return $recipient->getKey();
                return $recipient;
            })
            ->filter(static fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(static fn ($id) => (int) $id)
            ->unique()->values();

        if ($ids->isEmpty()) return;

        // Resolve against the users table so deleted/stale recipient ids never
        // make notification creation fail or appear to vanish silently.
        foreach (User::query()->whereKey($ids)->pluck('id') as $id) {
            Notification::create(['user_id' => $id, 'title' => $title, 'body' => $body, 'url' => $url, 'requires_open' => $important]);
        }
    }

    private static function notificationUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') return null;

        $url = trim($url);
        $parsed = parse_url($url);
        if ($parsed === false) return null;

        // Controllers may pass either route(..., false) or an absolute route.
        // Store one safe, app-relative path so the open action works in every
        // workspace and never redirects to an unrelated host.
        $path = $parsed['path'] ?? (str_starts_with($url, '/') ? $url : null);
        if ($path === null || $path === '') return null;
        if (isset($parsed['query'])) $path .= '?'.$parsed['query'];
        if (isset($parsed['fragment'])) $path .= '#'.$parsed['fragment'];

        return preg_replace('#^/workspaces/[a-f0-9-]{36}(?=/|$)#i', '', $path) ?: '/';
    }

    public static function canView(Project $project, User $user): bool
    {
        return in_array($project->status, Project::APPROVED, true)
            || ($project->status === 'Cancelled' && $project->decisions()->where('decision', 'approved')->exists())
            || $project->created_by === $user->id
            || $project->chair_id === $user->id
            || in_array($user->role, ['admin', 'bod', 'treasurer'], true);
    }

    public static function visibleProjects(User $user)
    {
        return Project::query()->when($user->role === 'member', fn ($q) => $q->where(fn ($q) =>
            $q->whereIn('status', Project::APPROVED)
                ->orWhere(fn ($cancelled) => $cancelled->where('status', 'Cancelled')->whereHas('decisions', fn ($decision) => $decision->where('decision', 'approved')))
                ->orWhere('created_by', $user->id)->orWhere('chair_id', $user->id)));
    }

    public static function requireFields(array $data, array $labels, string $prefix): void
    {
        $errors = [];
        foreach ($labels as $key => $label) {
            if (trim((string) ($data[$key] ?? '')) === '') $errors[$prefix.'.'.$key] = $label.' is required before submission.';
        }
        if ($errors) throw ValidationException::withMessages($errors);
    }

    public function transition(Project $project, User $user, string $action, ?string $comments, ?int $chairId = null): Project
    {
        return app(ProposalWorkflowService::class)->transition($project, $user, $action, $comments);
    }
}
