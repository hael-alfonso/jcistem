@extends('layouts.chapter')
@section('title', 'System Controls')
@section('content')
<div class="page-heading"><div><div class="eyebrow">SETTINGS</div><h1>System Controls</h1><p>Chapter-wide options for reports, workspace notices, and project evidence.</p></div></div>
<form class="panel form-panel form-grid" method="POST" action="{{ route('pms.settings.update') }}">@csrf @method('PUT')
<div class="full"><h2>Chapter configuration</h2><p class="hint">Changes here affect all members and are recorded in the Activity Log.</p></div>
<x-field name="report_chapter_name" label="Chapter name on generated reports" :value="$chapterName" required/>
<label class="field"><span>Photos allowed per evidence upload</span><input type="number" name="evidence_photo_limit" min="1" max="8" value="{{ old('evidence_photo_limit', $evidencePhotoLimit) }}" required><small>Between 1 and 8 images. Each image may be up to 10 MB.</small></label>
<label class="field"><span>Default workspace color</span><select name="workspace_theme"><option value="jci" @selected(old('workspace_theme', $workspaceTheme) === 'jci')>JCI Blue (default)</option><option value="teal" @selected(old('workspace_theme', $workspaceTheme) === 'teal')>Carmona Teal</option><option value="navy" @selected(old('workspace_theme', $workspaceTheme) === 'navy')>Deep Navy</option></select><small>Members following the system theme see this palette. Select JCI Blue to restore the original.</small></label>
<label class="field full"><span>Workspace notice</span><textarea name="workspace_notice" maxlength="500" rows="3" placeholder="Optional chapter-wide update for members">{{ old('workspace_notice', $workspaceNotice) }}</textarea><small>Leave empty to hide the notice.</small></label>
<div class="full"><button class="btn primary" data-confirm="Save these chapter-wide settings? The new values will apply to all members."><x-icon name="save"/> Save system controls</button></div></form>
<section class="panel form-panel"><h2>Related tools</h2><div class="button-row"><a class="btn secondary" href="{{ route('templates') }}"><x-icon name="folder" /> Forms &amp; Templates</a><a class="btn secondary" href="{{ route('activity-log') }}"><x-icon name="clock" /> Activity Log</a><a class="btn secondary" href="{{ route('accounts') }}"><x-icon name="users" /> User Accounts</a></div></section>
@endsection
