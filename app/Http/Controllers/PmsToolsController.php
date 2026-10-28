<?php

namespace App\Http\Controllers;

use App\Models\{ChapterSetting, DocumentTemplate, Project, BodDecision};
use App\Services\{ChapterService, ExportService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};

class PmsToolsController extends Controller
{
    public function reports(Request $request)
    {
        $projects = ChapterService::visibleProjects($request->user())->whereIn('status', Project::APPROVED)->orderBy('title')->get();
        return view('chapter.report-generator', compact('projects'));
    }

    public function export(Request $request, ExportService $exports)
    {
        $data = $request->validate(['format' => 'required|in:pdf,docx', 'project_id' => 'nullable|integer|exists:projects,id', 'type' => 'required|in:progress,budgets']);
        $query = ChapterService::visibleProjects($request->user())->whereIn('status', Project::APPROVED);
        if (!empty($data['project_id'])) $query->whereKey($data['project_id']);
        $projects = $query->with('chair')->orderBy('title')->get();
        abort_if(!empty($data['project_id']) && $projects->isEmpty(), 403);
        $sections = [];
        foreach ($projects as $project) {
            $body = 'Status: '.$project->status."\nChair: ".($project->chair?->name ?? 'Unassigned')."\nSchedule: ".($project->starts_on?->format('d M Y') ?? 'Unscheduled')."\nCompleted tasks: ".$project->progress.'%';
            if ($data['type'] === 'budgets') $body .= "\nAllocated: PHP ".number_format($project->allocated, 2)."\nPosted expenses: PHP ".number_format($project->spent, 2)."\nBalance: PHP ".number_format($project->remaining, 2);
            $sections[($project->reference ?? '#'.$project->id).' — '.$project->title] = $body;
        }
        return $exports->download($request->user(), $data['type'] === 'budgets' ? 'Chapter Budget Summary' : 'Chapter Project Progress', $sections ?: ['Projects' => 'No approved projects match this report.'], $data['format']);
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

    public function settings() { return view('chapter.pms-settings', ['chapterName' => ChapterSetting::where('key', 'report_chapter_name')->value('value') ?? 'JCI Carmona']); }

    public function saveSettings(Request $request)
    {
        $data = $request->validate(['report_chapter_name' => 'required|string|max:100']);
        DB::transaction(function () use ($data) {
            $setting = ChapterSetting::firstOrNew(['key' => 'report_chapter_name']);
            $before = $setting->toArray(); $setting->value = $data['report_chapter_name']; $setting->save();
            ChapterService::audit('report_settings_updated', $setting, $before);
        });
        return back()->with('success', 'Report settings saved.');
    }

    public function decisionEvidence(Request $request, BodDecision $decision)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'bod', 'treasurer'], true) || $decision->project->isChair($request->user()), 403);
        abort_unless(Storage::disk('local')->exists($decision->evidence_path), 404);
        return Storage::disk('local')->download($decision->evidence_path, 'bod-resolution-'.$decision->id.'.'.pathinfo($decision->evidence_path, PATHINFO_EXTENSION), ['X-Content-Type-Options' => 'nosniff']);
    }
}
