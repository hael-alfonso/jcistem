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
    <link rel="stylesheet" href="{{ asset('assets/css/project-documents.css') }}?v={{ filemtime(public_path('assets/css/project-documents.css')) }}">
    <script type="module" src="{{ asset('assets/js/chapter.js') }}?v={{ filemtime(public_path('assets/js/chapter.js')) }}"></script>
</head>
@php
    $user = auth()->user();
    $workspacePreferences = $user?->profile['preferences'] ?? [];
    $unread = \App\Models\Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    $recentNotifications = \App\Models\Notification::where('user_id', $user->id)->latest()->limit(4)->get();
    $roleLabel = \App\Support\WorkspaceNav::label($user->role);
    $navigation = \App\Support\PmsNavigation::for($user);
    $workspaceNotice = \Illuminate\Support\Facades\Cache::remember('jci.workspace_notice', 300, fn () => \App\Models\ChapterSetting::where('key', 'workspace_notice')->value('value') ?? '');
    $systemTheme = \Illuminate\Support\Facades\Cache::remember('jci.workspace_theme', 300, fn () => \App\Models\ChapterSetting::where('key', 'workspace_theme')->value('value') ?? 'jci');
    $preferredTheme = $workspacePreferences['color_theme'] ?? 'system';
    $effectiveTheme = in_array($preferredTheme, ['jci', 'teal', 'navy'], true) ? $preferredTheme : (in_array($systemTheme, ['jci', 'teal', 'navy'], true) ? $systemTheme : 'jci');
    $activeGroup = collect($navigation)->search(fn ($items) => collect($items)->contains(fn ($item) => \App\Support\PmsNavigation::active($item)));
@endphp
<body class="workspace-density-{{ $workspacePreferences['interface_density'] ?? 'comfortable' }} workspace-theme-{{ $effectiveTheme }} {{ !empty($workspacePreferences['reduce_motion']) ? 'workspace-reduce-motion' : '' }}" data-navigation-mode="{{ $workspacePreferences['navigation_mode'] ?? 'accordion' }}">
<a class="skip-link" href="#content">Skip to content</a>
<div class="app-shell">
    <aside class="app-sidebar" id="navigation" aria-label="Workspace navigation">
        @include('shared.brand')
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
                        <div class="topbar-notification-heading"><span>Notifications</span><a href="{{ route('notifications') }}">View all</a></div>
                        @forelse($recentNotifications as $notification)
                            <form class="topbar-notification-item {{ $notification->read_at ? 'is-read' : 'is-unread' }}" method="POST" action="{{ route('notifications.open', $notification) }}">@csrf
                                <button type="submit"><span class="topbar-notification-title">{{ $notification->title }}</span><span class="topbar-notification-body">{{ $notification->body }}</span><small>{{ $notification->created_at->diffForHumans() }}</small></button>
                            </form>
                        @empty
                            <p class="topbar-notification-empty">You're all caught up. New updates will appear here.</p>
                        @endforelse
                    </div>
                </details>
                <details class="topbar-settings-menu topbar-profile-menu">
                    <summary class="topbar-profile-trigger" aria-label="Account menu" aria-controls="account-dropdown" aria-expanded="false"><span class="avatar small">@if(!empty($user->profile['photo_path']))<img src="{{ route('account.photo') }}" alt="">@else{{ strtoupper(mb_substr($user->name, 0, 1)) }}@endif</span></summary>
                    <div class="topbar-menu-panel" id="account-dropdown">
                        <span class="topbar-menu-label">{{ $user->name }}<small>{{ $roleLabel }}</small></span>
                        <a href="{{ route('account') }}" @if(request()->routeIs('account')) aria-current="page" @endif><x-icon name="user" /> My Profile</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-icon name="logout" /> Sign out</button></form>
                    </div>
                </details>
            </div>
        </header>
        <main class="app-content" id="content">
            @if($workspaceNotice)<div class="workspace-notice" role="note"><x-icon name="bell"/><span>{{ $workspaceNotice }}</span></div>@endif
            @if(session('success'))<div class="app-toast-stack" aria-label="Notification"><div class="app-toast" role="status" data-toast><x-icon name="check"/><span>{{ session('success') }}</span><button type="button" aria-label="Dismiss notification" data-toast-dismiss><x-icon name="close"/></button></div></div>@endif
            @if($errors->any())
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
        <div class="confirm-dialog-actions"><button class="btn secondary" type="button" data-dialog-cancel><x-icon name="close" /> Cancel</button><button class="btn primary" type="button" data-dialog-confirm><x-icon name="check" /> Continue</button></div>
    </div>
</dialog>
</body>
</html>
