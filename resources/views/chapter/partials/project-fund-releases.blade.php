@php
    $fundReleases = $project->fundReleases->sortByDesc('id');
    $pendingFundReleases = $fundReleases->where('status', 'Pending');
    $canInitiateRelease = $u->role === 'treasurer' && $u->status === 'active' && $project->chair_id !== $u->id
        && $project->chair_id && $project->plan_locked_at && in_array($project->status, ['Plan Locked', 'Ongoing'], true);
    $releaseOptions = collect($project->budget_items ?? [])->mapWithKeys(function ($item) use ($project) {
        $itemId = $item['id'] ?? '';
        $allocated = (float) ($project->allocations->firstWhere('budget_item_id', $itemId)?->amount ?? 0);
        $posted = (float) $project->transactions->where('status', 'Posted')->where('direction', 'debit')->where('budget_item_id', $itemId)->sum('amount');
        $claims = (float) $project->expenseClaims->where('status', 'Pending')->where('budget_item_id', $itemId)->sum('amount');
        $releases = (float) $project->fundReleases->where('status', 'Pending')->where('budget_item_id', $itemId)->sum('amount');
        $available = max(0, $allocated - $posted - $claims - $releases);
        return $available > 0 ? [$itemId => $item['description'].' · Available PHP '.number_format($available, 2)] : [];
    });
@endphp

