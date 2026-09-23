@props(['title', 'subtitle' => '', 'class' => ''])

<section class="card {{ $class }}">
    <div class="card-head">
        <div>
            <h2>{{ $title }}</h2>
            @if($subtitle)<p>{{ $subtitle }}</p>@endif
        </div>
        {{ $actions ?? '' }}
    </div>
    <div class="card-body">{{ $slot }}</div>
</section>
