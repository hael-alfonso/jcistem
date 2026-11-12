@extends('layouts.chapter')
@section('title','Workspace Settings')
@section('content')
@php
    $preferences = $member->profile['preferences'] ?? [];
    // Normalize preferences saved by the previous four-theme selector.
    $preferences['color_theme'] = ($preferences['color_theme'] ?? 'light') === 'navy' || ($preferences['color_theme'] ?? null) === 'dark' ? 'dark' : 'light';
@endphp
<div class="page-heading profile-page-heading"><div><div class="eyebrow">JCI CARMONA · WORKSPACE PREFERENCES</div><h1>Workspace Settings</h1><p>Choose how the PMS opens, feels, and organizes navigation for your account.</p></div></div>
<form method="POST" action="{{ route('settings.update') }}" class="settings-layout">
    @csrf @method('PUT')
    <section class="panel form-panel settings-card">
        <div class="section-heading"><span><x-icon name="dashboard"/></span><div><h2>Starting workspace</h2><p>Open the part of JCISTEM you use most after signing in.</p></div></div>
        <div class="settings-option-grid">
            @foreach(['dashboard' => ['Dashboard', 'Chapter overview and items needing attention', 'dashboard'], 'projects' => ['My projects', 'Projects you proposed, chair, or support', 'briefcase'], 'tasks' => ['My tasks', 'Assigned work and upcoming deadlines', 'checklist'], 'calendar' => ['Calendar', 'Chapter dates, milestones, and meetings', 'calendar']] as $value => [$label, $description, $icon])
                <label><input type="radio" name="profile[preferences][start_page]" value="{{ $value }}" @checked(($preferences['start_page'] ?? 'dashboard') === $value)><span><x-icon :name="$icon"/><strong>{{ $label }}</strong><small>{{ $description }}</small></span></label>
            @endforeach
        </div>
    </section>
    <section class="panel form-panel settings-card">
        <div class="section-heading"><span><x-icon name="settings"/></span><div><h2>Layout and navigation</h2><p>Use a comfortable view or fit more work on screen without changing your data.</p></div></div>
        <div class="form-grid">
            <x-field name="profile[preferences][interface_density]" label="Content spacing" type="select" :options="['comfortable' => 'Comfortable', 'compact' => 'Compact']" :value="$preferences['interface_density'] ?? 'comfortable'" required/>
            <x-field name="profile[preferences][navigation_mode]" label="Sidebar groups" type="select" :options="['accordion' => 'Open one group at a time', 'expanded' => 'Keep groups open']" :value="$preferences['navigation_mode'] ?? 'accordion'" required/>
        </div>
        <label class="settings-toggle"><input type="hidden" name="profile[preferences][reduce_motion]" value="0"><input type="checkbox" name="profile[preferences][reduce_motion]" value="1" @checked((bool) ($preferences['reduce_motion'] ?? false))><span><strong>Reduce interface motion</strong><small>Minimize panel, dialog, and toast animations in the PMS.</small></span></label>
    </section>
    <section class="panel form-panel settings-card">
        <div class="section-heading"><span><x-icon name="palette"/></span><div><h2>Color theme</h2><p>Choose the original light workspace or a JCI Navy dark workspace. Your choice applies across every module.</p></div></div>
        <div class="settings-theme-options">
            @foreach(['light' => ['Light theme', 'Original plain workspace palette', 'light'], 'dark' => ['Dark mode', 'JCI Navy navigation and dark workspace', 'dark']] as $value => [$label, $description, $swatch])
                <label class="settings-theme-choice"><input type="radio" name="profile[preferences][color_theme]" value="{{ $value }}" @checked(($preferences['color_theme'] ?? 'light') === $value)><span class="theme-swatch theme-swatch-{{ $swatch }}" aria-hidden="true"><i></i><b></b><em></em></span><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span></label>
            @endforeach
        </div>
    </section>
    <div class="settings-save-bar"><p>These options affect only your workspace.</p><div class="button-row"><button class="btn secondary" type="submit" name="reset_preferences" value="1" formnovalidate data-confirm="Restore your workspace layout, navigation, motion, and color theme to the defaults?"><x-icon name="refresh" /> Reset to default</button><button class="btn primary" type="submit"><x-icon name="save" /> Save workspace settings</button></div></div>
</form>
<script>
document.querySelectorAll('input[name="profile[preferences][color_theme]"]').forEach((input) => input.addEventListener('change', () => {
    const theme = input.value === 'dark' ? 'dark' : 'light';
    document.body.classList.remove('workspace-theme-light', 'workspace-theme-dark');
    document.body.classList.add('workspace-theme-' + theme);
}));
</script>
@endsection
