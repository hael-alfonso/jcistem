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
    <script defer src="{{ asset('assets/js/chapter.js') }}?v={{ filemtime(public_path('assets/js/chapter.js')) }}"></script>
</head>
<body>
@php
    $user = auth()->user();
    $unread = \App\Models\Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    $roleLabel = \App\Support\WorkspaceNav::label($user->role);
    $projectMonitoringActive = request()->routeIs('projects*') && !request()->routeIs('projects.liquidation') && request()->query('filter') !== 'concepts';
    $financialMonitoringActive = request()->routeIs('finance', 'projects.liquidation');
    $navigation = [
        'PROJECT WORK' => [
            ['projects', 'Project concepts', 'fileSignature', ['filter' => 'concepts']],
            ['tasks', 'Tasks & milestones', 'checklist', []],
            ['calendar', 'Calendar', 'calendar', []],
        ],
        'DOCUMENTS & REPORTS' => [
            ['documents', 'Project documents', 'folder', []],
            ['records', 'External LOIs', 'fileSignature', ['kind' => 'letters']],
            ['records', 'Project reports', 'report', ['kind' => 'reports']],
        ],
        'FINANCIAL RECORDS' => [
            ['dues', 'My member dues', 'receipt', []],
        ],
        'CHAPTER' => [
            ['members', 'Member directory', 'users', []],
            ['notifications', 'Notifications', 'bell', []],
        ],
    ];
    if ($user->role === 'treasurer') {
        $navigation['FINANCIAL RECORDS'][] = ['ledger', 'Treasurer ledger', 'wallet', []];
        $navigation['FINANCIAL RECORDS'][] = ['ledger', 'Liquidation queue', 'checklist', ['filter' => 'liquidation']];
        $navigation['FINANCIAL RECORDS'][] = ['dues.manage', 'Record member dues', 'receipt', []];
    }
    if ($user->role === 'admin') {
        $navigation['CHAPTER'][] = ['inquiries', 'Public inquiries', 'info', []];
        $navigation['CHAPTER'][] = ['registrations', 'Registrations', 'userPlus', []];
        $navigation['CHAPTER'][] = ['members.create', 'Register member', 'userPlus', []];
        $navigation['CHAPTER'][] = ['activity-log', 'Activity log', 'clock', []];
    }
