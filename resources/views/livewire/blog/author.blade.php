<div>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 sm:pt-16 pb-4">

        {{-- Breadcrumb --}}
        <nav class="mb-8 text-sm" style="color:rgba(226,232,240,0.45)">
            <a href="{{ route('blog.index') }}" class="hover:underline">Blog</a>
            <span class="mx-2">/</span>
            <span>{{ $author->name }}</span>
        </nav>

        {{-- Profile --}}
        <div class="flex flex-col sm:flex-row sm:items-start gap-6 mb-12 pb-10" style="border-bottom:1px solid rgba(255,255,255,0.07)">
            <div class="w-20 h-20 rounded-full flex items-center justify-center text-2xl font-black flex-shrink-0"
                 style="background:linear-gradient(135deg,#1a56db,#3b82f6);color:#fff">
                {{ strtoupper(substr($author->name, 0, 1)) }}
            </div>

            <div class="flex-1">
                <h1 class="fd font-black leading-tight" style="color:#f8fafc;font-size:clamp(1.9rem,4vw,2.6rem);letter-spacing:-0.02em">
                    {{ $author->name }}
                </h1>

                @if($author->author_role)
                    <p class="mt-1 text-sm font-semibold" style="color:rgba(59,130,246,0.9)">{{ $author->author_role }}</p>
                @endif

                <p class="mt-4 max-w-2xl text-base sm:text-lg" style="color:rgba(226,232,240,0.6);line-height:1.7">
                    {{ $author->author_bio }}
                </p>

                <p class="mt-4 text-sm" style="color:rgba(226,232,240,0.4)">
                    {{ $posts->total() }} {{ \Illuminate\Support\Str::plural('post', $posts->total()) }}
                </p>
            </div>
        </div>

        {{-- Posts --}}
        @if($posts->isEmpty())
            <p class="py-16 text-center" style="color:rgba(226,232,240,0.5)">Nothing published yet.</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-7">
                @foreach($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}"
                       class="blog-card group flex flex-col rounded-2xl overflow-hidden transition-all duration-300"
                       style="background:#0d1526;border:1px solid rgba(255,255,255,0.07)">
                        <div class="aspect-[16/10] overflow-hidden relative" style="background:rgba(255,255,255,0.02)">
                            @if($post->featuredImageUrl())
                                <img src="{{ $post->featuredImageUrl() }}"
                                     alt="{{ $post->featured_image_alt ?: $post->title }}"
                                     width="1200" height="630" loading="lazy" decoding="async"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.05]">
                            @endif
                        </div>

                        <div class="p-5 sm:p-6 flex-1 flex flex-col">
                            <div class="flex items-center gap-3 mb-3">
                                @if($post->category)
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full whitespace-nowrap"
                                          style="background:{{ $post->category->color }}22;color:{{ $post->category->color }};border:1px solid {{ $post->category->color }}33">
                                        {{ $post->category->name }}
                                    </span>
                                @endif
                                <time class="text-xs" datetime="{{ $post->published_at?->toIso8601String() }}" style="color:rgba(226,232,240,0.4)">
                                    {{ $post->published_at?->format('M j, Y') }}
                                </time>
                            </div>

                            <h2 class="fd font-bold text-lg leading-snug mb-2" style="color:#f8fafc">{{ $post->title }}</h2>
                            <p class="text-sm flex-1" style="color:rgba(226,232,240,0.55);line-height:1.6">{{ $post->excerpt }}</p>
                            <p class="mt-4 text-xs" style="color:rgba(226,232,240,0.35)">{{ $post->reading_time }} min read</p>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($posts->hasPages())
                <div class="mt-14">
                    {{ $posts->links('vendor.pagination.blog') }}
                </div>
            @endif
        @endif
    </div>
</div>
