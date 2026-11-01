@if($u->role === 'treasurer' || $project->isChair($u))
<div class="panel-heading flush expense-claims-heading"><div><h3>Expense claims</h3><p>A claim enters the official ledger only after independent Treasurer verification.</p></div><a class="text-link" href="{{ route('expense-claims.index') }}">{{ $u->role === 'treasurer' ? 'Review all claims' : 'See my claims' }}</a></div>
@forelse($project->expenseClaims->sortByDesc('id')->take(5) as $claim)
<div class="list-row expense-claim-row"><span><strong>{{ $claim->description }}</strong><small>PHP {{ number_format((float) $claim->amount, 2) }} · {{ $claim->submitter?->name }} · {{ $claim->created_at?->format('d M Y') }}</small></span><span class="badge">{{ $claim->status }}</span><a class="text-link" href="{{ route('expense-claims.receipt', $claim) }}" target="_blank" rel="noopener">View receipt</a></div>
@empty<p class="panel-empty">No expense claims submitted yet.</p>@endforelse
@endif
