@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Create Project</h1>
        <p>Complete the Project Creation Form before validation and submission.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('admin.projects') }}"><x-icon name="arrow" /> Back to Projects</a>
    </div>
</div>

<x-card title="Project proposal form" subtitle="Fields follow the JCI Carmona master definitions">
    <form class="form-grid" id="createProjectForm">
        <div class="full form-note">
            <strong>Project Creation Form</strong>
            <span>All registered users can create and submit proposals. This Admin workspace uses the same form structure.</span>
        </div>
        <div>
            <label class="field"><span>Project Title</span><input name="title" required placeholder="Required"></label>
        </div>
        <div>
            <label class="field">
                <span>JCI Area / Area of Opportunity</span>
                <select name="area">
                    @foreach ($areas as $area)
                        <option>{{ $area }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="full"><label class="field"><span>Rationale / Needs</span><textarea name="needs" placeholder="Describe the need"></textarea></label></div>
        <div class="full"><label class="field"><span>Objectives</span><textarea name="objectives"></textarea></label></div>
        <div class="full"><label class="field"><span>Target Beneficiaries / Community</span><textarea name="beneficiaries"></textarea></label></div>
        <div class="full"><label class="field"><span>Partners / Stakeholders</span><input name="partners"></label></div>
        <div><label class="field"><span>Project Date</span><input type="date" name="date"></label></div>
        <div><label class="field"><span>Venue</span><input name="venue"></label></div>
        <div class="full"><label class="field"><span>Expected Participants</span><input name="participants"></label></div>
        <div class="full"><label class="field"><span>Expected Outputs</span><textarea name="outputs"></textarea></label></div>
        <div class="full"><label class="field"><span>Expected Outcomes</span><textarea name="outcomes"></textarea></label></div>
        <div><label class="field"><span>Proposed Budget</span><input type="number" name="budget" value="0" min="0"></label></div>
        <div><label class="field"><span>Member / Volunteer Participation</span><input name="memberParticipation"></label></div>
        <div class="full"><label class="field"><span>Risks</span><textarea name="risks"></textarea></label></div>
        <div class="full"><label class="field"><span>Resources</span><textarea name="resources"></textarea></label></div>
        <div class="full"><label class="field"><span>LOI / Partnership Requirements</span><textarea name="loiRequirements"></textarea></label></div>
        <div class="full"><label class="field"><span>Monitoring and Evaluation Notes</span><textarea name="meNotes"></textarea></label></div>
        <div class="full">
            <label class="field">
                <span>Supporting Documents</span>
                <div class="file-drop">Drop files here or choose files<input type="file" multiple></div>
            </label>
        </div>
        <div class="full form-actions">
            <button type="button" class="btn btn-secondary" data-toast="Project draft saved.">Save Draft</button>
            <button type="submit" class="btn btn-primary">Validate &amp; Submit</button>
        </div>
    </form>
</x-card>
@endsection