@if(in_array($u->role, ['treasurer'], true) || $project->isChair($u))
<section class="project-fund-releases" aria-labelledby="fund-release-title">
    <div class="project-finance-heading">
        <div>
            <h3 class="subheading" id="fund-release-title">Fund releases to the Project Chair</h3>
            <p>A cash advance reserves its budget line. It becomes a posted debit only after the assigned Chair confirms receipt.</p>
        </div>
        @if($pendingFundReleases->isNotEmpty())<span class="badge fund-release-attention">{{ $pendingFundReleases->count() }} awaiting Chair confirmation</span>@endif
    </div>

    @if($project->isChair($u) && $pendingFundReleases->isNotEmpty())
    <div class="fund-release-callout" role="status"><x-icon name="wallet" /><div><strong>Confirm only money you actually received</strong><p>Confirmation posts the amount to the official ledger and reduces the available project allocation. Decline it if the amount or payment details are incorrect.</p></div></div>
    @endif

    <div class="fund-release-list">
        @forelse($fundReleases as $release)
            @php
                $line = collect($project->budget_items ?? [])->firstWhere('id', $release->budget_item_id);
                $statusLabel = match($release->status) { 'Pending' => 'Awaiting Chair', 'Confirmed' => 'Received and posted', 'Declined' => 'Declined by Chair', 'Cancelled' => 'Cancelled', 'Voided' => 'Ledger entry voided', default => $release->status };
                $statusTone = match($release->status) { 'Confirmed' => 'confirmed', 'Pending' => 'pending', 'Declined', 'Cancelled', 'Voided' => 'closed', default => 'pending' };
            @endphp
            <article class="fund-release-item status-{{ $statusTone }}">
                <header><div><span>{{ $release->reference }}</span><h4>{{ $line['description'] ?? 'Approved budget expense' }}</h4></div><span class="badge fund-release-status">{{ $statusLabel }}</span></header>
                <dl>
                    <div><dt>Amount</dt><dd>PHP {{ number_format((float) $release->amount, 2) }}</dd></div>
                    <div><dt>Release date</dt><dd>{{ $release->release_date?->format('M d, Y') }}</dd></div>
                    <div><dt>Method</dt><dd>{{ $release->payment_method }}</dd></div>
                    <div><dt>Treasurer</dt><dd>{{ $release->treasurer?->name ?? 'Former member' }}</dd></div>
                </dl>
                <p>{{ $release->purpose }}</p>
                @if($release->transfer_reference)<small>Payment reference: {{ $release->transfer_reference }}</small>@endif
                @if($release->chair_note)<small>Chair note: {{ $release->chair_note }}</small>@endif
                @if($release->cancel_note)<small>Record note: {{ $release->cancel_note }}</small>@endif
                @if($release->status === 'Confirmed')<small>Confirmed {{ $release->confirmed_at?->format('M d, Y g:i A') }} · Ledger {{ $release->ledgerEntry?->reference }}</small>@endif

                @if($release->status === 'Pending' && $release->chair_id === $u->id)
                <div class="fund-release-actions no-print">
                    <form method="POST" action="{{ route('fund-releases.respond', $release) }}" class="fund-release-confirm-form">@csrf
                        <input type="hidden" name="action" value="confirm">
                        <label class="field"><span>Receipt note (optional)</span><textarea name="chair_note" rows="2" maxlength="2000" placeholder="Example: Cash counted and received in full"></textarea></label>
                        <button class="btn primary" type="submit" data-confirm="Confirm receipt of PHP {{ number_format((float) $release->amount, 2) }}? This will post one debit to the project ledger and reduce available funds."><x-icon name="check" /> Confirm money received</button>
                    </form>
                    <details class="fund-release-decline"><summary>Amount or payment is incorrect</summary><form method="POST" action="{{ route('fund-releases.respond', $release) }}">@csrf<input type="hidden" name="action" value="decline"><label class="field"><span>Reason for declining <em aria-hidden="true">*</em></span><textarea name="chair_note" rows="2" maxlength="2000" required></textarea></label><button class="btn danger" type="submit" data-confirm="Decline this fund release? No ledger transaction will be created.">Decline release</button></form></details>
                </div>
                @elseif($release->status === 'Pending' && $u->role === 'treasurer')
                <details class="fund-release-cancel no-print"><summary>Cancel pending release</summary><form method="POST" action="{{ route('fund-releases.cancel', $release) }}">@csrf<label class="field"><span>Cancellation reason <em aria-hidden="true">*</em></span><textarea name="cancel_note" rows="2" maxlength="2000" required></textarea></label><button class="btn danger" type="submit" data-confirm="Cancel this pending fund release? No ledger transaction will be created.">Cancel release</button></form></details>
                @endif
            </article>
        @empty
            <p class="panel-empty">No fund releases have been recorded. Use this for cash advances; use expense claims for reimbursements after the Chair paid an expense.</p>
        @endforelse
    </div>

    @if($canInitiateRelease && $releaseOptions->isNotEmpty())
    <details class="record-detail fund-release-create no-print" @if($errors->hasAny(['release_date', 'account', 'payment_method', 'purpose'])) open @endif>
        <summary><x-icon name="wallet" /> Record money prepared for the Project Chair<x-icon name="chevron" class="disclosure-icon" /></summary>
        <form method="POST" action="{{ route('fund-releases.store', $project) }}" class="form-grid">@csrf
            <p class="full hint">This creates a pending handover only. The budget is reserved now, while the official debit is posted after the Chair confirms receipt.</p>
            <x-field name="budget_item_id" label="Approved expense line" type="select" :options="$releaseOptions->all()" required/>
            <x-field name="amount" label="Amount prepared (PHP)" type="number" step="0.01" min="0.01" required/>
            <x-field name="release_date" label="Release date" type="date" :value="old('release_date', now()->format('Y-m-d'))" required/>
            <x-field name="payment_method" label="Payment method" type="select" :options="array_combine(['Cash','Bank transfer','GCash','Cheque','Other'], ['Cash','Bank transfer','GCash','Cheque','Other'])" required/>
            <x-field name="account" label="Source account or cash fund" required placeholder="Example: Chapter operating fund"/>
            <x-field name="transfer_reference" label="Cheque / transfer reference (optional)"/>
            <div class="full"><x-field name="purpose" label="Purpose and handover details" type="textarea" required/></div>
            <div class="full"><button class="btn primary"><x-icon name="send" /> Send for Chair confirmation</button></div>
        </form>
    </details>
    @elseif($canInitiateRelease && $releaseOptions->isEmpty())
        <p class="project-finance-alert">No allocation is currently available for a new release. Check posted spending, pending claims, releases awaiting confirmation, and line allocations.</p>
    @endif
</section>
@endif
