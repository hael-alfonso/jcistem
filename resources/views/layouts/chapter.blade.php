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
<body>
@php
    $user = auth()->user();
    $unread = \App\Models\Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    $roleLabel = \App\Support\WorkspaceNav::label($user->role);
    $navigation = \App\Support\PmsNavigation::for($user);
    $activeGroup = collect($navigation)->search(fn ($items) => collect($items)->contains(fn ($item) => \App\Support\PmsNavigation::active($item)));
@endphp
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
                <details class="nav-section" name="sidebar-menu" @if($groupOpen) open @endif>
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
        <div class="sidebar-account">
            <div class="sidebar-account-identity">
                <span class="avatar">@if(!empty($user->profile['photo_path']))<img src="{{ route('account.photo') }}" alt="">@else{{ strtoupper(mb_substr($user->name, 0, 1)) }}@endif</span>
                <span><strong>{{ $user->name }}</strong><small>{{ $roleLabel }}</small></span>
            </div>
        </div>
    </aside>
    <button class="nav-overlay" type="button" aria-label="Close navigation"></button>
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-context">
                <button class="menu-button" type="button" aria-controls="navigation" aria-expanded="false" aria-label="Open navigation"><x-icon name="menu" /></button>
                <span><a href="{{ route('dashboard') }}">Dashboard</a>@if($activeGroup)<span class="muted">/</span><span class="topbar-group">{{ \App\Support\PmsNavigation::label($activeGroup) }}</span>@endif @unless(request()->routeIs('dashboard'))<span class="muted">/</span><strong>@yield('title', 'Overview')</strong>@endunless</span>
            </div>
            <div class="topbar-right">
                <details class="topbar-settings-menu topbar-quick-menu">
                    <summary aria-label="Open quick actions"><x-icon name="plus" /></summary>
                    <div class="topbar-menu-panel">
                        <span class="topbar-menu-label">Quick actions</span>
                        <a href="{{ route('projects.create') }}"><x-icon name="filePlus" /> Submit new project</a>
                        <a href="{{ route('tasks', ['mine' => 1]) }}"><x-icon name="checklist" /> My assigned tasks</a>
                        @if(in_array($user->role, ['admin','treasurer','bod'], true))<a href="{{ route('projects', ['filter' => 'review']) }}"><x-icon name="clipboardCheck" /> Open review queue</a>@endif
                        @if($user->role === 'treasurer')<a href="{{ route('ledger') }}"><x-icon name="wallet" /> Open master ledger</a>@endif
                        <a href="{{ route('calendar') }}"><x-icon name="calendar" /> Open calendar</a>
                    </div>
                </details>
                <span class="today">{{ now()->format('D, d M Y') }}</span>
                <span class="topbar-role">{{ $roleLabel }}</span>
            </div>
        </header>
        <main class="app-content" id="content">
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
