@php
    $actionIcon = match (true) {
        $action === 'archive' => 'archive',
        in_array($action, ['decline', 'reject']) => 'close',
        str_starts_with($action, 'revise_'), str_starts_with($action, 'return_') => 'arrowLeft',
        str_starts_with($action, 'submit_'), $action === 'request_completion' => 'arrow',
        $action === 'start_proposal' => 'filePlus',
        default => 'check',
    };
@endphp
<form method="POST" action="{{ route('projects.transition', $project) }}" class="action-form">
    @csrf
    <input type="hidden" name="action" value="{{ $action }}">
    @if($comments ?? false)
        <x-field name="comments" label="Review comments / conditions" type="textarea" required/>
    @endif
    <button class="btn {{ $tone ?? 'secondary' }}" @if(in_array($action, ['endorse', 'decline', 'approve', 'reject', 'complete', 'archive'])) data-confirm="{{ $label }}? This decision will be recorded in the project history." @endif>
        <x-icon name="{{ $actionIcon }}" /> {{ $label }}
    </button>
</form>
