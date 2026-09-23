@props(['name' => 'grid'])

@php
$icons = [
    'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
    'briefcase' => '<rect x="3" y="6.5" width="18" height="13" rx="2.5"/><path d="M8 6.5V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1.5M3 11h18"/>',
    'filePlus' => '<path d="M6 3.5h8l4 4v13H6z"/><path d="M14 3.5v4h4M12 11v6M9 14h6"/>',
    'clipboardCheck' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M8.5 12l2 2 4.5-4.5"/>',
    'checklist' => '<rect x="5" y="3.5" width="14" height="17" rx="2"/><path d="M8.5 9.5l1.5 1.5 2.7-2.7M14.5 10h2M8.5 15l1.5 1.5 2.7-2.7"/>',
    'fileSignature' => '<path d="M6 3.5h8l4 4v13H6z"/><path d="M14 3.5v4h4M8 12h4"/>',
    'calendar' => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M7 2.5v4M17 2.5v4M3 9h18"/>',
    'report' => '<path d="M6 3.5h8l4 4v13H6z"/><path d="M14 3.5v4h4M9 12h6M9 15.5h6"/>',
    'chart' => '<path d="M4 19.5V4.5M4 19.5h16"/><path d="m7 15 3-4 3 2 4-6"/>',
    'users' => '<circle cx="10" cy="8" r="3"/><path d="M4 20a5.5 5.5 0 0 1 11 0M16 7.5a3 3 0 0 1 0 5.8"/>',
    'userPlus' => '<circle cx="10" cy="8" r="3.5"/><path d="M4.5 20a5.5 5.5 0 0 1 11 0M19 8v6M16 11h6"/>',
    'receipt' => '<path d="M6 3.5h12v17l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 11.5h6"/>',
    'bell' => '<path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M9.5 21h5"/>',
    'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
    'shieldCheck' => '<path d="M12 3 19 6v5c0 4.5-2.8 7.7-7 10-4.2-2.3-7-5.5-7-10V6z"/><path d="m8.5 12 2.2 2.2 4.8-4.8"/>',
    'shieldBrand' => '<path d="M12 3.2 18.5 6v5c0 4.1-2.4 7.2-6.5 9.8C7.9 18.2 5.5 15.1 5.5 11V6z"/><path d="M8.5 9.2h7L12 15z"/>',
    'shield' => '<path d="M12 3 19 6v5c0 4.5-2.8 7.7-7 10-4.2-2.3-7-5.5-7-10V6z"/>',
    'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'chevron' => '<path d="m9 5 7 7-7 7"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'arrow' => '<path d="M5 12h13M13 7l5 5-5 5"/>',
    'search' => '<circle cx="10.5" cy="10.5" r="5.8"/><path d="m15 15 5 5"/>',
    'eye' => '<path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5z"/><circle cx="12" cy="12" r="2.5"/>',
    'download' => '<path d="M12 4v11M8 11l4 4 4-4M5 20h14"/>',
    'print' => '<path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v6H7z"/>',
    'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
    'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
    'check' => '<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12 2.3 2.3 4.7-5"/>',
    'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/>',
    'folder' => '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H10l2 2h6.5A2.5 2.5 0 0 1 21 9.5v7A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5z"/>',
    'wallet' => '<path d="M4 7.5h14.5A1.5 1.5 0 0 1 20 9v8.5A2.5 2.5 0 0 1 17.5 20h-12A2.5 2.5 0 0 1 3 17.5V6a2 2 0 0 1 2-2h12"/>',
    'link' => '<path d="M10 13.8 8.4 15.4a3.2 3.2 0 0 1-4.5-4.5L7 7.8a3.2 3.2 0 0 1 4.5 0"/><path d="m14 10.2 1.6-1.6a3.2 3.2 0 0 1 4.5 4.5L17 16.2a3.2 3.2 0 0 1-4.5 0"/>',
];
$path = $icons[$name] ?? '<circle cx="12" cy="12" r="2"/>';
@endphp

<span {{ $attributes->class(['ico']) }} aria-hidden="true">
    <svg viewBox="0 0 24 24" focusable="false" stroke="currentColor" fill="none" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg>
</span>
