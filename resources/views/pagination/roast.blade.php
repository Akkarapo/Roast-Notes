@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-wrap items-center justify-center gap-2">
        @php $pill = 'inline-flex min-w-10 justify-center rounded-full border-2 border-cobalt px-3.5 py-1.5 text-sm font-semibold'; @endphp

        @if ($paginator->onFirstPage())
            <span class="{{ $pill }} opacity-30">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $pill }} hover:bg-paper" aria-label="{{ __('pagination.previous') }}">‹</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-1">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" class="{{ $pill }} bg-cobalt text-paper">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="{{ $pill }} hover:bg-paper">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $pill }} hover:bg-paper" aria-label="{{ __('pagination.next') }}">›</a>
        @else
            <span class="{{ $pill }} opacity-30">›</span>
        @endif
    </nav>
@endif
