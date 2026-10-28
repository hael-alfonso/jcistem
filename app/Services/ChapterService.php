<?php
namespace App\Services;

use App\Models\{AuditLog, Notification, Project, ProjectReview, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\{ChapterForms, Permissions};

class ChapterService
{
    public static function audit(string $action, Model $record, array $before = [], ?string $remarks = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(), 'action' => $action,
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

    public static function notify(iterable $users, string $title, string $body, string $url): void
    {
        $url = preg_replace('#^/workspaces/[a-f0-9-]{36}(?=/|$)#', '', $url);
        foreach (collect($users)->filter()->unique()->values() as $id) {
            Notification::create(['user_id' => $id, 'title' => $title, 'body' => $body, 'url' => $url]);
        }
    }

    public static function canView(Project $project, User $user): bool
    {
        return in_array($project->status, Project::APPROVED, true)
            || $project->created_by === $user->id
            || in_array($user->role, ['admin', 'bod', 'treasurer'], true);
    }

    public static function visibleProjects(User $user)
    {
        return Project::query()->when($user->role === 'member', fn ($q) => $q->where(fn ($q) =>
            $q->whereIn('status', Project::APPROVED)->orWhere('created_by', $user->id)));
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
