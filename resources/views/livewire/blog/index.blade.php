<div>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 sm:pt-16 pb-4">

        {{-- Header --}}
        <div class="mb-8 sm:mb-12">
            <p class="text-xs font-semibold uppercase tracking-widest mb-3" style="color:rgba(59,130,246,0.8)">
                @if($currentCategory) Category @else The Blog @endif
            </p>
            <h1 class="fd font-black leading-tight" style="color:#f8fafc;font-size:clamp(2.2rem,5vw,3.5rem);letter-spacing:-0.02em">
                @if($currentCategory)
                    {{ $currentCategory->name }}
                @else
                    Sharp takes on cash flow,<br>bookkeeping, and small business.
                @endif
            </h1>
            @if($currentCategory && $currentCategory->description)
                <p class="mt-4 max-w-2xl text-base sm:text-lg" style="color:rgba(226,232,240,0.55);line-height:1.6">{{ $currentCategory->description }}</p>
            @elseif(! $currentCategory)
                <p class="mt-4 max-w-2xl text-base sm:text-lg" style="color:rgba(226,232,240,0.55);line-height:1.6">
                    Practical playbooks for owners, freelancers, and finance teams — no accounting jargon.
                </p>
            @endif
        </div>

        {{-- Filter bar: categories + search --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 mb-10 pb-6" style="border-bottom:1px solid rgba(255,255,255,0.06)">
            <div class="flex-1 flex items-center gap-2 overflow-x-auto -mx-1 px-1 pb-1">
                <a href="{{ route('blog.index') }}"
                   class="flex-shrink-0 text-sm px-4 py-2 rounded-full transition-all"
                   style="background:{{ ! $currentCategory ? '#1a56db' : 'rgba(255,255,255,0.04)' }};color:{{ ! $currentCategory ? '#fff' : 'rgba(248,250,252,0.6)' }};border:1px solid {{ ! $currentCategory ? 'transparent' : 'rgba(255,255,255,0.06)' }}">
                    All
                </a>
                @foreach($allCategories as $cat)
                    <a href="{{ route('blog.category', $cat->slug) }}"
                       class="flex-shrink-0 text-sm px-4 py-2 rounded-full transition-all whitespace-nowrap"
                       style="background:{{ $currentCategory && $currentCategory->id === $cat->id ? $cat->color : 'rgba(255,255,255,0.04)' }};color:{{ $currentCategory && $currentCategory->id === $cat->id ? '#fff' : 'rgba(248,250,252,0.6)' }};border:1px solid {{ $currentCategory && $currentCategory->id === $cat->id ? 'transparent' : 'rgba(255,255,255,0.06)' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
            <div class="relative sm:w-64">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="color:rgba(255,255,255,0.35)">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search posts…"
                       class="w-full pl-10 pr-4 py-2.5 rounded-full text-sm"
                       style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);color:#f8fafc;outline:none"
                       onfocus="this.style.borderColor='rgba(59,130,246,0.4)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
        </div>
    </div>

    {{-- Featured post hero — bordered card, full-width, split image + body --}}
    @if($featured)
        <div class="max-w-6xl mx-auto px-4 sm:px-6 mb-12">
            <a href="{{ route('blog.show', $featured->slug) }}"
               class="blog-card blog-card-hero group grid md:grid-cols-2 rounded-2xl overflow-hidden transition-all duration-300"
               style="background:#0d1526;border:1px solid rgba(255,255,255,0.07)">
                <div class="relative aspect-[16/10] md:aspect-auto md:h-full overflow-hidden" style="background:rgba(255,255,255,0.02)">
                    @if($featured->featuredImageUrl())
                        <img src="{{ $featured->featuredImageUrl() }}"
                             alt="{{ $featured->featured_image_alt ?: $featured->title }}"
                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
                    @else
                        <div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,rgba(26,86,219,0.25),rgba(59,130,246,0.08))">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" style="color:rgba(255,255,255,0.3)"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                        </div>
                    @endif
                    <span class="absolute top-4 left-4 inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full" style="background:rgba(59,130,246,0.95);color:#fff;box-shadow:0 4px 14px rgba(26,86,219,0.4)">★ Featured</span>
                </div>
                <div class="p-6 sm:p-8 md:p-10 flex flex-col justify-center">
                    <div class="flex items-center gap-3 mb-4">
                        @if($featured->category)
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full"
                                  style="background:{{ $featured->category->color }}22;color:{{ $featured->category->color }};border:1px solid {{ $featured->category->color }}33">
                                {{ $featured->category->name }}
                            </span>
                        @endif
                        <span class="text-[11px]" style="color:rgba(255,255,255,0.4)">{{ $featured->published_at?->format('M j, Y') }}</span>
                    </div>
                    <h2 class="fd font-black mb-3 transition-colors" style="color:#f8fafc;font-size:clamp(1.5rem,3vw,2.2rem);line-height:1.15;letter-spacing:-0.01em">{{ $featured->title }}</h2>
                    @if($featured->excerpt)
                        <p class="text-base mb-5" style="color:rgba(226,232,240,0.6);line-height:1.6">{{ $featured->excerpt }}</p>
                    @endif
                    <div class="flex items-center gap-3 text-xs pt-4" style="color:rgba(255,255,255,0.4);border-top:1px solid rgba(255,255,255,0.05)">
                        @if($featured->author)
                            <span>By {{ $featured->author->name }}</span>
                            <span>·</span>
                        @endif
                        <span>{{ $featured->reading_time }} min read</span>
                        <span class="ml-auto inline-flex items-center gap-1 font-medium transition-all group-hover:gap-2" style="color:#93c5fd">
                            Read article
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </span>
                    </div>
                </div>
            </a>
        </div>
    @endif

    {{-- Posts grid --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 pb-16">
        @if($posts->isEmpty() && ! $featured)
            <div class="py-24 text-center">
                <div class="w-16 h-16 rounded-2xl mx-auto mb-5 flex items-center justify-center" style="background:rgba(26,86,219,0.08);border:1px solid rgba(59,130,246,0.15)">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:#3b82f6"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5-4.72-4.72a.75.75 0 0 0-1.28.53v4.19H3.375a.75.75 0 0 0-.75.75v3a.75.75 0 0 0 .75.75H9.75v4.19c0 .67.81 1 1.28.53l4.72-4.72a.75.75 0 0 0 0-1.06ZM15 15V9"/></svg>
                </div>
                <h3 class="fd font-bold text-xl mb-2" style="color:#f8fafc">No posts yet</h3>
                <p class="text-sm max-w-md mx-auto" style="color:rgba(226,232,240,0.5)">
                    @if($search !== '')
                        Nothing matches "{{ $search }}". Try a different keyword.
                    @elseif($currentCategory)
                        No posts in this category yet — check back soon.
                    @else
                        Articles are coming soon.
                    @endif
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7">
                @foreach($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}"
                       class="blog-card group flex flex-col rounded-2xl overflow-hidden transition-all duration-300"
                       style="background:#0d1526;border:1px solid rgba(255,255,255,0.07)">
                        {{-- Image at the top, full-bleed to card edges --}}
                        <div class="aspect-[16/10] overflow-hidden relative" style="background:rgba(255,255,255,0.02)">
                            @if($post->featuredImageUrl())
                                <img src="{{ $post->featuredImageUrl() }}"
                                     alt="{{ $post->featured_image_alt ?: $post->title }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.05]">
                            @else
                                <div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,rgba(26,86,219,0.22),rgba(59,130,246,0.04))">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" style="color:rgba(255,255,255,0.22)"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                                </div>
                            @endif
                        </div>

                        {{-- Body --}}
                        <div class="p-5 sm:p-6 flex-1 flex flex-col">
                            {{-- Meta row: category pill + date --}}
                            <div class="flex items-center gap-3 mb-3">
                                @if($post->category)
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full whitespace-nowrap"
                                          style="background:{{ $post->category->color }}22;color:{{ $post->category->color }};border:1px solid {{ $post->category->color }}33">
                                        {{ $post->category->name }}
                                    </span>
                                @endif
                                <span class="text-[11px]" style="color:rgba(255,255,255,0.38)">{{ $post->published_at?->format('M j, Y') }}</span>
                            </div>

                            {{-- Title --}}
                            <h3 class="fd font-extrabold text-lg sm:text-xl mb-2 transition-colors line-clamp-2"
                                style="color:#f8fafc;line-height:1.3;letter-spacing:-0.005em">
                                {{ $post->title }}
                            </h3>

                            {{-- Excerpt --}}
                            @if($post->excerpt)
                                <p class="text-sm mb-4 line-clamp-3 flex-1" style="color:rgba(226,232,240,0.55);line-height:1.55">
                                    {{ $post->excerpt }}
                                </p>
                            @endif

                            {{-- Footer: reading time + read-more arrow --}}
                            <div class="flex items-center justify-between text-[11px] pt-3" style="color:rgba(255,255,255,0.4);border-top:1px solid rgba(255,255,255,0.05)">
                                <span>{{ $post->reading_time }} min read</span>
                                <span class="inline-flex items-center gap-1 font-medium transition-all group-hover:gap-2" style="color:#93c5fd">
                                    Read
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <style>
                .blog-card:hover {
                    border-color: rgba(59,130,246,0.35) !important;
                    background: #111a30 !important;
                    transform: translateY(-3px);
                    box-shadow: 0 14px 36px rgba(0,0,0,0.35), 0 0 0 1px rgba(59,130,246,0.08);
                }
            </style>
        @endif

        {{-- Pagination --}}
        @if($posts->hasPages())
            <div class="mt-14">
                {{ $posts->withQueryString()->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
