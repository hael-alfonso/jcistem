@if($items->total() > 0)
@php
    $current = $items->currentPage();
    $last = $items->lastPage();
    $start = max(2, $current - 1);
    $end = min($last - 1, $current + 1);
@endphp
<nav class="pagination workspace-pagination" aria-label="Pagination">
    <span>Showing {{ number_format($items->firstItem()) }}–{{ number_format($items->lastItem()) }} of {{ number_format($items->total()) }}</span>
    @if($last > 1)
    <div class="pagination-pages">
        @if($items->previousPageUrl())<a class="pagination-edge" href="{{ $items->previousPageUrl() }}" rel="prev" aria-label="Previous page"><x-icon name="arrowLeft"/><span>Previous</span></a>@endif
        <a href="{{ $items->url(1) }}" aria-label="Page 1" @if($current === 1) aria-current="page" @endif>1</a>
        @if($start > 2)<span class="pagination-ellipsis" aria-hidden="true">…</span>@endif
        @for($page = $start; $page <= $end; $page++)<a href="{{ $items->url($page) }}" aria-label="Page {{ $page }}" @if($current === $page) aria-current="page" @endif>{{ $page }}</a>@endfor
        @if($end < $last - 1)<span class="pagination-ellipsis" aria-hidden="true">…</span>@endif
        <a href="{{ $items->url($last) }}" aria-label="Page {{ $last }}" @if($current === $last) aria-current="page" @endif>{{ $last }}</a>
        @if($items->nextPageUrl())<a class="pagination-edge" href="{{ $items->nextPageUrl() }}" rel="next" aria-label="Next page"><span>Next</span><x-icon name="arrow"/></a>@endif
    </div>
    @endif
</nav>
@endif
