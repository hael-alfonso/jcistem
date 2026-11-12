@php $assessmentAttempt = old('_finance_dialog') === 'dues-assessment-dialog'; @endphp
<dialog class="finance-dialog dues-assessment-dialog" id="dues-assessment-dialog" aria-labelledby="dues-assessment-title">
    <div class="finance-dialog-header">
        <div><span class="eyebrow">MEMBER DUES</span><h2 id="dues-assessment-title">Assess Member Dues</h2><p>Choose the account and enter the chapter-approved period and amount.</p></div>
        <button class="finance-dialog-close" type="button" data-finance-dialog-close aria-label="Close assessment form"><x-icon name="close" /></button>
    </div>
    <div class="finance-dialog-body">
        @if($assessmentAttempt && $errors->any())<div class="alert error" role="alert">Please review the form: {{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('dues.store') }}" class="form-grid">@csrf<input type="hidden" name="_finance_dialog" value="dues-assessment-dialog">
            <fieldset class="full dues-target-choices"><legend>Who is this for? <span aria-hidden="true">*</span></legend>
                <label class="dues-target-option"><input type="radio" name="member_id" value="" @checked($assessmentAttempt && old('member_id') === '') required><span><strong>All active members</strong><small>Same period and amount for {{ number_format($assessableMembers->count()) }} active {{ \Illuminate\Support\Str::plural('account', $assessableMembers->count()) }}</small></span></label>
                <label class="dues-target-search">Or find one member<input type="search" placeholder="Search by name or member number" data-dues-choice-search></label>
                <div class="dues-target-list" data-dues-choice-list>@foreach($assessableMembers as $person)<label class="dues-target-option"><input type="radio" name="member_id" value="{{ $person->id }}" @checked($assessmentAttempt && (string) old('member_id') === (string) $person->id) required><span><strong>{{ $person->name }}</strong>@if($person->member_no)<small>{{ $person->member_no }}</small>@endif</span></label>@endforeach</div>
            </fieldset>
            <label class="field"><span>Dues period <em aria-hidden="true">*</em></span><input name="period" type="month" value="{{ $assessmentAttempt ? old('period', now()->format('Y-m')) : now()->format('Y-m') }}" required></label>
            <label class="field"><span>Due date <em aria-hidden="true">*</em></span><input name="due_date" type="date" value="{{ $assessmentAttempt ? old('due_date') : '' }}" required></label>
            <label class="field"><span>Amount due (PHP) <em aria-hidden="true">*</em></span><input name="amount" type="number" min="0" max="9999999.99" step="0.01" value="{{ $assessmentAttempt ? old('amount') : '' }}" required></label>
            <div class="full"><label class="field"><span>Policy or adjustment reason <em aria-hidden="true">*</em></span><textarea name="remarks" rows="3" maxlength="5000" required>{{ $assessmentAttempt ? old('remarks') : '' }}</textarea></label></div>
            <p class="full hint dues-assessment-note">Saving an existing period updates its assessment without removing posted payments. The amount cannot be lower than payments already posted. Enter zero only for a waiver or adjustment.</p>
            <div class="finance-dialog-actions"><button class="btn secondary" type="button" data-finance-dialog-close>Cancel</button><button class="btn primary" type="submit"><x-icon name="save" /> Save assessment</button></div>
        </form>
    </div>
</dialog>
@if($assessmentAttempt)<span hidden data-finance-dialog-reopen="dues-assessment-dialog"></span>@endif
