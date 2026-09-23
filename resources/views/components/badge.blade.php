@props(['text' => '', 'tone' => null])

<span class="badge {{ $tone ?: \App\Support\JciDemoData::statusClass($text) }}">{{ $text }}</span>
