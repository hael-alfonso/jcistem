<?php

namespace App\Http\Controllers;

use App\Models\{Partner, PartnershipAgreement, Project, User};
use App\Services\ChapterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\Rule;

class AgreementController extends Controller
{
    private function visible(PartnershipAgreement $agreement, User $user): bool
    {
        return ChapterService::canView($agreement->project, $user)
            && (in_array($agreement->status, ['Approved', 'Executed'], true) || $user->role === 'admin' || $agreement->created_by === $user->id || $agreement->project->chair_id === $user->id);
    }

    public function index(Request $request)
    {
        $projectIds = ChapterService::visibleProjects($request->user())->pluck('id');
        $agreements = PartnershipAgreement::with(['partner', 'project', 'author'])->whereIn('project_id', $projectIds)
            ->when($request->user()->role !== 'admin', fn ($q) => $q->where(fn ($v) => $v->whereIn('status', ['Approved', 'Executed'])->orWhere('created_by', $request->user()->id)->orWhereHas('project', fn ($p) => $p->where('chair_id', $request->user()->id))))
            ->latest()->paginate(20);
        return view('chapter.agreements', compact('agreements'));
    }

    public function create(Request $request, Partner $partner)
    {
        $projects = Project::where('chair_id', $request->user()->id)->whereIn('status', ['Approved', 'Plan Locked', 'Ongoing'])->orderBy('title')->get();
        abort_unless($projects->isNotEmpty() || $request->user()->role === 'admin', 403);
        if ($request->user()->role === 'admin') $projects = Project::whereIn('status', Project::APPROVED)->orderBy('title')->get();
        return view('chapter.agreement-form', ['agreement' => new PartnershipAgreement, 'partner' => $partner, 'projects' => $projects]);
    }

    public function edit(Request $request, PartnershipAgreement $agreement)
    {
        abort_unless(in_array($agreement->status, ['Draft', 'Returned'], true) && ($agreement->created_by === $request->user()->id || $agreement->project->chair_id === $request->user()->id), 403);
        return view('chapter.agreement-form', ['agreement' => $agreement, 'partner' => $agreement->partner, 'projects' => collect([$agreement->project])]);
    }

    public function save(Request $request, Partner $partner, ?PartnershipAgreement $agreement = null)
    {
        if ($agreement) abort_unless($agreement->partner_id === $partner->id && in_array($agreement->status, ['Draft', 'Returned'], true) && ($agreement->created_by === $request->user()->id || $agreement->project->chair_id === $request->user()->id), 403);
        $data = $request->validate([
            'project_id' => 'required|integer|exists:projects,id', 'title' => 'required|string|max:200',
            'terms.purpose' => 'required|string|max:5000', 'terms.partner_support' => 'required|string|max:5000',
            'terms.chapter_commitments' => 'required|string|max:5000', 'terms.deliverables' => 'required|string|max:5000',
            'terms.start_on' => 'required|date', 'terms.end_on' => 'required|date|after_or_equal:terms.start_on',
            'terms.funding' => 'nullable|string|max:5000', 'terms.reporting' => 'required|string|max:5000',
            'terms.contact_person' => 'required|string|max:200', 'terms.president_name' => 'required|string|max:200',
            'terms.partner_signatory' => 'required|string|max:200', 'terms.partner_signatory_title' => 'nullable|string|max:200',
            'terms.data_handling' => 'nullable|string|max:5000', 'terms.changes_and_termination' => 'required|string|max:5000',
        ]);
        $project = Project::findOrFail($data['project_id']);
        abort_unless(!$project->archived_at && !in_array($project->status, ['Archived', 'Completed', 'Cancelled'], true) && ($project->isChair($request->user()) || ($request->user()->role === 'admin' && in_array($project->status, Project::APPROVED))), 403);
        if ($agreement) abort_unless($agreement->project_id === $project->id, 403);
        DB::transaction(function () use (&$agreement, $data, $partner, $request) {
            $agreement ??= new PartnershipAgreement(['partner_id' => $partner->id, 'created_by' => $request->user()->id]);
            $before = $agreement->toArray();
            $agreement->fill($data + ['status' => 'Draft'])->save();
            ChapterService::audit('partnership_agreement_saved', $agreement, $before);
        });
        return redirect()->route('agreements.show', $agreement)->with('success', 'Agreement draft saved. Submit it to Admin for review.');
    }

