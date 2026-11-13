@extends('layouts.chapter')
@section('title','My Profile')
@section('content')
<div class="page-heading profile-page-heading"><div><div class="eyebrow">JCI CARMONA · MEMBER PROFILE</div><h1>My Profile</h1><p>Manage the personal and membership information shown in your chapter workspace.</p></div></div>
<form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <section class="panel form-panel profile-editor" id="profile">
        <div class="profile-membership-summary" aria-label="Membership details">
            <div><span>JCI Carmona ID</span><strong>{{ $member->member_no ?: 'Not assigned yet' }}</strong></div>
            <div><span>Workspace role</span><strong>{{ ['admin' => 'Administrator', 'bod' => 'Board of Directors', 'treasurer' => 'Treasurer', 'member' => 'General Member'][$member->role] ?? ucfirst($member->role) }}</strong></div>
            <div><span>Account status</span><strong>{{ ucfirst($member->status) }}</strong></div>
            <div><span>Member since</span><strong>{{ !empty($member->profile['joined_on']) ? \Illuminate\Support\Carbon::parse($member->profile['joined_on'])->format('M d, Y') : $member->created_at?->format('M d, Y') }}</strong></div>
        </div>
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
            <x-field name="profile[title]" label="Title" :value="$member->profile['title'] ?? ''"/>
            <x-field name="profile[date_of_birth]" label="Date of birth" type="date" :value="$member->profile['date_of_birth'] ?? ''"/>
            <x-field name="profile[phone]" label="Contact number" :value="$member->profile['phone'] ?? ''"/>
            <x-field name="profile[address]" label="Address" type="textarea" :value="$member->profile['address'] ?? ''"/>
            <div class="full"><div class="section-heading"><span><x-icon name="briefcase"/></span><div><h2>Membership background</h2><p>Keep your role and organization details current for chapter coordination.</p></div></div></div>
            <div><label for="profile-occupation">Current status</label><select id="profile-occupation" name="profile[occupation]"><option value="">Select if applicable</option>@foreach(['Student', 'Employed', 'Self-employed', 'Other'] as $option)<option value="{{ $option }}" @selected(old('profile.occupation', $member->profile['occupation'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></div>
            <x-field name="profile[organization]" label="School, employer, or business" :value="$member->profile['organization'] ?? ''"/>
            <x-field name="profile[position]" label="Current position or role" :value="$member->profile['position'] ?? ''"/>
            <x-field name="profile[business_sector]" label="Business sector" :value="$member->profile['business_sector'] ?? ''"/>
            <x-field name="profile[university_major]" label="University / course or major" :value="$member->profile['university_major'] ?? ''"/>
            <x-field name="profile[emergency_contact]" label="Emergency contact" :value="$member->profile['emergency_contact'] ?? ''"/>
            <div class="full"><strong>Areas of interest</strong><p class="hint">{{ implode(', ', $member->profile['interests'] ?? []) ?: 'Not provided' }}</p></div>
            <div class="full profile-form-actions"><button class="btn primary"><x-icon name="save" /> Save profile</button></div>
        </div>
    </section>
</form>
<section class="panel form-panel profile-password-panel" id="password" aria-labelledby="password-title">
    <div class="section-heading"><span><x-icon name="shieldCheck"/></span><div><h2 id="password-title">Change password</h2><p>Only you can change your sign-in password. Enter your current password to confirm it is you.</p></div></div>
    <form method="POST" action="{{ route('account.password') }}" class="form-grid">@csrf @method('PUT')
        <div class="full profile-password-current"><x-field name="current_password" label="Current password" type="password" required autocomplete="current-password"/></div>
        <x-field name="password" label="New password" type="password" required autocomplete="new-password" minlength="10"/>
        <x-field name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" minlength="10"/>
        <div class="full"><p class="hint">Use at least 10 characters. Your password is never shown in the activity log.</p><button class="btn primary" type="submit">Update password</button></div>
    </form>
</section>
@endsection
