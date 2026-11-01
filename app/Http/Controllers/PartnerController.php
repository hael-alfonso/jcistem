<?php

namespace App\Http\Controllers;

use App\Models\{Partner, PartnerActivity, Project};
use App\Services\{ChapterService};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:150']);
        $partners = Partner::query()->withCount('letters')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%')->orWhere('contact_name', 'like', '%'.$request->string('q').'%')))
            ->orderBy('name')->paginate(20)->withQueryString();
        return view('chapter.partners', compact('partners'));
    }

    public function show(Partner $partner)
    {
        $partner->load(['letters.project', 'letters.author', 'activities.project', 'activities.author', 'agreements.project']);
        $user = request()->user();
        $partner->setRelation('letters', $partner->letters->filter(fn ($letter) => in_array($user->role, ['admin', 'bod'], true) || $letter->project?->chair_id === $user->id || in_array($letter->status, ['Approved for Sending', 'Sent', 'Archived'], true)));
        $partner->setRelation('agreements', $partner->agreements->filter(fn ($agreement) => in_array($agreement->status, ['Approved', 'Executed'], true) || $user->role === 'admin' || $agreement->created_by === $user->id || $agreement->project?->chair_id === $user->id));
        $canPrepareAgreement = $user->role === 'admin' || Project::where('chair_id', $user->id)->whereIn('status', ['Approved', 'Plan Locked', 'Ongoing'])->exists();
        $projects = Project::whereIn('status', Project::APPROVED)->orderBy('title')->get(['id', 'title']);
        return view('chapter.partner', compact('partner', 'projects', 'canPrepareAgreement'));
    }

    public function save(Request $request, ?Partner $partner = null)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200', Rule::unique('partners')->ignore($partner?->id)],
            'contact_name' => 'nullable|string|max:200',
            'contact_title' => 'nullable|string|max:200',
            'email' => 'nullable|email|max:200',
            'phone' => 'nullable|string|max:60',
            'address' => 'nullable|string|max:2000',
            'status' => ['required', Rule::in(['Prospective', 'Active', 'Past'])],
            'notes' => 'nullable|string|max:5000',
        ]);
        $partner ??= new Partner;
        $before = $partner->toArray();
        $partner->fill($data)->save();
        ChapterService::audit('partner_saved', $partner, $before);
        return redirect()->route('partners.show', $partner)->with('success', 'Partner record saved.');
    }

    public function activity(Request $request, Partner $partner)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'occurred_on' => 'required|date',
            'type' => ['required', Rule::in(['Meeting', 'Call', 'Email', 'Delivery', 'Agreement', 'Follow-up', 'Other'])],
            'title' => 'required|string|max:200',
            'notes' => 'nullable|string|max:5000',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);
        $activity = PartnerActivity::create($data + ['partner_id' => $partner->id, 'created_by' => $request->user()->id]);
        ChapterService::audit('partner_activity_added', $activity);
        return back()->with('success', 'Partner activity added.');
    }
}
