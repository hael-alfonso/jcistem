@extends('layouts.chapter')
@section('title', 'Partner Directory')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PARTNERSHIPS</div><h1>Partner Directory</h1><p>Chapter contacts, external LOIs, and a shared record of partner interactions.</p></div><a class="btn secondary" href="{{ route('records', 'letters') }}">External LOIs</a></div>
<form class="list-filters" action="{{ route('partners.index') }}" method="GET"><label><span>Find a partner</span><input name="q" value="{{ request('q') }}" placeholder="Organization or contact"></label><button class="btn secondary">Search</button></form>
@if(auth()->user()->role === 'admin')
<details class="panel form-panel record-detail"><summary>Add a partner</summary><form class="form-grid" method="POST" action="{{ route('partners.store') }}">@csrf
    <x-field name="name" label="Organization name" required/><x-field name="contact_name" label="Contact person"/><x-field name="contact_title" label="Position"/><x-field name="email" label="Email" type="email"/><x-field name="phone" label="Phone"/><x-field name="status" label="Relationship" type="select" :options="['Prospective'=>'Prospective','Active'=>'Active','Past'=>'Past']" value="Prospective"/><div class="full"><x-field name="address" label="Address" type="textarea"/></div><div class="full"><x-field name="notes" label="Notes" type="textarea"/></div><div class="full"><button class="btn primary">Save partner</button></div>
</form></details>
@endif
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Organization</th><th>Main contact</th><th>Relationship</th><th>LOIs</th><th></th></tr></thead><tbody>
@forelse($partners as $partner)<tr><td><strong>{{ $partner->name }}</strong></td><td>{{ $partner->contact_name ?: 'Not recorded' }}<small>{{ $partner->email ?: $partner->phone }}</small></td><td><span class="badge">{{ $partner->status }}</span></td><td>{{ $partner->letters_count }}</td><td><a class="text-link" href="{{ route('partners.show', $partner) }}">View record</a></td></tr>
@empty<tr><td colspan="5"><div class="empty-state compact"><h3>No partners found</h3><p>Add a contact or clear the search.</p></div></td></tr>@endforelse
</tbody></table></div>@include('chapter.partials.pagination',['items'=>$partners])</section>
@endsection
