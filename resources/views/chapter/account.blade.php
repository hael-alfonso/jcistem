@extends('layouts.chapter')
@section('title','My Profile')
@section('content')
<div class="page-heading profile-page-heading"><div><div class="eyebrow">JCI CARMONA · MEMBER PROFILE</div><h1>My Profile</h1><p>Manage the personal and membership information shown in your chapter workspace.</p></div></div>
<form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <section class="panel form-panel profile-editor" id="profile">
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
            <div class="full"><div class="section-heading"><span><x-icon name="user"/></span><div><h2>Identity &amp; contact details</h2><p>Use the details JCI Carmona should have on file for you.</p></div></div></div>
            <x-field name="name" label="Full name" :value="$member->name" required/>
            <x-field name="username" label="Username for sign-in" :value="$member->username" required autocomplete="username"/><p class="hint">You can sign in with either this username or your email address.</p><x-field name="email" label="Email address" type="email" :value="$member->email" required autocomplete="email"/>
            <x-field name="profile[nickname]" label="Nickname" :value="$member->profile['nickname'] ?? ''"/>
            <x-field name="profile[phone]" label="Contact number" :value="$member->profile['phone'] ?? ''"/>
            <x-field name="profile[address]" label="Address" type="textarea" :value="$member->profile['address'] ?? ''"/>
            @if(!empty($member->profile['occupation']) || !empty($member->profile['organization']) || !empty($member->profile['interests']))
                <div class="full"><h2>Application details</h2><p class="hint">Details you provided when you applied for membership.</p><p><strong>Current status:</strong> {{ $member->profile['occupation'] ?? 'Not provided' }} · <strong>School or organization:</strong> {{ $member->profile['organization'] ?? 'Not provided' }}</p><p><strong>Areas of interest:</strong> {{ implode(', ', $member->profile['interests'] ?? []) ?: 'Not provided' }}</p></div>
            @endif
            <div class="full profile-form-actions"><button class="btn primary"><x-icon name="save" /> Save profile</button></div>
        </div>
    </section>
</form>
@endsection
