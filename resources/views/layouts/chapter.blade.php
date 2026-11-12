<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(request()->attributes->get('workspace'))
    <meta name="workspace-id" content="{{ request()->attributes->get('workspace') }}">
    <meta name="new-account-url" content="{{ request()->getBaseUrl() }}/login">
    <style>html.workspace-tab-pending body { visibility: hidden; }</style>
    <script>document.documentElement.classList.add('workspace-tab-pending'); setTimeout(() => document.documentElement.classList.remove('workspace-tab-pending'), 700);</script>
    <script src="{{ asset('assets/js/workspace-tab.js') }}?v={{ filemtime(public_path('assets/js/workspace-tab.js')) }}"></script>
    @endif
    <title>@yield('title', 'Workspace') · JCISTEM</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/brand/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/brand/favicon.png') }}">
    @include('shared.public-fonts')
    <link rel="stylesheet" href="{{ asset('assets/css/chapter.css') }}?v={{ filemtime(public_path('assets/css/chapter.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/jci-theme.css') }}?v={{ filemtime(public_path('assets/css/jci-theme.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/theme-ui.css') }}?v={{ filemtime(public_path('assets/css/theme-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/project-documents.css') }}?v={{ filemtime(public_path('assets/css/project-documents.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/project-workflow.css') }}?v={{ filemtime(public_path('assets/css/project-workflow.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard-charts.css') }}?v={{ filemtime(public_path('assets/css/dashboard-charts.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/project-tracker.css') }}?v={{ filemtime(public_path('assets/css/project-tracker.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/project-status.css') }}?v={{ filemtime(public_path('assets/css/project-status.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/task-ui.css') }}?v={{ filemtime(public_path('assets/css/task-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/empty-state-ui.css') }}?v={{ filemtime(public_path('assets/css/empty-state-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/calendar-ui.css') }}?v={{ filemtime(public_path('assets/css/calendar-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/filter-ui.css') }}?v={{ filemtime(public_path('assets/css/filter-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/notification-ui.css') }}?v={{ filemtime(public_path('assets/css/notification-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/overlay-ui.css') }}?v={{ filemtime(public_path('assets/css/overlay-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('assets/css/navigation-ui.css') }}?v={{ filemtime(public_path('assets/css/navigation-ui.css')) }}">
    <script type="module" src="{{ asset('assets/js/chapter.js') }}?v={{ filemtime(public_path('assets/js/chapter.js')) }}"></script>
    <script src="{{ asset('assets/js/back-navigation.js') }}?v={{ filemtime(public_path('assets/js/back-navigation.js')) }}" defer></script>
</head>
@php
    $user = auth()->user();
    $workspacePreferences = $user?->profile['preferences'] ?? [];
    $notificationCounts = \App\Models\Notification::where('user_id', $user->id)->whereNull('read_at')
        ->selectRaw('COUNT(*) AS unread_count, COALESCE(SUM(CASE WHEN requires_open = 1 THEN 1 ELSE 0 END), 0) AS important_count')->first();
    $unread = (int) $notificationCounts->unread_count;
    $importantUnread = (int) $notificationCounts->important_count;
    $recentNotifications = \App\Models\Notification::where('user_id', $user->id)
        ->orderByRaw('CASE WHEN requires_open = 1 AND read_at IS NULL THEN 0 ELSE 1 END')
        ->latest()
        ->get();
    $roleLabel = \App\Support\WorkspaceNav::label($user->role);
    $navigation = \App\Support\PmsNavigation::for($user);
    $workspaceNotice = \Illuminate\Support\Facades\Cache::remember('jci.workspace_notice', 300, fn () => \App\Models\ChapterSetting::where('key', 'workspace_notice')->value('value') ?? '');
    $systemTheme = \Illuminate\Support\Facades\Cache::remember('jci.workspace_theme', 300, fn () => \App\Models\ChapterSetting::where('key', 'workspace_theme')->value('value') ?? 'light');
    $normalizeTheme = static fn ($theme) => in_array($theme, ['dark', 'navy'], true) ? 'dark' : 'light';
    $preferredTheme = $workspacePreferences['color_theme'] ?? null;
    $effectiveTheme = in_array($preferredTheme, ['light', 'dark'], true) ? $preferredTheme : $normalizeTheme($systemTheme);
    $legacyThemeClass = in_array($preferredTheme, ['jci', 'teal', 'navy'], true)
        ? 'workspace-theme-'.$preferredTheme
        : (in_array($preferredTheme, [null, 'system'], true) && in_array($systemTheme, ['jci', 'teal', 'navy'], true) ? 'workspace-theme-'.$systemTheme : '');
    $activeGroup = collect($navigation)->search(fn ($items) => collect($items)->contains(fn ($item) => \App\Support\PmsNavigation::active($item)));
