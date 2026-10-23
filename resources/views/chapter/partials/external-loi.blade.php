@php
    $letter = $record->data ?? [];
    $snapshot = $letter['project_snapshot'] ?? [];
    $legacyLetter = !array_key_exists('project_action', $letter);
    $letterDate = !empty($letter['letter_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $letter['letter_date'])
        ? \Carbon\Carbon::parse($letter['letter_date'])->format('F j, Y')
        : $record->created_at?->format('F j, Y');
    $recognition = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $letter['commitments'] ?? '') ?: [])));
@endphp
<article class="panel printable-document project-document external-loi-document">
    <header class="project-letterhead"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="JCI logo" width="140" height="78"><div><strong>JCI Carmona</strong><span>JCI Philippines · Carmona City, Cavite</span></div></header>
    <p class="document-overline">EXTERNAL PARTNERSHIP LETTER · {{ $snapshot['reference'] ?? $record->project?->reference ?? 'JCI CARMONA' }}</p>
    <p class="letter-date">{{ $letterDate }}</p>
    <address class="letter-address">
        <strong>{{ $letter['recipient'] ?? 'Recipient name' }}</strong>
        @if($letter['recipient_title'] ?? null)<br>{{ $letter['recipient_title'] }}@endif
        <br>{{ $letter['organization'] ?? 'Organization' }}
        @if($letter['address'] ?? null)<br><span class="preserve-lines">{{ $letter['address'] }}</span>@endif
    </address>
    <p class="letter-subject"><strong>Subject:</strong> {{ $record->title }}</p>
    <div class="document-copy">
        <p>Dear {{ $letter['recipient'] ?? 'Partner' }},</p>
        <p>Greetings from JCI Carmona!</p>
        @if($legacyLetter)
            <p class="preserve-lines">{{ $letter['body'] ?? 'Letter content was not provided in this earlier version.' }}</p>
            @if($letter['purpose'] ?? null)<p><strong>Purpose:</strong> {{ $letter['purpose'] }}</p>@endif
            @if($letter['support'] ?? null)<p><strong>Requested support:</strong> {{ $letter['support'] }}</p>@endif
            @if($letter['commitments'] ?? null)<p><strong>Proposed commitments:</strong> {{ $letter['commitments'] }}</p>@endif
        @else
            <p>Junior Chamber International (JCI) is a global leadership development organization of young active citizens working to create positive change. Through JCI Carmona, our members develop leadership skills while responding to community needs.</p>
            <p>We are pleased to present <strong>{{ $snapshot['title'] ?? $record->project?->title ?? 'our proposed project' }}</strong>. This initiative addresses {{ $letter['purpose'] ?? 'a community need' }} in {{ $letter['target_location'] ?? 'the target community' }}. The project will {{ $letter['project_action'] ?? 'deliver the activities described in the proposal' }}.</p>
            <p>The proposed activity is scheduled for {{ !empty($snapshot['date']) ? \Carbon\Carbon::parse($snapshot['date'])->format('F j, Y') : 'a date to be confirmed' }}@if($snapshot['venue'] ?? null) at {{ $snapshot['venue'] }}@endif, and is expected to serve approximately {{ $letter['beneficiary_count'] ?? 'the identified' }} beneficiaries.</p>
            @if($letter['partner_alignment'] ?? null)<p>We believe {{ $letter['organization'] ?? 'your organization' }} could be a meaningful partner because {{ $letter['partner_alignment'] }}.</p>@endif
            <p>We invite your partnership through <strong>{{ $letter['support'] ?? 'support to be discussed' }}</strong>.</p>
            @if($recognition)
                <p>In appreciation, JCI Carmona proposes the following recognition, subject to final agreement:</p>
                <ul>@foreach($recognition as $benefit)<li>{{ preg_replace('/^\s*(?:[-*]|\d+[.)])\s*/', '', $benefit) }}</li>@endforeach</ul>
            @endif
            <p>The detailed project proposal, including objectives, timeline, committee structure, and budget, is available for your review. Our team would welcome the opportunity to discuss how we can work together.</p>
            @if($letter['body'] ?? null)<p class="preserve-lines">{{ $letter['body'] }}</p>@endif
            <p>Thank you for your time, consideration, and shared commitment to the community.</p>
        @endif
        <p>Yours in JCI Service,</p>
        <div class="document-signatures">
            <div class="document-signature"><span>Signature</span><strong>{{ $letter['signatory'] ?? $record->author?->name ?? 'Project Chairperson' }}</strong><small>Project Chairperson, {{ $snapshot['title'] ?? $record->project?->title ?? 'Project' }}<br>JCI Carmona</small></div>
            <div class="document-signature"><span>Noted by · Signature</span><strong>{{ $letter['president_name'] ?? 'Local Organization President' }}</strong><small>Local Organization President<br>JCI Carmona</small></div>
        </div>
    </div>
    <footer class="document-page-footer">JCI Carmona · {{ $record->status }} · Version {{ $record->version }}</footer>
</article>
