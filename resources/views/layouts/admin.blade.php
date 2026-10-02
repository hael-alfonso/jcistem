<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} · JCISTEM Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/brand/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/brand/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin-ui.css') }}">
</head>
<body>
@php
    $nav = [
        ['group' => 'Overview', 'items' => [
            ['dashboard', 'Dashboard', 'dashboard'],
        ]],
        ['group' => 'Projects', 'items' => [
            ['projects*', 'Projects', 'briefcase'],
            ['projects.create', 'Create Project', 'filePlus'],
            ['tasks', 'Tasks & Milestones', 'checklist'],
            ['loi', 'Letters of Intent', 'fileSignature'],
            ['calendar', 'Calendar', 'calendar'],
        ]],
        ['group' => 'Reports & Finance', 'items' => [
            ['reports', 'Project Reports', 'report'],
            ['finance*', 'Financial Monitoring', 'chart'],
        ]],
        ['group' => 'Organization', 'items' => [
            ['members', 'Members & Accounts', 'users'],
            ['members.registration', 'Member Registration', 'userPlus'],
            ['dues', 'My Member Dues', 'receipt'],
            ['notifications', 'Notifications', 'bell'],
        ]],
        ['group' => 'Account', 'items' => [
            ['account', 'My Account', 'user'],
            ['audit', 'Audit Log', 'shieldCheck'],
        ]],
    ];
    $routeName = [
        'dashboard' => 'dashboard',
        'projects*' => 'projects',
        'projects.create' => 'projects.create',
        'tasks' => 'tasks',
        'loi' => 'loi',
        'calendar' => 'calendar',
        'reports' => 'reports',
        'finance*' => 'finance',
        'members' => 'members',
        'members.registration' => 'members.registration',
        'dues' => 'dues',
        'notifications' => 'notifications',
        'account' => 'account',
        'audit' => 'audit',
    ];
@endphp
<div class="shell">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-mark"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="" class="system-logo"></div>
            <div>
                <strong>JCISTEM</strong>
                <span>Project Management System</span>
            </div>
        </div>
        <div class="workspace">
            <span>Signed in as</span>
            <strong>{{ $currentUser }}</strong>
            <small>Admin • Monitoring & Workflow</small>
        </div>
        <nav class="sidebar-nav" aria-label="Admin navigation">
            @foreach ($nav as $group)
                <div class="nav-group">
                    <span class="nav-label">{{ $group['group'] }}</span>
                    @foreach ($group['items'] as [$pattern, $label, $icon])
                        @php
                            $href = route($routeName[$pattern]);
                            $active = $pattern === 'projects.create'
                                ? request()->routeIs('projects.create')
                                : ($pattern === 'projects*'
                                    ? request()->routeIs('projects', 'projects.show') && !request()->routeIs('projects.create')
                                    : ($pattern === 'members'
                                        ? request()->routeIs('members')
                                        : request()->routeIs($pattern)));
                        @endphp
                        <a href="{{ $href }}" class="nav-link {{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif>
                            <span class="nav-icon"><x-icon :name="$icon" /></span>
                            <span>{{ $label }}</span>
                            @if($pattern === 'notifications' && $unread)
                                <b class="nav-count">{{ $unread }}</b>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
        <div class="sidebar-foot">
            <a href="{{ route('account') }}" class="account-chip">
                <span class="avatar avatar-sm">{{ \App\Support\JciDemoData::initials($currentUser) }}</span>
                <span>
                    <strong>{{ $currentUser }}</strong>
                    <small>Admin</small>
                </span>
                <em><x-icon name="chevron" /></em>
            </a>
            <div class="brand-footer">JCISTEM • Admin Workspace</div>
        </div>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <main class="main">
        <header class="topbar">
            <div class="top-left">
                <button class="mobile-toggle" id="mobileToggle" type="button" aria-label="Open navigation"><x-icon name="menu" /></button>
                <div class="top-title">
                    <span class="top-eyebrow">JCISTEM • ADMIN</span>
                    <h2>{{ $pageTitle }}</h2>
                    <small class="top-context">Projects • Monitoring • Reports</small>
                </div>
            </div>
            <div class="top-actions">
                <a class="icon-btn" href="{{ route('notifications') }}" title="Notifications">
                    <x-icon name="bell" />
                    @if($unread)<b class="notification-badge">{{ $unread }}</b>@endif
                </a>
                <a class="profile-pill" href="{{ route('account') }}">
                    <span class="avatar avatar-sm">{{ \App\Support\JciDemoData::initials($currentUser) }}</span>
                    <span>Admin</span>
                    <x-icon name="chevron" />
                </a>
            </div>
        </header>
        <section class="content">
            @yield('content')
        </section>
        <footer class="footer">
            <span><strong>JCISTEM</strong> • Online Project Management System</span>
            <span>Admin Workspace</span>
        </footer>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('assets/js/admin.js') }}"></script>
@stack('scripts')
</body>
</html>
