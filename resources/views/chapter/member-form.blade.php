@extends('layouts.chapter')
@section('title', $member->exists ? 'Manage member' : 'Register member')
@section('content')
@php
    $profile = $member->profile ?? [];
    $nameParts = preg_split('/\s+/', trim($member->name ?? ''), 2);
    $givenName = $profile['given_name'] ?? (isset($nameParts[1]) ? $nameParts[0] : '');
    $familyName = $profile['family_name'] ?? ($nameParts[1] ?? '');
    $isAdmin = auth()->user()->role === 'admin';
@endphp
<div class="page-heading"><div><span class="eyebrow">JCI CARMONA MEMBERSHIP</span><h1>{{ $member->exists ? 'Manage member' : 'Register member' }}</h1><p>Record the member's application details, interests, and chapter access in one place.</p></div><a class="btn secondary" href="{{ route('members') }}"><x-icon name="arrowLeft" /> Member directory</a></div>
<form class="membership-form" method="POST" action="{{ $member->exists ? route('members.update', $member) : route('members.store') }}" enctype="multipart/form-data">
    @csrf @if($member->exists) @method('PUT') @endif
    <input type="hidden" name="name" value="{{ old('name', $member->name) }}">
    <input type="hidden" name="membership_interests_present" value="1">
    <section class="panel form-panel membership-section" aria-labelledby="member-personal"><div class="membership-section-heading"><span>01</span><div><h2 id="member-personal">Personal and contact information</h2><p>Use the member's name as shown on their application.</p></div></div>
        <div class="membership-photo-row"><span class="membership-photo-preview">@if($member->exists && !empty($profile['photo_path']))<img src="{{ route('members.photo', $member) }}?v={{ $member->updated_at?->timestamp }}" width="64" height="64" alt="" loading="lazy" decoding="async">@else{{ strtoupper(mb_substr($member->name ?: '?', 0, 1)) }}@endif</span><div><strong>Profile photo</strong><p>Optional JPG, PNG, or WebP image, up to 2 MB.</p><x-field name="photo" label="Choose a photo" type="file" accept="image/jpeg,image/png,image/webp"/></div></div>
        <div class="form-grid"><x-field name="profile[title]" label="Title" type="select" :options="['Mr.'=>'Mr.','Ms.'=>'Ms.','Mrs.'=>'Mrs.','Dr.'=>'Dr.','Other'=>'Other']" :value="$profile['title'] ?? ''"/>
            <x-field name="profile[given_name]" label="First / given name" :value="$givenName" :required="!$member->exists"/>
            <x-field name="profile[family_name]" label="Surname / family name" :value="$familyName" :required="!$member->exists"/>
            <x-field name="profile[nickname]" label="Nickname (optional)" :value="$profile['nickname'] ?? ''"/>
            <x-field name="profile[date_of_birth]" label="Date of birth" type="date" :value="$profile['date_of_birth'] ?? ''"/>
            <x-field name="profile[phone]" label="Mobile number" type="tel" :value="$profile['phone'] ?? ''"/>
            <x-field name="profile[home_phone]" label="Home phone (optional)" type="tel" :value="$profile['home_phone'] ?? ''"/>
            <x-field name="profile[city]" label="City / town" :value="$profile['city'] ?? ''"/>
            <x-field name="profile[address]" label="Home address" type="textarea" :value="$profile['address'] ?? ''"/>
        </div>
    </section>
    <section class="panel form-panel membership-section" aria-labelledby="member-background"><div class="membership-section-heading"><span>02</span><div><h2 id="member-background">Occupation and interests</h2><p>Keep these details useful for chapter opportunities and member coordination.</p></div></div>
        <div class="form-grid"><x-field name="profile[occupation]" label="Current status" type="select" :options="['Student'=>'Student','Employed'=>'Employed','Self-employed'=>'Self-employed','Other'=>'Other']" :value="$profile['occupation'] ?? ''"/>
            <x-field name="profile[organization]" label="Employer, business, or university" :value="$profile['organization'] ?? ''"/>
            <x-field name="profile[position]" label="Position or role" :value="$profile['position'] ?? ''"/>
            <x-field name="profile[business_sector]" label="Business sector" :value="$profile['business_sector'] ?? ''"/>
            <x-field name="profile[university_major]" label="University course / major" :value="$profile['university_major'] ?? ''"/>
            <div class="full"><fieldset class="membership-interests"><legend>JCI areas of interest</legend><div>@foreach(\App\Support\ChapterForms::AREAS as $interest)<label><input type="checkbox" name="profile[interests][]" value="{{ $interest }}" @checked(in_array($interest, old('profile.interests', $profile['interests'] ?? []), true))><span>{{ $interest }}</span></label>@endforeach</div></fieldset></div>
            <div class="full"><fieldset class="membership-interests"><legend>Activities of interest</legend><div>@foreach(\App\Support\ChapterForms::MEMBERSHIP_INTERESTS as $interest)<label><input type="checkbox" name="profile[interest_topics][]" value="{{ $interest }}" @checked(in_array($interest, old('profile.interest_topics', $profile['interest_topics'] ?? []), true))><span>{{ $interest }}</span></label>@endforeach</div></fieldset></div>
        </div>
    </section>
    <section class="panel form-panel membership-section" aria-labelledby="member-account"><div class="membership-section-heading"><span>03</span><div><h2 id="member-account">Membership and account</h2><p>JCI Carmona member ID is generated automatically when the account is created or approved.</p></div></div>
        <div class="membership-id-note"><strong>JCI Carmona ID</strong><span>{{ $member->member_no ?: 'JCI-CAR-'.now()->format('Y').'-••••• (assigned on save)' }}</span></div>
        <div class="form-grid"><x-field name="profile[joined_on]" label="Membership date" type="date" :value="$profile['joined_on'] ?? ''"/><x-field name="profile[emergency_contact]" label="Emergency contact (optional)" :value="$profile['emergency_contact'] ?? ''"/>
        @if($isAdmin)
            <x-field name="username" label="Username for sign-in" :value="$member->username" required autocomplete="username"/><x-field name="email" label="Email address" type="email" :value="$member->email" required autocomplete="email"/>
            <x-field name="role" label="Permanent account role" type="select" :options="['admin'=>'Admin','bod'=>'Board of Directors','treasurer'=>'Treasurer','member'=>'General Member']" :value="$member->role ?? 'member'" required/>
            <x-field name="status" label="Account status" type="select" :options="['active'=>'Active','inactive'=>'Inactive']" :value="$member->status ?? 'active'" required/>
            <p class="hint full">General-member accounts inactive for one full year move automatically to Removed Accounts. Admin, Treasurer, and BOD accounts are not removed by this rule. Restoring an account starts a new inactive period.</p>
            <x-field name="password" :label="$member->exists ? 'New password (leave blank to keep current)' : 'Initial password'" type="password" :required="!$member->exists" minlength="10" autocomplete="new-password"/>
            <x-field name="password_confirmation" label="Confirm password" type="password" :required="!$member->exists" autocomplete="new-password"/>
        @else
            <div class="full membership-account-note">Account role, sign-in details, and activation remain with the chapter administrator.</div>
        @endif
        </div>
    </section>
    <div class="membership-actions"><a class="btn secondary" href="{{ route('members') }}">Cancel</a><button class="btn primary" type="submit"><x-icon name="save" /> Save member details</button></div>
</form>
@if($isAdmin && $member->exists && $member->id !== auth()->id())<details class="panel form-panel account-remove-panel"><summary class="large-summary"><x-icon name="info"/> Remove account</summary><p>Remove this account from sign-in and member lists. Past project, payment, and activity records stay available. The account can be restored as inactive.</p><form method="POST" action="{{ route('members.delete', $member) }}">@csrf @method('DELETE')<label class="field"><span>Reason for removal <em aria-hidden="true">*</em></span><textarea name="reason" rows="3" maxlength="2000" required></textarea></label><button class="btn danger" type="submit" data-confirm="Remove this account from active use? Its chapter history will be preserved.">Remove account</button></form></details>@endif
@endsection
