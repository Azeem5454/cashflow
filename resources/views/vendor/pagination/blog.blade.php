{{--
    Crawlable pagination for the blog.

    Livewire's default pagination renders <button wire:click="gotoPage(n)">,
    which Googlebot cannot follow — everything past page 1 then has no
    internal link path and earns no PageRank. These are real <a href> links
    carrying wire:navigate, so users still get the SPA transition while
    crawlers get something to crawl.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Blog pagination"
         class="flex items-center justify-center gap-1.5 flex-wrap">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="px-3.5 py-2 rounded-lg text-sm text-slate-600 cursor-default" aria-disabled="true">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" wire:navigate rel="prev"
               class="px-3.5 py-2 rounded-lg text-sm text-slate-300 hover:text-white hover:bg-white/5 transition">Previous</a>
        @endif

        {{-- Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 py-2 text-sm text-slate-600">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"
                              class="px-3.5 py-2 rounded-lg text-sm font-semibold text-white bg-primary">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" wire:navigate
                           class="px-3.5 py-2 rounded-lg text-sm text-slate-300 hover:text-white hover:bg-white/5 transition">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" wire:navigate rel="next"
               class="px-3.5 py-2 rounded-lg text-sm text-slate-300 hover:text-white hover:bg-white/5 transition">Next</a>
        @else
            <span class="px-3.5 py-2 rounded-lg text-sm text-slate-600 cursor-default" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif
