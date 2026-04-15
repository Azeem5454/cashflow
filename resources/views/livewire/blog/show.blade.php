<div>
    {{-- Hero — featured image banner + title --}}
    <article class="max-w-3xl mx-auto px-4 sm:px-6 pt-10 sm:pt-14 pb-4">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-xs mb-8" style="color:rgba(255,255,255,0.4)">
            <a href="{{ url('/') }}" class="hover:text-white transition-colors">Home</a>
            <span>›</span>
            <a href="{{ route('blog.index') }}" class="hover:text-white transition-colors">Blog</a>
            @if($post->category)
                <span>›</span>
                <a href="{{ route('blog.category', $post->category->slug) }}" class="hover:text-white transition-colors" style="color:{{ $post->category->color }}">{{ $post->category->name }}</a>
            @endif
        </nav>

        {{-- Post meta header --}}
        <header class="mb-8">
            @if($post->category)
                <span class="inline-block text-xs font-semibold uppercase tracking-widest mb-4" style="color:{{ $post->category->color }}">{{ $post->category->name }}</span>
            @endif

            <h1 class="fd font-black mb-5" style="color:#f8fafc;font-size:clamp(2rem,5.5vw,3.2rem);line-height:1.12;letter-spacing:-0.02em">
                {{ $post->title }}
            </h1>

            @if($post->excerpt)
                <p class="mb-6 text-lg sm:text-xl" style="color:rgba(226,232,240,0.6);line-height:1.55">{{ $post->excerpt }}</p>
            @endif

            <div class="flex items-center flex-wrap gap-x-4 gap-y-2 text-sm pt-5" style="color:rgba(255,255,255,0.45);border-top:1px solid rgba(255,255,255,0.08)">
                @if($post->author)
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" style="background:linear-gradient(135deg,#1a56db,#3b82f6);color:#fff">
                            {{ strtoupper(substr($post->author->name, 0, 1)) }}
                        </div>
                        <span style="color:rgba(248,250,252,0.8)">{{ $post->author->name }}</span>
                    </div>
                    <span>·</span>
                @endif
                <span>{{ $post->published_at?->format('M j, Y') }}</span>
                <span>·</span>
                <span>{{ $post->reading_time }} min read</span>
            </div>
        </header>

        {{-- Featured image --}}
        @if($post->featuredImageUrl())
            <figure class="mb-10 -mx-4 sm:mx-0">
                <div class="aspect-[16/9] overflow-hidden sm:rounded-2xl" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07)">
                    <img src="{{ $post->featuredImageUrl() }}"
                         alt="{{ $post->featured_image_alt ?: $post->title }}"
                         class="w-full h-full object-cover">
                </div>
            </figure>
        @endif

        {{-- Body --}}
        <div class="prose-blog">
            {!! $post->body_html !!}
        </div>

        {{-- Social share --}}
        @php
            $shareUrl   = urlencode($post->url());
            $shareTitle = urlencode($post->title);
        @endphp
        <div class="mt-14 pt-8 flex flex-wrap items-center gap-3" style="border-top:1px solid rgba(255,255,255,0.08)">
            <span class="text-xs font-semibold uppercase tracking-wider mr-2" style="color:rgba(255,255,255,0.4)">Share</span>
            <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener"
               class="flex items-center gap-2 px-4 py-2 rounded-full text-xs font-medium transition-all"
               style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);color:rgba(248,250,252,0.75)"
               onmouseover="this.style.background='rgba(29,161,242,0.15)';this.style.borderColor='rgba(29,161,242,0.35)';this.style.color='#fff'"
               onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.borderColor='rgba(255,255,255,0.08)';this.style.color='rgba(248,250,252,0.75)'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                X / Twitter
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener"
               class="flex items-center gap-2 px-4 py-2 rounded-full text-xs font-medium transition-all"
               style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);color:rgba(248,250,252,0.75)"
               onmouseover="this.style.background='rgba(10,102,194,0.15)';this.style.borderColor='rgba(10,102,194,0.35)';this.style.color='#fff'"
               onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.borderColor='rgba(255,255,255,0.08)';this.style.color='rgba(248,250,252,0.75)'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                LinkedIn
            </a>
            <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" rel="noopener"
               class="flex items-center gap-2 px-4 py-2 rounded-full text-xs font-medium transition-all"
               style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);color:rgba(248,250,252,0.75)"
               onmouseover="this.style.background='rgba(37,211,102,0.15)';this.style.borderColor='rgba(37,211,102,0.35)';this.style.color='#fff'"
               onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.borderColor='rgba(255,255,255,0.08)';this.style.color='rgba(248,250,252,0.75)'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.82 11.82 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zM6.597 20.13a9.874 9.874 0 005.452 1.636h.004c5.448 0 9.886-4.434 9.889-9.885a9.86 9.86 0 00-2.898-7.011 9.843 9.843 0 00-7.005-2.903c-5.45 0-9.887 4.434-9.889 9.884a9.88 9.88 0 001.693 5.525l.235.374-1 3.648 3.74-.981.306.18z"/></svg>
                WhatsApp
            </a>
            <button type="button" onclick="navigator.clipboard.writeText('{{ $post->url() }}').then(()=>{this.textContent='Copied ✓';setTimeout(()=>this.innerHTML=this.dataset.orig,1600);});" data-orig='<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>Copy link'
                    class="flex items-center gap-2 px-4 py-2 rounded-full text-xs font-medium transition-all cursor-pointer"
                    style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);color:rgba(248,250,252,0.75)"
                    onmouseover="this.style.background='rgba(26,86,219,0.15)';this.style.borderColor='rgba(26,86,219,0.35)';this.style.color='#fff'"
                    onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.borderColor='rgba(255,255,255,0.08)';this.style.color='rgba(248,250,252,0.75)'">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                Copy link
            </button>
        </div>
    </article>

    {{-- Related posts --}}
    @if($related->count() > 0)
        <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-20">
            <h2 class="fd font-bold text-xl sm:text-2xl mb-8" style="color:#f8fafc;letter-spacing:-0.01em">More in {{ $post->category?->name ?: 'the blog' }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
                @foreach($related as $r)
                    <a href="{{ route('blog.show', $r->slug) }}" class="group block">
                        <div class="aspect-[16/10] rounded-xl overflow-hidden mb-4" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07)">
                            @if($r->featuredImageUrl())
                                <img src="{{ $r->featuredImageUrl() }}" alt="{{ $r->featured_image_alt ?: $r->title }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
                            @else
                                <div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,rgba(26,86,219,0.15),rgba(59,130,246,0.03))"></div>
                            @endif
                        </div>
                        @if($r->category)
                            <span class="inline-block text-[11px] font-semibold uppercase tracking-widest mb-2" style="color:{{ $r->category->color }}">{{ $r->category->name }}</span>
                        @endif
                        <h3 class="fd font-bold text-base mb-2" style="color:#f8fafc;line-height:1.3">{{ $r->title }}</h3>
                        <p class="text-[11px]" style="color:rgba(255,255,255,0.4)">{{ $r->published_at?->format('M j, Y') }} · {{ $r->reading_time }} min read</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- CTA --}}
    <section class="max-w-3xl mx-auto px-4 sm:px-6 mt-20 mb-8">
        <div class="rounded-2xl p-8 sm:p-10 text-center" style="background:linear-gradient(135deg,rgba(26,86,219,0.08),rgba(59,130,246,0.03));border:1px solid rgba(59,130,246,0.18)">
            <h3 class="fd font-bold text-xl sm:text-2xl mb-2" style="color:#f8fafc;letter-spacing:-0.01em">Ready to track your numbers?</h3>
            <p class="text-sm sm:text-base mb-6" style="color:rgba(226,232,240,0.65)">Setup takes 2 minutes. Free forever — no credit card required.</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-full text-sm font-semibold transition-all"
               style="background:#1a56db;color:#fff;box-shadow:0 6px 22px rgba(26,86,219,0.35)"
               onmouseover="this.style.background='#3b82f6'" onmouseout="this.style.background='#1a56db'">
                Create free account →
            </a>
        </div>
    </section>
</div>
