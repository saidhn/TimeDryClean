<div class="pagination-container mb-3">
    @if ($paginator->onFirstPage())
    <span class="disabled">{{ __('pagination.previous') }}</span>
    @else
    <a href="{{ $paginator->previousPageUrl() }}" class="pagination-link">{{ __('pagination.previous') }}</a>
    @endif

    @for ($i = 1; $i <= $paginator->lastPage(); $i++)
        @if ($i == $paginator->currentPage())
        <span class="current">{{ $i }}</span>
        @else
        <a href="{{ $paginator->url($i) }}" class="pagination-link">{{ $i }}</a>
        @endif
        @endfor

        @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="pagination-link">{{ __('pagination.next') }}</a>
        @else
        <span class="disabled">{{ __('pagination.next') }}</span>
        @endif
</div>