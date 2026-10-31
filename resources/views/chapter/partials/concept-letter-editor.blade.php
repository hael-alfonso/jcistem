@php $conceptData = $project->concept ?? []; @endphp
<section class="official-document-editor concept-letter-editor" aria-labelledby="concept-letter-title">
    @include('chapter.partials.official-letterhead')
    <h2 id="concept-letter-title">INTERNAL PROJECT PROPOSAL LETTER</h2>
    <div class="official-letter-body">
        <label class="official-field"><span>Date</span><input type="date" name="concept[letter_date]" value="{{ old('concept.letter_date', $conceptData['letter_date'] ?? '') }}"></label>
        <div class="official-recipient-grid">
            <label class="official-field"><span>Name of Local Organization President</span><input name="concept[president_name]" value="{{ old('concept.president_name', $conceptData['president_name'] ?? '') }}" required></label>
            <strong>President, JCI Carmona</strong>
            <label class="official-field"><span>Chapter address</span><input name="concept[chapter_address]" value="{{ old('concept.chapter_address', $conceptData['chapter_address'] ?? '') }}"></label>
        </div>
        <p><strong>Subject:</strong> Project Proposal for “{{ old('title', $project->title ?: 'Project Name') }}”</p>
        <p>Dear President <span class="official-placeholder">[Last Name]</span> and the Board of Directors,</p>
        <p>I am writing to formally propose <strong>{{ old('title', $project->title ?: '[Project Name]') }}</strong> for the upcoming quarter. This project aligns with the JCI Active Citizenship Framework and addresses United Nations Sustainable Development Goal (SDG)
            <span class="official-inline-fields"><input name="concept[sdg_number]" value="{{ old('concept.sdg_number', $conceptData['sdg_number'] ?? '') }}" placeholder="Number" required aria-label="SDG number"><input name="concept[sdg_name]" value="{{ old('concept.sdg_name', $conceptData['sdg_name'] ?? '') }}" placeholder="Name of SDG" required aria-label="SDG name"></span>.
        </p>
        <label class="official-field official-paragraph-field"><span>Primary objective</span><textarea name="concept[objective]" required placeholder="State the core project goal and intended community impact.">{{ old('concept.objective', $conceptData['objective'] ?? '') }}</textarea></label>
        <h3>PROJECT OVERVIEW</h3>
        <div class="official-overview-table">
            <span>Proposed Date</span><strong>{{ old('starts_on', $project->starts_on?->format('M d, Y')) ?: 'To be confirmed' }}</strong>
            <span>Target Location</span><strong>{{ old('venue', $project->venue) ?: 'To be confirmed' }}</strong>
            <span>Target Beneficiaries</span><label><input name="concept[beneficiaries]" value="{{ old('concept.beneficiaries', $conceptData['beneficiaries'] ?? '') }}" placeholder="Target group / expected participants" required></label>
            <span>Proposed Project Chair</span><strong>{{ $project->chair?->name ?? $project->owner?->name ?? auth()->user()->name }}</strong>
        </div>
        <h3>RESOURCE REQUIREMENTS AND STRATEGY</h3>
        <div class="official-resource-grid">
            <p>The estimated working budget is <strong data-budget-letter-total>PHP {{ number_format($displayedBudgetTotal, 2) }}</strong>, to be sourced from:</p>
            <label class="official-field"><span>Funding strategy or chapter allocation</span><input name="concept[funding_source]" value="{{ old('concept.funding_source', $conceptData['funding_source'] ?? '') }}" required></label>
            <label class="official-field"><span>Required active JCI members</span><input name="concept[committee_size]" value="{{ old('concept.committee_size', $conceptData['committee_size'] ?? '') }}" required></label>
            <label class="official-field official-paragraph-field"><span>Additional resource requirements</span><textarea name="concept[resources]" placeholder="Venue, equipment, materials, permits, volunteers, or partner support.">{{ old('concept.resources', $conceptData['resources'] ?? '') }}</textarea></label>
        </div>
        <label class="official-field official-paragraph-field"><span>Formal request to the Board</span><textarea name="concept[request]" placeholder="Request Board review and approval and list the attached planning documents.">{{ old('concept.request', $conceptData['request'] ?? '') }}</textarea></label>
        <p>Yours in JCI,</p>
        <div class="official-signature-block"><strong>{{ $project->owner?->name ?? auth()->user()->name }}</strong><label class="official-field"><span>Your position / title</span><input name="concept[proponent_position]" value="{{ old('concept.proponent_position', $conceptData['proponent_position'] ?? '') }}" required></label><label class="official-field"><span>Contact information</span><input name="concept[contact_info]" value="{{ old('concept.contact_info', $conceptData['contact_info'] ?? auth()->user()->email) }}" required></label><span>JCI Carmona</span></div>
    </div>
</section>
