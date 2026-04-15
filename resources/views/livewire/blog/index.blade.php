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

    {{-- Featured post hero --}}
    @if($featured)
        <div class="max-w-6xl mx-auto px-4 sm:px-6 mb-14">
            <a href="{{ route('blog.show', $featured->slug) }}" class="block group">
                <div class="grid md:grid-cols-2 gap-6 sm:gap-10 items-center">
                    <div class="relative aspect-[16/10] rounded-2xl overflow-hidden" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07)">
                        @if($featured->featuredImageUrl())
                            <img src="{{ $featured->featuredImageUrl() }}" alt="{{ $featured->featured_image_alt ?: $featured->title }}"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
                        @else
                            <div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,rgba(26,86,219,0.25),rgba(59,130,246,0.08))">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" style="color:rgba(255,255,255,0.3)"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                            </div>
                        @endif
                        <span class="absolute top-4 left-4 text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full" style="background:rgba(59,130,246,0.95);color:#fff">★ Featured</span>
                    </div>
                    <div>
                        @if($featured->category)
                            <span class="inline-block text-xs font-semibold uppercase tracking-widest mb-3" style="color:{{ $featured->category->color }}">{{ $featured->category->name }}</span>
                        @endif
                        <h2 class="fd font-black mb-3 transition-colors group-hover:text-white" style="color:#f8fafc;font-size:clamp(1.5rem,3vw,2.2rem);line-height:1.15;letter-spacing:-0.01em">{{ $featured->title }}</h2>
                        @if($featured->excerpt)
                            <p class="text-base mb-5" style="color:rgba(226,232,240,0.6);line-height:1.6">{{ $featured->excerpt }}</p>
                        @endif
                        <div class="flex items-center gap-4 text-xs" style="color:rgba(255,255,255,0.4)">
                            @if($featured->author)<span>By {{ $featured->author->name }}</span><span>·</span>@endif
                            <span>{{ $featured->published_at?->format('M j, Y') }}</span>
                            <span>·</span>
                            <span>{{ $featured->reading_time }} min read</span>
                        </div>
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7">
                @foreach($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="group block">
                        <div class="aspect-[16/10] rounded-xl overflow-hidden mb-4" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07)">
                            @if($post->featuredImageUrl())
                                <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->featured_image_alt ?: $post->title }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
                            @else
                                <div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,rgba(26,86,219,0.18),rgba(59,130,246,0.04))">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24" style="color:rgba(255,255,255,0.25)"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z"/></svg>
                                </div>
                            @endif
                        </div>
                        @if($post->category)
                            <span class="inline-block text-[11px] font-semibold uppercase tracking-widest mb-2" style="color:{{ $post->category->color }}">{{ $post->category->name }}</span>
                        @endif
                        <h3 class="fd font-bold text-lg sm:text-xl mb-2 transition-colors" style="color:#f8fafc;line-height:1.25;letter-spacing:-0.005em">{{ $post->title }}</h3>
                        @if($post->excerpt)
                            <p class="text-sm mb-3" style="color:rgba(226,232,240,0.55);line-height:1.55">{{ Str::limit($post->excerpt, 130) }}</p>
                        @endif
                        <div class="flex items-center gap-2 text-[11px]" style="color:rgba(255,255,255,0.35)">
                            <span>{{ $post->published_at?->format('M j, Y') }}</span>
                            <span>·</span>
                            <span>{{ $post->reading_time }} min read</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Pagination --}}
        @if($posts->hasPages())
            <div class="mt-14">
                {{ $posts->withQueryString()->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
