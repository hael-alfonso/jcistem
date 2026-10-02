<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} · JCISTEM</title>
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
    $user = auth()->user();
    $role = $user?->role;
    $nav = \App\Support\WorkspaceNav::for($role);
    $roleLabel = \App\Support\WorkspaceNav::label($role);
    $context = \App\Support\WorkspaceNav::context($role);
    $notifyRoute = $role.'.notifications';
    $accountRoute = $role.'.account';
@endphp
<div class="shell">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-mark"><img src="{{ asset('assets/images/brand/logo.png') }}" alt="" class="system-logo"></div>
            <div>
                <strong>JCISTEM</strong>
                <span>{{ $roleLabel }} Workspace</span>
            </div>
        </div>
        <div class="workspace">
            <span>Signed in as</span>
            <strong>{{ $user->name }}</strong>
            <small>{{ $roleLabel }} • {{ $user->member_no }}</small>
        </div>
        <nav class="sidebar-nav" aria-label="{{ $roleLabel }} navigation">
            @foreach ($nav as $group)
                <div class="nav-group">
                    <span class="nav-label">{{ $group['group'] }}</span>
                    @foreach ($group['items'] as $item)
                        @php $active = \App\Support\WorkspaceNav::isActive($item); @endphp
                        <a href="{{ route($item[0]) }}" class="nav-link {{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif>
                            <span class="nav-icon"><x-icon :name="$item[2]" /></span>
                            <span>{{ $item[1] }}</span>
                            @if(str_contains($item[0], 'notifications') && ($unread ?? 0))
                                <b class="nav-count">{{ $unread }}</b>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
        <div class="sidebar-foot">
            <a href="{{ route($accountRoute) }}" class="account-chip">
                <span class="avatar avatar-sm">{{ \App\Support\JciDemoData::initials($user->name) }}</span>
                <span>
                    <strong>{{ $user->name }}</strong>
                    <small>{{ $roleLabel }}</small>
                </span>
                <em><x-icon name="chevron" /></em>
            </a>
        </div>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <main class="main">
        <header class="topbar">
            <div class="top-left">
                <button class="mobile-toggle" id="mobileToggle" type="button" aria-label="Open navigation"><x-icon name="menu" /></button>
                <div class="top-title">
                    <span class="top-eyebrow">JCISTEM • {{ strtoupper($roleLabel) }}</span>
                    <h2>{{ $pageTitle }}</h2>
                    <small class="top-context">{{ $context }}</small>
                </div>
            </div>
            <div class="top-actions">
                @if (Route::has($notifyRoute))
                    <a class="icon-btn" href="{{ route($notifyRoute) }}" title="Notifications">
                        <x-icon name="bell" />
                        @if(($unread ?? 0))<b class="notification-badge">{{ $unread }}</b>@endif
                    </a>
                @endif
                <a class="profile-pill" href="{{ route($accountRoute) }}">
                    <span class="avatar avatar-sm">{{ \App\Support\JciDemoData::initials($user->name) }}</span>
                    <span>{{ $roleLabel }}</span>
                    <x-icon name="chevron" />
                </a>
                <form class="topbar-logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="topbar-logout">Log out</button>
                </form>
            </div>
        </header>
        <section class="content">
            @yield('content')
        </section>
        <footer class="footer">
            <span><strong>JCISTEM</strong> • Online Project Management System</span>
            <span>{{ $roleLabel }} Workspace</span>
        </footer>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('assets/js/admin.js') }}"></script>
@stack('scripts')
</body>
</html>
