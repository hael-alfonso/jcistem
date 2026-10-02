<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Workspace') · JCIstem</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/brand/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/brand/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/chapter.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/jci-theme.css') }}">
    <script defer src="{{ asset('assets/js/chapter.js') }}"></script>
</head>
<body>
@php
    $user = auth()->user();
    $unread = \App\Models\Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    $roleLabel = \App\Support\WorkspaceNav::label($user->role);
    $navigation = [
        'OVERVIEW' => [
            ['dashboard', 'Dashboard', 'dashboard', []],
        ],
        'PROJECTS' => [
            ['projects', 'All projects', 'briefcase', []],
            ['projects', 'Project concepts', 'fileSignature', ['filter' => 'concepts']],
            ['tasks', 'Tasks & milestones', 'checklist', []],
            ['calendar', 'Calendar', 'calendar', []],
        ],
        'DOCUMENTS' => [
            ['records', 'Partner letters', 'fileSignature', ['kind' => 'letters']],
            ['records', 'Project reports', 'report', ['kind' => 'reports']],
        ],
        'FINANCES' => [
            ['finance', 'Financial overview', 'chart', []],
            ['dues', 'My member dues', 'receipt', []],
        ],
        'CHAPTER' => [
            ['members', 'Member directory', 'users', []],
            ['notifications', 'Notifications', 'bell', []],
            ['about', 'About JCI Carmona', 'shieldBrand', []],
        ],
    ];
    if ($user->role === 'treasurer') {
        $navigation['FINANCES'][] = ['ledger', 'Treasurer ledger', 'wallet', []];
        $navigation['FINANCES'][] = ['ledger', 'Liquidation', 'checklist', ['filter' => 'liquidation']];
        $navigation['FINANCES'][] = ['dues.manage', 'Record member dues', 'receipt', []];
    }
    if ($user->role === 'admin') {
        $navigation['CHAPTER'][] = ['members.create', 'Register member', 'userPlus', []];
        $navigation['CHAPTER'][] = ['audit', 'Audit trail', 'shieldCheck', []];
    }
@endphp
<a class="skip-link" href="#content">Skip to content</a>
<div class="app-shell">
    <aside class="app-sidebar" id="navigation" aria-label="Workspace navigation">
        @include('shared.brand')
        <div class="workspace-label"><span class="live-dot"></span><span>{{ $roleLabel }} workspace</span><span>PH</span></div>
        <nav aria-label="Main navigation">
            @foreach($navigation as $group => $items)
                <div class="nav-section">
                    <p>{{ $group }}</p>
                    @foreach($items as [$route, $label, $icon, $params])
                        @php
                            $active = match (true) {
                                $route === 'projects' && !$params => request()->routeIs('projects*') && request()->query('filter') !== 'concepts',
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
            @endforeach
        </nav>
        <div class="sidebar-account">
            <a href="{{ route('account') }}" aria-label="My account">
                <span class="avatar">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <span><strong>{{ $user->name }}</strong><small>{{ $roleLabel }}</small></span>
            </a>
            <form class="sidebar-logout-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="signout" type="submit"><span aria-hidden="true">↗</span> Log out</button>
            </form>
        </div>
    </aside>
    <button class="nav-overlay" type="button" aria-label="Close navigation"></button>
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-context">
                <button class="menu-button" type="button" aria-controls="navigation" aria-expanded="false" aria-label="Open navigation">☰</button>
                <span><a href="{{ route('dashboard') }}">Workspace</a><span class="muted">/</span><strong>@yield('title', 'Overview')</strong></span>
            </div>
            <div class="topbar-right">
                <span class="today">{{ now()->format('D, d M Y') }}</span>
                <a href="{{ route('notifications') }}" class="notification-link" aria-label="Notifications{{ $unread ? ', '.$unread.' unread' : '' }}">
                    <x-icon name="bell"/>
                    @if($unread)<span class="notification-dot"></span>@endif
                </a>
                <a class="avatar small" href="{{ route('account') }}" aria-label="My account">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</a>
                <form class="topbar-logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="topbar-logout" type="submit">Log out</button>
                </form>
            </div>
        </header>
        <main class="app-content" id="content">
            @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())
                <div class="alert error" role="alert">
                    <strong>Please check the following:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
        <footer class="app-footer">
            <span>JCIstem · Carmona City, Cavite</span>
            <span>Developing leaders. Creating positive change.</span>
        </footer>
    </div>
</div>
</body>
</html>
