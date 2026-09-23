@extends('layouts.workspace')

@section('content')
<div class="page-head">
    <div class="page-copy">
        <div class="eyebrow">JCI CARMONA • ADMIN WORKSPACE</div>
        <h1>Member Registration</h1>
        <p>JCI Carmona member registration structure based on the master form definitions.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('admin.members') }}"><x-icon name="arrow" /> Back to Members</a>
    </div>
</div>

<x-card title="Member Registration Form" subtitle="Core registration fields">
    <form class="form-grid" id="memberForm">
        <div><label class="field"><span>Full Name</span><input name="fullName" required placeholder="Member name"></label></div>
        <div><label class="field"><span>Nickname</span><input name="nickname" placeholder="Preferred name"></label></div>
        <div><label class="field"><span>Contact Information</span><input name="contact" placeholder="Phone / email"></label></div>
        <div><label class="field"><span>Address</span><input name="address" placeholder="Address"></label></div>
        <div><label class="field"><span>Membership Information</span><textarea name="membership" placeholder="Membership details"></textarea></label></div>
        <div><label class="field"><span>Chapter Information</span><input name="chapter" value="JCI Carmona"></label></div>
        <div>
            <label class="field">
                <span>Profile Photo</span>
                <div class="file-drop">Choose profile photo<input type="file" accept="image/*"></div>
            </label>
        </div>
        <div><label class="field"><span>Emergency / Contact Information</span><input name="emergency"></label></div>
        <div>
            <label class="field">
                <span>Account Status</span>
                <select name="status">
                    <option>Pending</option>
                    <option>Active</option>
                    <option>Inactive</option>
                </select>
            </label>
        </div>
        <div class="full form-actions">
            <button type="button" class="btn btn-secondary" data-toast="Registration saved.">Save Registration</button>
            <button type="submit" class="btn btn-primary">Save &amp; Prepare Account</button>
        </div>
    </form>
</x-card>
@endsection
