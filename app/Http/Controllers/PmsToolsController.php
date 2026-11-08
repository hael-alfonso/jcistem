<?php

namespace App\Http\Controllers;

use App\Models\{ChapterSetting, DocumentTemplate, Project, BodDecision};
use App\Services\{ChapterService, ExportService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB, Storage};

class PmsToolsController extends Controller
{
    public function reports(Request $request)
    {
        $data = $request->validate(['project_id' => 'nullable|integer|exists:projects,id']);
        $projects = ChapterService::visibleProjects($request->user())
            ->whereIn('status', Project::APPROVED)
            ->with('chair')->withSum('allocations as allocated_total', 'amount')
            ->withSum(['transactions as spent_total' => fn ($query) => $query->where('status', 'Posted')->where('direction', 'debit')], 'amount')
            ->orderBy('title')->get();
        $selectedId = $data['project_id'] ?? null;
        $shownProjects = $selectedId ? $projects->where('id', (int) $selectedId) : $projects;
        abort_if($selectedId && $shownProjects->isEmpty(), 403);
        return view('chapter.report-generator', compact('projects', 'shownProjects', 'selectedId'));
    }

    public function export(Request $request, ExportService $exports)
    {
        $data = $request->validate(['format' => 'required|in:pdf,docx', 'project_id' => 'nullable|integer|exists:projects,id', 'type' => 'required|in:budgets']);
        $query = ChapterService::visibleProjects($request->user())->whereIn('status', Project::APPROVED);
        if (!empty($data['project_id'])) $query->whereKey($data['project_id']);
        $projects = $query->with('chair')->orderBy('title')->get();
        abort_if(!empty($data['project_id']) && $projects->isEmpty(), 403);
        $sections = [];
        foreach ($projects as $project) {
            $body = 'Status: '.$project->status."\nChair: ".($project->chair?->name ?? 'Unassigned')."\nAllocated: PHP ".number_format($project->allocated, 2)."\nPosted expenses: PHP ".number_format($project->spent, 2)."\nBalance: PHP ".number_format($project->remaining, 2);
            $sections[($project->reference ?? '#'.$project->id).' — '.$project->title] = $body;
        }
        return $exports->download($request->user(), 'Chapter Budget Summary', $sections ?: ['Projects' => 'No approved projects match this summary.'], $data['format']);
    }

    public function templates()
    {
        return view('chapter.templates', [
            'templates' => DocumentTemplate::latest()->paginate(20),
            'referenceForms' => config('reference_forms'),
        ]);
    }

    public function referenceFormDownload(string $form)
    {
        // Only known catalog keys are accepted; request input never becomes a file path.
        $entry = config('reference_forms')[$form] ?? null;
        abort_unless($entry, 404);
        $path = resource_path('forms/'.$entry['file']);
        abort_unless(is_file($path), 404);

        return response()->download($path, $entry['file'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function templateDownload(DocumentTemplate $template)
    {
        abort_unless(Storage::disk('local')->exists($template->path), 404);
        return Storage::disk('local')->download($template->path, $template->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function templateStore(Request $request)
    {
        $data = $request->validate(['title' => 'required|string|max:200', 'version' => 'required|string|max:100', 'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:10240']);
        $path = $request->file('file')->store('chapter-templates', 'local');
        try {
            DB::transaction(function () use ($request, $data, $path) {
                $template = DocumentTemplate::create(['title' => $data['title'], 'version' => $data['version'], 'path' => $path, 'original_name' => $request->file('file')->getClientOriginalName(), 'uploaded_by' => $request->user()->id]);
                ChapterService::audit('template_published', $template);
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Approved template published. Earlier versions remain available.');
    }

    public function settings()
    {
        return view('chapter.pms-settings', [
            'chapterName' => ChapterSetting::where('key', 'report_chapter_name')->value('value') ?? 'JCI Carmona',
            'workspaceNotice' => ChapterSetting::where('key', 'workspace_notice')->value('value') ?? '',
            'workspaceTheme' => ChapterSetting::where('key', 'workspace_theme')->value('value') ?? 'jci',
            'evidencePhotoLimit' => (int) (ChapterSetting::where('key', 'evidence_photo_limit')->value('value') ?? 8),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'report_chapter_name' => 'required|string|max:100',
            'workspace_notice' => 'nullable|string|max:500',
            'evidence_photo_limit' => 'required|integer|between:1,8',
            'workspace_theme' => 'nullable|in:jci,teal,navy',
        ]);
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                $setting = ChapterSetting::firstOrNew(['key' => $key]);
                $before = $setting->toArray();
                $setting->value = $value ?? '';
                $setting->save();
                ChapterService::audit('system_setting_updated', $setting, $before);
            }
        });
        Cache::forget('jci.workspace_notice');
        Cache::forget('jci.workspace_theme');
        return back()->with('success', 'System controls saved.');
    }

    public function decisionEvidence(Request $request, BodDecision $decision)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'bod', 'treasurer'], true) || $decision->project->isChair($request->user()), 403);
        abort_unless(Storage::disk('local')->exists($decision->evidence_path), 404);
        return Storage::disk('local')->download($decision->evidence_path, 'bod-decision-'.$decision->id.'.'.pathinfo($decision->evidence_path, PATHINFO_EXTENSION), ['X-Content-Type-Options' => 'nosniff']);
    }
}