@endphp
<body class="workspace-density-{{ $workspacePreferences['interface_density'] ?? 'comfortable' }} workspace-theme-{{ $effectiveTheme }} {{ $legacyThemeClass }} {{ !empty($workspacePreferences['reduce_motion']) ? 'workspace-reduce-motion' : '' }}" data-navigation-mode="{{ $workspacePreferences['navigation_mode'] ?? 'accordion' }}" data-chapter-theme="{{ $normalizeTheme($systemTheme) }}">
<a class="skip-link" href="#content">Skip to content</a>
<div class="app-shell">
    <aside class="app-sidebar" id="navigation" aria-label="Workspace navigation">
        <div class="sidebar-mobile-header">
            @include('shared.brand')
            <button class="sidebar-mobile-close" type="button" data-nav-close aria-label="Close navigation menu" title="Close menu"><x-icon name="close" /></button>
        </div>
        <div class="workspace-label"><span class="live-dot"></span><span>{{ $roleLabel }} workspace</span><span>PH</span></div>
        <nav aria-label="Main navigation">
            <div class="sidebar-primary" aria-label="Monitoring and overview">
                <a class="side-link sidebar-primary-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif><x-icon name="dashboard"/><span>Dashboard</span></a>
            </div>
            @foreach($navigation as $group => $items)
                @php
                    $groupOpen = collect($items)->contains(fn ($item) => \App\Support\PmsNavigation::active($item));
                    $direct = \App\Support\PmsNavigation::direct($group);
                @endphp
                @if($direct)
                    @php
                        [$route, $label, $icon, $params] = $items[0];
                    @endphp
                    <a class="side-link nav-direct-link {{ $groupOpen ? 'is-active' : '' }}" href="{{ \App\Support\PmsNavigation::url($items[0]) }}" @if($groupOpen) aria-current="page" @endif>
                        <x-icon :name="$icon"/><span>{{ $label }}</span>
                        @if($route === 'notifications' && $unread)<b>{{ $unread }}</b>@endif
                    </a>
                @else
                <details class="nav-section" @if(($workspacePreferences['navigation_mode'] ?? 'accordion') !== 'expanded') name="sidebar-menu" @endif @if($groupOpen || ($workspacePreferences['navigation_mode'] ?? 'accordion') === 'expanded') open @endif>
                    <summary><x-icon :name="\App\Support\PmsNavigation::icon($group)"/><span>{{ \App\Support\PmsNavigation::label($group) }}</span></summary>
                    <div class="nav-items">
                    @foreach($items as [$route, $label, $icon, $params])
                        @php
                            $item = [$route, $label, $icon, $params];
                            $active = \App\Support\PmsNavigation::active($item);
                        @endphp
                        @if($route)
                        <a class="side-link {{ $active ? 'is-active' : '' }}" href="{{ \App\Support\PmsNavigation::url($item) }}" @if($active) aria-current="page" @endif>
                            <x-icon :name="$icon"/>
                            <span>{{ $label }}</span>
                        </a>
                        @else
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="side-link side-logout" type="submit"><x-icon :name="$icon"/><span>{{ $label }}</span></button></form>
                        @endif
                    @endforeach
                    </div>
                </details>
                @endif
            @endforeach
        </nav>
        <form class="sidebar-logout-form" method="POST" action="{{ route('logout') }}">@csrf<button class="side-link side-logout" type="submit"><x-icon name="logout"/><span>Sign out</span></button></form>
        <div class="sidebar-account">
            <a class="sidebar-profile-link {{ request()->routeIs('account') ? 'is-active' : '' }}" href="{{ route('account') }}" aria-label="My Profile: {{ $user->name }}" @if(request()->routeIs('account')) aria-current="page" @endif>
                <span class="sidebar-profile-avatar" aria-hidden="true">@if(!empty($user->profile['photo_path']))<img src="{{ route('account.photo') }}" alt="">@else{{ strtoupper(mb_substr($user->name, 0, 1)) }}@endif</span>
                <span class="sidebar-account-identity"><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span>
            </a>
        </div>
    </aside>
    <button class="nav-overlay" type="button" aria-label="Close navigation"></button>
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-context">
                <button class="menu-button" type="button" aria-controls="navigation" aria-expanded="false" aria-label="Open navigation"><x-icon name="menu" /></button>
                <span><a href="{{ route('dashboard') }}">Dashboard</a>@if($activeGroup && !\App\Support\PmsNavigation::direct($activeGroup))<span class="muted">/</span><span class="topbar-group">{{ \App\Support\PmsNavigation::label($activeGroup) }}</span>@endif @unless(request()->routeIs('dashboard'))<span class="muted">/</span><strong>@yield('title', 'Overview')</strong>@endunless</span>
            </div>
            <div class="topbar-right">
                <span class="today" aria-label="Today">{{ now()->format('D, d M Y') }}</span>
                <details class="topbar-settings-menu topbar-notification-menu">
                    <summary class="notification-link" aria-label="Notifications{{ $unread ? ' ('.$unread.' unread)' : '' }}" aria-controls="notification-dropdown" aria-expanded="false" title="Notifications"><x-icon name="bell" />@if($unread)<b>{{ $unread > 9 ? '9+' : $unread }}</b>@endif</summary>
                    <div class="topbar-menu-panel topbar-notification-panel" id="notification-dropdown">
                        <div class="topbar-notification-heading"><span>Notifications</span><a class="topbar-notification-view-all" href="{{ route('notifications') }}">View all</a></div>
                        @forelse($recentNotifications as $notification)
                            <div class="topbar-notification-item {{ $notification->read_at ? 'is-read' : 'is-unread' }} {{ $notification->requires_open ? 'is-important' : '' }}">
                            <form method="POST" action="{{ route('notifications.open', $notification) }}" class="topbar-notification-open-form">@csrf
                                <button type="submit" class="topbar-notification-static"><span class="topbar-notification-title">{{ $notification->title }}@if($notification->requires_open && !$notification->read_at)<em>Important</em>@endif</span><span class="topbar-notification-body">{{ $notification->body }}</span><small>{{ $notification->created_at->diffForHumans() }}@if($notification->requires_open && !$notification->read_at) · Open to mark read @endif</small></button>
                            </form>
                            </div>
                        @empty
                            <p class="topbar-notification-empty">You're all caught up. New updates will appear here.</p>
                        @endforelse
                    </div>
                </details>
                <details class="topbar-settings-menu topbar-profile-menu">
                    <summary class="topbar-profile-trigger" aria-label="Account menu" aria-controls="account-dropdown" aria-expanded="false"><span class="avatar small">@if(!empty($user->profile['photo_path']))<img src="{{ route('account.photo') }}" alt="">@else{{ strtoupper(mb_substr($user->name, 0, 1)) }}@endif</span></summary>
                    <div class="topbar-menu-panel" id="account-dropdown">
                        <span class="topbar-menu-label">{{ $user->name }}<small>{{ $roleLabel }}@if($user->member_no) &middot; {{ $user->member_no }}@endif</small></span>
                        <a href="{{ route('account') }}" @if(request()->routeIs('account')) aria-current="page" @endif><x-icon name="user" /> My account</a>
                        <a href="{{ route('settings') }}"><x-icon name="settings" /> Workspace settings</a>
                        @if($user->role === 'member')<a href="{{ route('tasks', ['mine' => 1]) }}"><x-icon name="checklist" /> My assigned tasks</a>
                        @elseif($user->role === 'treasurer')<a href="{{ route('expense-claims.index', ['status' => 'pending']) }}"><x-icon name="receipt" /> Claims to review</a>
                        @elseif($user->role === 'bod')<a href="{{ route('projects', ['filter' => 'board']) }}"><x-icon name="fileSignature" /> BOD agenda</a>
                        @else<a href="{{ route('projects', ['filter' => 'review']) }}"><x-icon name="fileSignature" /> Project reviews</a>@endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-icon name="logout" /> Sign out</button></form>
                    </div>
                </details>
            </div>
        </header>
        <main class="app-content" id="content">
            @if($workspaceNotice)<div class="workspace-notice" role="note"><x-icon name="bell"/><span>{{ $workspaceNotice }}</span></div>@endif
            @if(session('success'))<div class="app-toast-stack" aria-label="Notification"><div class="app-toast" role="status" data-toast><x-icon name="check"/><span>{{ session('success') }}</span><button type="button" aria-label="Dismiss notification" data-toast-dismiss><x-icon name="close"/></button></div></div>@endif
            @if($errors->any() && !old('_task_dialog'))
                <div class="alert error" role="alert">
                    <strong>Please check the following:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
        <footer class="app-footer">
            <span>JCISTEM · Carmona City, Cavite</span>
            <span>Developing leaders. Creating positive change.</span>
        </footer>
    </div>
