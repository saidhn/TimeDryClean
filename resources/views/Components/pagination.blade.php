<div class="pagination-container mb-3">
    @if ($paginator->onFirstPage())
        <span class="disabled">{{ __('pagination.previous') }}</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="pagination-link">{{ __('pagination.previous') }}</a>
    @endif

    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $window = 2; // Number of pages to show on each side of the current page
    @endphp

    @if ($lastPage <= 10)
        {{-- Show all pages if there are 10 or fewer --}}
        @for ($i = 1; $i <= $lastPage; $i++)
            @if ($i == $currentPage)
                <span class="current">{{ $i }}</span>
            @else
                <a href="{{ $paginator->url($i) }}" class="pagination-link">{{ $i }}</a>
            @endif
        @endfor
    @else
        {{-- Show limited pages with ellipsis --}}
        @if ($currentPage > $window + 2)
            <a href="{{ $paginator->url(1) }}" class="pagination-link">1</a>
            @if ($currentPage > $window + 3)
                <span>...</span>
            @endif
        @endif

        @for ($i = max(1, $currentPage - $window); $i <= min($lastPage, $currentPage + $window); $i++)
            @if ($i == $currentPage)
                <span class="current">{{ $i }}</span>
            @else
                <a href="{{ $paginator->url($i) }}" class="pagination-link">{{ $i }}</a>
            @endif
        @endfor

        @if ($currentPage < $lastPage - $window - 1)
            @if ($currentPage < $lastPage - $window - 2)
                <span>...</span>
            @endif
            <a href="{{ $paginator->url($lastPage) }}" class="pagination-link">{{ $lastPage }}</a>
        @endif
    @endif

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="pagination-link">{{ __('pagination.next') }}</a>
    @else
        <span class="disabled">{{ __('pagination.next') }}</span>
    @endif
</div>