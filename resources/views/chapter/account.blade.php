@extends('layouts.chapter')
@section('title','My profile')
@section('content')
<div class="page-heading"><div><div class="eyebrow">JCI CARMONA · MY ACCOUNT</div><h1>My profile</h1><p>Keep your chapter contact details and profile photo current.</p></div></div>
<form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <section class="panel form-panel">
        <div class="profile-photo-row">
            <span class="avatar profile-photo-preview">@if(!empty($member->profile['photo_path']))<img src="{{ route('account.photo') }}" alt="">@else{{ strtoupper(mb_substr($member->name, 0, 1)) }}@endif</span>
            <div class="profile-photo-controls">
                <h2>Profile photo</h2>
                <p>Upload a JPG, PNG, or WebP image up to 2 MB. Your photo appears in your workspace profile and navigation.</p>
                <x-field name="photo" label="Choose profile photo" type="file" accept="image/jpeg,image/png,image/webp"/>
                @if(!empty($member->profile['photo_path']))<label class="check-field"><input type="checkbox" name="remove_photo" value="1"> Remove current photo if no new photo is selected</label>@endif
            </div>
        </div>
        <div class="form-grid">
            <div class="full"><h2>Contact details</h2><p class="hint">Use the details JCI Carmona should have on file for you.</p></div>
            <x-field name="name" label="Full name" :value="$member->name" required/>
            <x-field name="email" label="Email" type="email" :value="$member->email" required/>
            <x-field name="profile[nickname]" label="Nickname" :value="$member->profile['nickname'] ?? ''"/>
            <x-field name="profile[phone]" label="Contact number" :value="$member->profile['phone'] ?? ''"/>
            <x-field name="profile[address]" label="Address" type="textarea" :value="$member->profile['address'] ?? ''"/>
            <div class="full" id="security"><h2>Change password</h2><p class="hint">Leave these fields empty to keep your existing password.</p></div>
            <x-field name="current_password" label="Current password" type="password" autocomplete="current-password"/>
            <x-field name="password" label="New password (at least 10 characters)" type="password" minlength="10" autocomplete="new-password"/>
            <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password"/>
            <div class="full"><button class="btn primary">Save account changes</button></div>
        </div>
    </section>
</form>
@endsection