</div>
<dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message">
    <div class="confirm-dialog-inner">
        <span class="confirm-dialog-icon"><x-icon name="shieldCheck"/></span>
        <h2 id="confirm-dialog-title">Confirm this change</h2>
        <p id="confirm-dialog-message"></p>
        <div class="confirm-dialog-actions"><button class="btn secondary" type="button" data-dialog-cancel><x-icon name="close" /> Cancel</button><button class="btn primary" type="button" data-dialog-confirm><x-icon name="check" /> <span data-confirm-label>Continue</span></button></div>
    </div>
</dialog>
@php
    $feedbackType = session('error') ? 'error' : (session('warning') ? 'warning' : null);
    $feedbackMessage = $feedbackType ? session($feedbackType) : null;
@endphp
@if($feedbackType)
<dialog class="feedback-dialog is-{{ $feedbackType }}" id="feedback-dialog" aria-labelledby="feedback-title" aria-describedby="feedback-message" data-feedback-dialog>
    <div class="feedback-dialog-inner">
        <span class="feedback-dialog-icon" aria-hidden="true"><x-icon name="info" /></span>
        <span class="eyebrow">PLEASE REVIEW</span>
        <h2 id="feedback-title">{{ $feedbackType === 'error' ? 'Something needs attention' : 'Please check this' }}</h2>
        <p id="feedback-message">{{ $feedbackMessage }}</p>
        <button class="btn primary" type="button" data-feedback-close>Continue</button>
    </div>
</dialog>
@endif
</body>
</html>
