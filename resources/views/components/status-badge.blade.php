@props(['status', 'label' => null])
@php
    $value = strtolower(trim((string) $status));
    $tone = match (true) {
        in_array($value, ['approved', 'approved for sending', 'reviewed', 'endorsed', 'accepted'], true) => 'status-approved',
        in_array($value, ['posted', 'paid', 'completed', 'executed', 'sent', 'resolved', 'closed'], true) => 'status-completed',
        in_array($value, ['active', 'ongoing', 'in progress', 'in_progress'], true) => 'status-ongoing',
        in_array($value, ['pending', 'for review', 'submitted', 'under review', 'unpaid'], true) => 'status-review',
        in_array($value, ['returned', 'partial', 'overdue', 'revision requested'], true) => 'status-revision',
        in_array($value, ['rejected', 'declined', 'void', 'cancelled', 'canceled', 'inactive', 'blocked'], true) => 'status-declined',
        in_array($value, ['draft', 'new', 'prospective'], true) => 'status-draft',
        default => 'status-archived',
    };
@endphp
<span {{ $attributes->class(['badge', 'status-badge', $tone]) }}>{{ $label ?? $status }}</span>
