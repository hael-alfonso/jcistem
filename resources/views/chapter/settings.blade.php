@extends('layouts.chapter')
@section('title','My Settings')
@section('content')
@php $preferences = $member->profile['preferences'] ?? []; @endphp
<div class="page-heading profile-page-heading"><div><div class="eyebrow">JCI CARMONA · PERSONAL SETTINGS</div><h1>My Settings</h1><p>Control your reminders and sign-in security without changing your member profile.</p></div><a class="btn secondary" href="{{ route('account') }}"><x-icon name="user" /> View profile</a></div>
<form method="POST" action="{{ route('settings.update') }}" class="settings-layout">
    @csrf @method('PUT')
    <section class="panel form-panel settings-card">
        <div class="section-heading"><span><x-icon name="bell"/></span><div><h2>Notification preferences</h2><p>Choose which scheduled reminders appear in your private notification center.</p></div></div>
        <div class="settings-choice-list">
            <label><input type="hidden" name="profile[preferences][task_reminders]" value="0"><input type="checkbox" name="profile[preferences][task_reminders]" value="1" @checked((bool) ($preferences['task_reminders'] ?? true))><span><strong>Task reminders</strong><small>Deadlines, overdue assignments, and work that needs your attention.</small></span></label>
            <label><input type="hidden" name="profile[preferences][dues_reminders]" value="0"><input type="checkbox" name="profile[preferences][dues_reminders]" value="1" @checked((bool) ($preferences['dues_reminders'] ?? true))><span><strong>Dues reminders</strong><small>Upcoming and outstanding membership dues linked to your account.</small></span></label>
        </div>
    </section>
    <section class="panel form-panel settings-card" id="security">
        <div class="section-heading"><span><x-icon name="shieldCheck"/></span><div><h2>Password &amp; security</h2><p>Use your current password to set a new password for this account.</p></div></div>
        <div class="form-grid settings-password-grid">
            <x-field name="current_password" label="Current password" type="password" autocomplete="current-password"/>
            <span></span>
            <x-field name="password" label="New password (at least 10 characters)" type="password" minlength="10" autocomplete="new-password"/>
            <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password"/>
        </div>
    </section>
    <div class="settings-save-bar"><p>These settings apply only to your signed-in account.</p><button class="btn primary"><x-icon name="save" /> Save settings</button></div>
</form>
@endsection