    public function show(Request $request, PartnershipAgreement $agreement)
    {
        $agreement->load(['partner', 'project.chair', 'author', 'approver']);
        abort_unless($this->visible($agreement, $request->user()), 403);
        return view('chapter.agreement', compact('agreement'));
    }

    public function transition(Request $request, PartnershipAgreement $agreement)
    {
        $data = $request->validate(['action' => ['required', Rule::in(['submit', 'approve', 'return'])], 'comments' => 'nullable|string|max:5000']);
        DB::transaction(function () use ($request, $agreement, $data) {
            $agreement = PartnershipAgreement::lockForUpdate()->findOrFail($agreement->id);
            $before = $agreement->toArray();
            if ($data['action'] === 'submit') {
                abort_unless(in_array($agreement->status, ['Draft', 'Returned'], true) && ($agreement->created_by === $request->user()->id || $agreement->project->chair_id === $request->user()->id), 403);
                $agreement->status = 'For Review';
                ChapterService::notify(User::where('role', 'admin')->where('status', 'active')->pluck('id'), 'Partnership agreement for review', $agreement->title, route('agreements.show', $agreement, false), true);
            } else {
                abort_unless($request->user()->role === 'admin' && $agreement->status === 'For Review' && $agreement->created_by !== $request->user()->id && $agreement->project->chair_id !== $request->user()->id, 403);
                if (!trim($data['comments'] ?? '')) throw \Illuminate\Validation\ValidationException::withMessages(['comments' => 'Review comments are required.']);
                $agreement->status = $data['action'] === 'approve' ? 'Approved' : 'Returned';
                $agreement->approved_by = $data['action'] === 'approve' ? $request->user()->id : null;
                $agreement->approved_at = $data['action'] === 'approve' ? now() : null;
                $agreement->review_comments = $data['comments'];
                ChapterService::notify([$agreement->created_by, $agreement->project->chair_id], 'Agreement '.$agreement->status, $agreement->title, route('agreements.show', $agreement, false), true);
            }
            $agreement->save();
            ChapterService::audit('partnership_agreement_'.$data['action'], $agreement, $before, $data['comments'] ?? null);
        });
        return back()->with('success', 'Agreement status updated.');
    }

    public function signed(Request $request, PartnershipAgreement $agreement)
    {
        abort_unless($request->user()->role === 'admin' && $agreement->status === 'Approved', 403);
        $data = $request->validate(['signed_on' => 'required|date', 'file' => 'required|file|mimes:pdf|max:10240']);
        $path = $request->file('file')->store('partnership-agreements', 'local');
        try {
            DB::transaction(function () use ($agreement, $request, $data, $path) {
                $agreement = PartnershipAgreement::lockForUpdate()->findOrFail($agreement->id);
                abort_unless($agreement->status === 'Approved', 403);
                $before = $agreement->toArray();
                $agreement->fill(['status' => 'Executed', 'signed_on' => $data['signed_on'], 'signed_path' => $path, 'signed_name' => $request->file('file')->getClientOriginalName()])->save();
                ChapterService::audit('partnership_agreement_executed', $agreement, $before);
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Signed agreement recorded.');
    }

    public function download(Request $request, PartnershipAgreement $agreement)
    {
        abort_unless($this->visible($agreement, $request->user()), 403);
        abort_unless($agreement->signed_path && Storage::disk('local')->exists($agreement->signed_path), 404);
        return Storage::disk('local')->download($agreement->signed_path, $agreement->signed_name, ['X-Content-Type-Options' => 'nosniff']);
    }
}
