<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Workspace') · JCI Carmona</title>
<link rel="stylesheet" href="{{ asset('assets/css/chapter.css') }}">
<script defer src="{{ asset('assets/js/chapter.js') }}"></script>
</head>
<body>
@php
$user = auth()->user();
$unread = \App\Models\Notification::where('user_id', $user->id)->whereNull('read_at')->count();
$roleLabel = ['admin'=>'Administrator','bod'=>'Board of Directors','treasurer'=>'Treasurer','member'=>'General Member'][$user->role] ?? 'Member';
$navigation = [
'WORKSPACE' => [['dashboard','Overview','dashboard',[]],['projects','Projects','briefcase',[]],['projects','Project concepts','fileSignature',['filter'=>'concepts']],['tasks','Tasks & milestones','checklist',[]],['calendar','Calendar','calendar',[]]],
'DOCUMENTS & FUNDS' => [['records','Partner letters','fileSignature',['kind'=>'letters']],['records','Project reports','report',['kind'=>'reports']],['finance','Financial monitoring','chart',[]]],
'CHAPTER' => [['members','Member directory','users',[]],['dues','My member dues','receipt',[]],['notifications','Notifications','bell',[]],['about','About JCI Carmona','shieldBrand',[]]],
];
if ($user->role === 'treasurer') $navigation['TREASURER'] = [['ledger','Treasurer ledger','wallet',[]],['ledger','Liquidation','checklist',['filter'=>'liquidation']],['dues.manage','Record member dues','receipt',[]]];
if ($user->role === 'admin') $navigation['ADMINISTRATION'] = [['members.create','Register member','userPlus',[]],['audit','Audit trail','shieldCheck',[]]];
@endphp
<a class="skip-link" href="#content">Skip to content</a>
<div class="app-shell">
<aside class="app-sidebar" id="navigation">
<a class="chapter-brand" href="{{ route('dashboard') }}"><span class="brand-word">JCI<span class="brand-spark">✦</span></span><span>CARMONA<small>PROJECT MANAGEMENT</small></span></a>
<div class="workspace-label"><span class="live-dot"></span> Chapter workspace <span>PH</span></div>
<nav aria-label="Main navigation">
@foreach($navigation as $group => $items)
<div class="nav-section"><p>{{ $group }}</p>
@foreach($items as [$route,$label,$icon,$params])
@php $active = request()->routeIs($route) && collect($params)->every(fn($value,$key) => (request()->route($key) ?? request()->query($key)) == $value) && ($params || !request()->has('filter')); @endphp
<a class="side-link {{ $active ? 'is-active' : '' }}" href="{{ route($route,$params) }}" @if($active) aria-current="page" @endif><x-icon :name="$icon"/><span>{{ $label }}</span>@if($route==='notifications' && $unread)<b>{{ $unread }}</b>@endif</a>
@endforeach
</div>
@endforeach
</nav>
<div class="sidebar-account"><a href="{{ route('account') }}"><span class="avatar">{{ strtoupper(mb_substr($user->name,0,1)) }}</span><span><strong>{{ $user->name }}</strong><small>{{ $roleLabel }}</small></span></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="signout" title="Sign out" aria-label="Sign out">↗</button></form></div>
</aside>
<button class="nav-overlay" type="button" aria-label="Close navigation"></button>
<div class="app-main">
<header class="app-topbar"><div class="topbar-context"><button class="menu-button" type="button" aria-controls="navigation" aria-expanded="false" aria-label="Open navigation">☰</button><span>JCI Carmona <span class="muted">/</span> <strong>@yield('title', 'Workspace')</strong></span></div><div class="topbar-right"><span class="today">{{ now()->format('D, d M Y') }}</span><a href="{{ route('notifications') }}" class="notification-link" aria-label="Notifications{{ $unread ? ', '.$unread.' unread' : '' }}"><x-icon name="bell"/>@if($unread)<span class="notification-dot"></span>@endif</a><a class="avatar small" href="{{ route('account') }}" aria-label="My account">{{ strtoupper(mb_substr($user->name,0,1)) }}</a></div></header>
<main class="app-content" id="content">
@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert"><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
<footer class="app-footer"><span>JCI Carmona · Carmona City, Cavite</span><span>Developing leaders. Creating positive change.</span></footer>
</div>
</div>
</body></html>