@endphp
<a class="skip-link" href="#content">Skip to content</a>
<div class="app-shell">
    <aside class="app-sidebar" id="navigation" aria-label="Workspace navigation">
        @include('shared.brand')
        <div class="workspace-label"><span class="live-dot"></span><span>{{ $roleLabel }} workspace</span><span>PH</span></div>
        <nav aria-label="Main navigation">
            <div class="sidebar-primary" aria-label="Monitoring and overview">
                <a class="side-link sidebar-primary-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif><x-icon name="dashboard"/><span>Dashboard</span></a>
                <a class="side-link sidebar-primary-link {{ $projectMonitoringActive ? 'is-active' : '' }}" href="{{ route('projects') }}" @if($projectMonitoringActive) aria-current="page" @endif><x-icon name="briefcase"/><span>Project monitoring</span></a>
                <a class="side-link sidebar-primary-link {{ $financialMonitoringActive ? 'is-active' : '' }}" href="{{ route('finance') }}" @if($financialMonitoringActive) aria-current="page" @endif><x-icon name="chart"/><span>Financial monitoring</span></a>
            </div>
            @foreach($navigation as $group => $items)
                @php
                    $groupOpen = match ($group) {
                        'PROJECT WORK' => request()->routeIs('tasks', 'calendar') || (request()->routeIs('projects') && request()->query('filter') === 'concepts'),
                        'DOCUMENTS & REPORTS' => request()->routeIs('documents', 'records*'),
                        'FINANCIAL RECORDS' => request()->routeIs('ledger*', 'dues*'),
                        'CHAPTER' => request()->routeIs('members*', 'registrations*', 'notifications', 'inquiries*', 'activity-log', 'audit'),
                        default => false,
                    };
                @endphp
                <details class="nav-section" name="sidebar-menu" @if($groupOpen) open @endif>
                    <summary>{{ $group }}</summary>
                    <div class="nav-items">
                    @foreach($items as [$route, $label, $icon, $params])
                        @php
                            $active = match (true) {
                                $route === 'projects' => request()->routeIs('projects') && request()->query('filter') === 'concepts',
                                $route === 'records' => request()->routeIs('records*') && request()->route('kind') === $params['kind'],
                                $route === 'ledger' && !$params => request()->routeIs('ledger*') && request()->query('filter') !== 'liquidation',
                                $route === 'ledger' => request()->routeIs('ledger') && request()->query('filter') === 'liquidation',
                                default => request()->routeIs($route),
                            };
                        @endphp
                        <a class="side-link {{ $active ? 'is-active' : '' }}" href="{{ route($route, $params) }}" @if($active) aria-current="page" @endif>
                            <x-icon :name="$icon"/>
                            <span>{{ $label }}</span>
                            @if($route === 'notifications' && $unread)<b>{{ $unread }}</b>@endif
                        </a>
                    @endforeach
                    </div>
                </details>
            @endforeach
        </nav>
        <div class="sidebar-utility"><a class="side-link {{ request()->routeIs('account') ? 'is-active' : '' }}" href="{{ route('account') }}" @if(request()->routeIs('account')) aria-current="page" @endif><x-icon name="settings"/><span>Settings</span></a></div>
        <div class="sidebar-account">
            <a href="{{ route('account') }}" aria-label="My account">
                <span class="avatar">@if(!empty($user->profile['photo_path']))<img src="{{ route('account.photo') }}" alt="">@else{{ strtoupper(mb_substr($user->name, 0, 1)) }}@endif</span>
                <span><strong>{{ $user->name }}</strong><small>{{ $roleLabel }}</small></span>
            </a>
        </div>
    </aside>
    <button class="nav-overlay" type="button" aria-label="Close navigation"></button>
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-context">
                <button class="menu-button" type="button" aria-controls="navigation" aria-expanded="false" aria-label="Open navigation"><x-icon name="menu" /></button>
                <span><a href="{{ route('dashboard') }}">Workspace</a><span class="muted">/</span><strong>@yield('title', 'Overview')</strong></span>
            </div>
            <div class="topbar-right">
                <span class="today">{{ now()->format('D, d M Y') }}</span>
                <a href="{{ route('notifications') }}" class="notification-link" aria-label="Notifications{{ $unread ? ', '.$unread.' unread' : '' }}">
                    <x-icon name="bell"/>
                    @if($unread)<span class="notification-dot"></span>@endif
                </a>
                <details class="topbar-settings-menu">
                    <summary aria-label="Settings" title="Settings"><x-icon name="settings"/></summary>
                    <div class="topbar-menu-panel">
                        <div class="topbar-menu-user"><strong>{{ $user->name }}</strong><small>{{ $roleLabel }} workspace</small></div>
                        <a href="{{ route('account') }}" @if(request()->routeIs('account')) aria-current="page" @endif><x-icon name="user"/><span>My profile</span></a>
                        <a href="{{ route('account') }}#security"><x-icon name="shieldCheck"/><span>Password &amp; security</span></a>
                        <a href="{{ request()->getBaseUrl() }}/login" target="_blank" rel="noopener noreferrer"><x-icon name="userPlus"/><span>Open another account</span></a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button type="submit"><x-icon name="logout"/><span>Log out</span></button>
                        </form>
                    </div>
                </details>
                <a class="avatar small" href="{{ route('account') }}" aria-label="My profile">@if(!empty($user->profile['photo_path']))<img src="{{ route('account.photo') }}" alt="">@else{{ strtoupper(mb_substr($user->name, 0, 1)) }}@endif</a>
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
