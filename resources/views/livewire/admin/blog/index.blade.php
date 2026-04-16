<div class="p-4 sm:p-8">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3 mb-6">
        <div>
            <h1 class="font-heading font-bold text-2xl dark:text-white text-gray-900">Blog Posts</h1>
            <p class="text-sm dark:text-slate-400 text-gray-500 mt-1">Write articles to drive search traffic and build authority.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.blog.autopilot') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-lg dark:bg-violet-500/15 bg-violet-50 dark:text-violet-300 text-violet-700 dark:border-violet-500/30 border border-violet-200 dark:hover:bg-violet-500/25 hover:bg-violet-100 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                Autopilot
            </a>
            <a href="{{ route('admin.blog.categories') }}"
               class="px-3.5 py-2 text-xs font-medium rounded-lg dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700 hover:bg-gray-200 transition-colors">
                Manage categories
            </a>
            <a href="{{ route('admin.blog.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-accent text-white text-sm font-semibold rounded-lg shadow-lg shadow-primary/25 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                New post
            </a>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-4 mb-5 flex flex-wrap items-center gap-3">
        {{-- Status tabs --}}
        <div class="flex items-center gap-1 dark:bg-slate-800 bg-gray-100 rounded-lg p-1">
            @foreach([
                ['all',       'All',       $counts['all']],
                ['published', 'Published', $counts['published']],
                ['draft',     'Drafts',    $counts['draft']],
            ] as [$k, $label, $count])
                <button wire:click="$set('statusFilter', '{{ $k }}')"
                    class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors
                           {{ $statusFilter === $k ? 'dark:bg-[#1e293b] bg-white dark:text-white text-gray-900 shadow-sm' : 'dark:text-slate-400 text-gray-500 hover:dark:text-slate-200 hover:text-gray-700' }}">
                    {{ $label }}
                    <span class="ml-1 text-[10px] opacity-70">{{ $count }}</span>
                </button>
            @endforeach
        </div>

        {{-- Category picker --}}
        <select wire:model.live="categoryFilter"
                class="dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/40">
            <option value="all">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>

        {{-- Sort --}}
        <select wire:model.live="sort"
                class="dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/40">
            <option value="newest">Newest</option>
            <option value="oldest">Oldest</option>
            <option value="most_viewed">Most viewed</option>
            <option value="title">Title A-Z</option>
        </select>

        {{-- Search --}}
        <div class="relative flex-1 min-w-[200px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search title or excerpt…"
                   class="w-full pl-9 pr-3 py-1.5 dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/40">
        </div>
    </div>

    {{-- Posts table --}}
    <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl overflow-hidden">
        @if($posts->isEmpty())
            <div class="p-12 text-center">
                <div class="w-14 h-14 rounded-xl mx-auto mb-4 flex items-center justify-center dark:bg-slate-800 bg-gray-100">
                    <svg class="w-6 h-6 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                </div>
                <p class="text-sm dark:text-slate-400 text-gray-500">No posts match your filters.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px]">
                    <thead class="dark:bg-slate-800/50 bg-gray-50">
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-widest dark:text-slate-500 text-gray-500">
                            <th class="px-5 py-3">Post</th>
                            <th class="px-5 py-3">Category</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Published</th>
                            <th class="px-5 py-3 text-right">Views</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="dark:divide-slate-800 divide-gray-100 divide-y">
                        @foreach($posts as $p)
                            <tr class="dark:hover:bg-slate-800/40 hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($p->is_featured)
                                            <span class="flex-shrink-0 w-5 h-5 rounded flex items-center justify-center" style="background:rgba(59,130,246,0.2)" title="Featured">
                                                <svg class="w-3 h-3" viewBox="0 0 20 20" fill="#3b82f6"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.blog.edit', ['id' => $p->id]) }}" class="font-semibold text-sm dark:text-white text-gray-900 hover:text-primary dark:hover:text-blue-light transition-colors line-clamp-1">{{ $p->title }}</a>
                                            <p class="text-xs dark:text-slate-500 text-gray-400 mt-0.5 font-mono truncate max-w-xs">/{{ $p->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @if($p->category)
                                        <span class="inline-flex items-center gap-1.5 text-xs">
                                            <span class="w-2 h-2 rounded-full" style="background:{{ $p->category->color }}"></span>
                                            <span class="dark:text-slate-300 text-gray-700">{{ $p->category->name }}</span>
                                        </span>
                                    @else
                                        <span class="text-xs dark:text-slate-600 text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if($p->status === 'published')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" style="background:rgba(22,163,74,0.15);color:#22c55e;border:1px solid rgba(22,163,74,0.25)">
                                            <span class="w-1.5 h-1.5 rounded-full" style="background:#22c55e"></span>
                                            Live
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider dark:bg-slate-700/60 bg-gray-200 dark:text-slate-400 text-gray-600">Draft</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs dark:text-slate-400 text-gray-500">{{ $p->published_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-5 py-4 text-right text-xs dark:text-slate-400 text-gray-500 font-mono">{{ number_format($p->view_count) }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="toggleFeatured('{{ $p->id }}')" title="{{ $p->is_featured ? 'Unfeature' : 'Feature' }}"
                                                class="p-1.5 rounded-md dark:text-slate-500 text-gray-400 dark:hover:text-amber-400 hover:text-amber-500 dark:hover:bg-slate-800 hover:bg-gray-100 transition-all">
                                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="{{ $p->is_featured ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        </button>
                                        <button wire:click="togglePublish('{{ $p->id }}')" title="{{ $p->status === 'published' ? 'Unpublish' : 'Publish' }}"
                                                class="p-1.5 rounded-md dark:text-slate-500 text-gray-400 dark:hover:text-emerald-400 hover:text-emerald-500 dark:hover:bg-slate-800 hover:bg-gray-100 transition-all">
                                            @if($p->status === 'published')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                            @endif
                                        </button>
                                        <a href="{{ route('admin.blog.edit', ['id' => $p->id]) }}" title="Edit"
                                           class="p-1.5 rounded-md dark:text-slate-500 text-gray-400 dark:hover:text-primary hover:text-primary dark:hover:bg-slate-800 hover:bg-gray-100 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                        </a>
                                        @if($p->status === 'published')
                                            <a href="{{ route('blog.show', $p->slug) }}" target="_blank" rel="noopener" title="View live"
                                               class="p-1.5 rounded-md dark:text-slate-500 text-gray-400 dark:hover:text-emerald-400 hover:text-emerald-500 dark:hover:bg-slate-800 hover:bg-gray-100 transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                            </a>
                                        @endif
                                        <button wire:click="deletePost('{{ $p->id }}')" wire:confirm="Delete this post? This cannot be undone." title="Delete"
                                                class="p-1.5 rounded-md dark:text-slate-500 text-gray-400 dark:hover:text-red-400 hover:text-red-500 dark:hover:bg-slate-800 hover:bg-gray-100 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($posts->hasPages())
        <div class="mt-5">{{ $posts->withQueryString()->links() }}</div>
    @endif
</div>
