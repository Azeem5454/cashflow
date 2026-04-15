<div class="p-4 sm:p-8 max-w-6xl mx-auto">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div>
            <a href="{{ route('admin.blog.index') }}" class="inline-flex items-center gap-1 text-xs dark:text-slate-400 text-gray-500 hover:text-primary dark:hover:text-blue-light mb-2">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                Back to posts
            </a>
            <h1 class="font-heading font-bold text-2xl dark:text-white text-gray-900">
                {{ $post ? 'Edit post' : 'New post' }}
            </h1>
        </div>
        <div class="flex items-center gap-2">
            @if($post && $post->status === 'published')
                <a href="{{ route('blog.show', $post->slug) }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-lg dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700 hover:bg-gray-200 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    View live
                </a>
            @endif
            <button wire:click="togglePreview"
                    class="px-3.5 py-2 text-xs font-medium rounded-lg dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700 hover:bg-gray-200 transition-colors">
                {{ $showPreview ? 'Hide preview' : 'Preview' }}
            </button>
            <button wire:click="save" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-accent text-white text-sm font-semibold rounded-lg shadow-lg shadow-primary/25 transition-all disabled:opacity-60">
                <svg wire:loading wire:target="save" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/></svg>
                Save
            </button>
            @if($post)
                <button wire:click="deletePost" wire:confirm="Delete this post permanently? This cannot be undone."
                        title="Delete"
                        class="p-2 rounded-lg dark:text-slate-500 text-gray-400 dark:hover:text-red-400 hover:text-red-500 dark:hover:bg-slate-800 hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9M18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                </button>
            @endif
        </div>
    </div>

    @if($savedMessage)
        <div class="mb-5 px-4 py-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-sm text-emerald-700 dark:text-emerald-400"
             x-data x-init="setTimeout(() => $wire.set('savedMessage', ''), 2800)">
            ✓ {{ $savedMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main form column --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Title --}}
            <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5">
                <label class="block text-xs font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-2">Title</label>
                <input type="text" wire:model.live.debounce.500ms="title" placeholder="How to forecast cash flow for a small business"
                       class="w-full px-0 py-1 bg-transparent border-0 font-heading font-bold text-2xl dark:text-white text-gray-900 focus:outline-none focus:ring-0 placeholder:dark:text-slate-600 placeholder:text-gray-300">
                @error('title') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror

                <div class="mt-4 pt-4 dark:border-slate-800 border-t border-gray-100">
                    <label class="block text-[11px] font-semibold dark:text-slate-500 text-gray-500 uppercase tracking-wider mb-1.5">URL slug</label>
                    <div class="flex items-center gap-2">
                        <span class="text-xs dark:text-slate-500 text-gray-400 font-mono">{{ url('/blog') }}/</span>
                        <input type="text" wire:model="slug" placeholder="how-to-forecast-cash-flow"
                               class="flex-1 px-2 py-1 text-sm rounded dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 font-mono focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </div>
                    @error('slug') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Excerpt --}}
            <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5">
                <label class="block text-xs font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-2">Excerpt</label>
                <textarea wire:model="excerpt" rows="2" maxlength="400"
                          placeholder="One-sentence hook that appears in search results, social cards, and the blog listing."
                          class="w-full px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"></textarea>
                <p class="mt-1.5 text-[11px] dark:text-slate-500 text-gray-400">{{ strlen($excerpt) }}/400</p>
                @error('excerpt') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Body — markdown editor + live preview --}}
            <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 dark:border-slate-800 border-b border-gray-100">
                    <label class="text-xs font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider">Body (Markdown)</label>
                    <a href="https://commonmark.org/help/" target="_blank" rel="noopener" class="text-[11px] dark:text-slate-500 text-gray-400 hover:text-primary dark:hover:text-blue-light inline-flex items-center gap-1">
                        Markdown cheatsheet
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    </a>
                </div>
                <div class="grid {{ $showPreview ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1' }}">
                    <textarea wire:model.live.debounce.600ms="body_markdown"
                              wire:change="refreshPreview"
                              rows="24"
                              placeholder="## Introduction&#10;&#10;Write your article in Markdown here. Headings, **bold**, *italics*, [links](https://example.com), lists, and code blocks are supported.&#10;&#10;### Subheading&#10;&#10;- Point one&#10;- Point two"
                              class="w-full px-5 py-4 bg-transparent dark:text-white text-gray-900 text-[15px] leading-relaxed focus:outline-none font-mono resize-none"
                              style="font-family: 'Geist Mono', ui-monospace, monospace;"></textarea>
                    @if($showPreview)
                        <div class="dark:border-slate-800 dark:border-l border-gray-100 border-l p-5 overflow-y-auto max-h-[640px]" style="background:rgba(255,255,255,0.01)">
                            <p class="text-[10px] font-semibold uppercase tracking-widest dark:text-slate-600 text-gray-400 mb-3">Preview</p>
                            <div class="prose-preview">{!! $previewHtml ?: '<p class="text-sm dark:text-slate-500 text-gray-400">Start typing — preview renders here.</p>' !!}</div>
                        </div>
                    @endif
                </div>
                <p class="px-5 py-2 text-[11px] dark:text-slate-500 text-gray-400 dark:border-slate-800 border-t border-gray-100">
                    {{ str_word_count(strip_tags($body_markdown)) }} words · ~{{ \App\Models\BlogPost::calcReadingTime($body_markdown) }} min read
                </p>
            </div>

            {{-- SEO --}}
            <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5">
                <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-1">SEO</h3>
                <p class="text-xs dark:text-slate-500 text-gray-400 mb-4">Override the default title and description that search engines and social cards show. Leave blank to auto-use title + excerpt.</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-1.5">SEO title</label>
                        <input type="text" wire:model="seo_title" maxlength="160" placeholder="{{ $title ?: 'Auto from title' }}"
                               class="w-full px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <p class="mt-1 text-[11px] dark:text-slate-500 text-gray-400">{{ strlen($seo_title) }}/60 recommended</p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-1.5">SEO description</label>
                        <textarea wire:model="seo_description" rows="2" maxlength="280"
                                  placeholder="{{ $excerpt ?: 'Auto from excerpt' }}"
                                  class="w-full px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"></textarea>
                        <p class="mt-1 text-[11px] dark:text-slate-500 text-gray-400">{{ strlen($seo_description) }}/160 recommended</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-5">

            {{-- Status + featured --}}
            <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5">
                <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-4">Publishing</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                        <select wire:model="status"
                                class="w-full px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-1.5">Publish date</label>
                        <input type="datetime-local" wire:model="published_at"
                               class="w-full px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <p class="mt-1 text-[11px] dark:text-slate-500 text-gray-400">Leave blank to auto-stamp when you publish.</p>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer pt-2">
                        <input type="checkbox" wire:model="is_featured" class="sr-only peer">
                        <div class="w-9 h-5 rounded-full relative transition-colors dark:bg-slate-700 bg-gray-300 peer-checked:bg-primary">
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-sm dark:text-slate-300 text-gray-700">Featured on blog homepage</span>
                    </label>
                    <p class="text-[11px] dark:text-slate-500 text-gray-400 -mt-2 pl-11">Only one post is featured at a time. Toggling this unfeatures the current pinned post.</p>
                </div>
            </div>

            {{-- Category --}}
            <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5">
                <label class="block text-xs font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-2">Category</label>
                <select wire:model="category_id"
                        class="w-full px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="">Uncategorised</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('admin.blog.categories') }}" class="mt-2 inline-block text-[11px] dark:text-slate-500 text-gray-400 hover:text-primary dark:hover:text-blue-light">+ Manage categories</a>
            </div>

            {{-- Featured image --}}
            <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5">
                <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-1">Featured image</h3>
                <p class="text-xs dark:text-slate-500 text-gray-400 mb-3">Used as hero on the post page, in the blog grid, and as the social-preview (OG) image. 1200×630 recommended.</p>

                @if($post && $post->featuredImageUrl() && ! $removeFeaturedImage)
                    <div class="mb-3 relative rounded-lg overflow-hidden dark:border-slate-700 border border-gray-200">
                        <img src="{{ $post->featuredImageUrl() }}" class="w-full aspect-[16/10] object-cover" alt="">
                        <button wire:click="removeExistingImage" class="absolute top-2 right-2 p-1.5 rounded-full bg-black/70 text-white hover:bg-black">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endif

                <input type="file" wire:model="featuredImageUpload" accept="image/png,image/jpeg,image/jpg,image/webp"
                       class="block w-full text-xs dark:text-slate-400 text-gray-500
                              file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium
                              file:bg-primary/10 file:text-primary dark:file:text-blue-light file:cursor-pointer hover:file:bg-primary/20">
                <div wire:loading wire:target="featuredImageUpload" class="mt-2 text-xs dark:text-slate-400 text-gray-500">Uploading…</div>
                @error('featuredImageUpload') <p class="mt-2 text-xs text-red-400">{{ $message }}</p> @enderror

                <div class="mt-4">
                    <label class="block text-[11px] font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-1.5">Alt text</label>
                    <input type="text" wire:model="featured_image_alt" maxlength="200" placeholder="Describe the image for screen readers + SEO"
                           class="w-full px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>
            </div>

            {{-- Stats (edit mode) --}}
            @if($post)
                <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5">
                    <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-3">Stats</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between"><span class="dark:text-slate-400 text-gray-500">Views</span><span class="font-mono dark:text-white text-gray-900">{{ number_format($post->view_count) }}</span></div>
                        <div class="flex justify-between"><span class="dark:text-slate-400 text-gray-500">Created</span><span class="dark:text-slate-300 text-gray-700">{{ $post->created_at->format('M j, Y') }}</span></div>
                        <div class="flex justify-between"><span class="dark:text-slate-400 text-gray-500">Last updated</span><span class="dark:text-slate-300 text-gray-700">{{ $post->updated_at->diffForHumans() }}</span></div>
                    </div>
                </div>
            @endif
        </aside>
    </div>
</div>
