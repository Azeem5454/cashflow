<div class="p-4 sm:p-8 max-w-4xl mx-auto">

    <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('admin.blog.index') }}" class="inline-flex items-center gap-1 text-xs dark:text-slate-400 text-gray-500 hover:text-primary dark:hover:text-blue-light mb-2">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                Back to posts
            </a>
            <h1 class="font-heading font-bold text-2xl dark:text-white text-gray-900">Blog Categories</h1>
            <p class="text-sm dark:text-slate-400 text-gray-500 mt-1">Group posts by topic. Each category gets its own page at <code class="text-xs dark:text-slate-300 text-gray-700">/blog/category/slug</code>.</p>
        </div>
    </div>

    @if($savedMessage)
        <div class="mb-4 px-4 py-2.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-sm text-emerald-700 dark:text-emerald-400"
             x-data x-init="setTimeout(() => $wire.set('savedMessage', ''), 2800)">
            ✓ {{ $savedMessage }}
        </div>
    @endif

    {{-- Form --}}
    <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5 mb-6">
        <h2 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-4">
            {{ $editingId ? 'Edit category' : 'Add category' }}
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-[11px] font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-1.5">Name</label>
                <input type="text" wire:model.live.debounce.500ms="name" placeholder="Growing Your Business"
                       class="w-full px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[11px] font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-1.5">Slug (URL-safe)</label>
                <input type="text" wire:model="slug" placeholder="growing-your-business"
                       class="w-full px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary/40">
                @error('slug') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-1.5">Description</label>
                <textarea wire:model="description" rows="2" maxlength="280" placeholder="Shown on the category page header."
                          class="w-full px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"></textarea>
            </div>
            <div>
                <label class="block text-[11px] font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-1.5">Accent colour</label>
                <div class="flex items-center gap-2">
                    <input type="color" wire:model="color" class="w-10 h-10 rounded border dark:border-slate-700 border-gray-200 cursor-pointer bg-transparent">
                    <input type="text" wire:model="color" placeholder="#1a56db" class="flex-1 px-3 py-2 rounded-lg dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 border dark:text-white text-gray-900 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary/40">
                </div>
                @error('color') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="mt-5 flex items-center gap-2">
            <button wire:click="save"
                    class="px-4 py-2 bg-primary hover:bg-accent text-white text-sm font-semibold rounded-lg shadow-lg shadow-primary/25 transition-all">
                {{ $editingId ? 'Update category' : 'Add category' }}
            </button>
            @if($editingId)
                <button wire:click="cancelEdit" class="px-3.5 py-2 text-xs font-medium rounded-lg dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700 hover:bg-gray-200 transition-colors">Cancel</button>
            @endif
        </div>
    </div>

    {{-- List --}}
    <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px]">
                <thead class="dark:bg-slate-800/50 bg-gray-50">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-widest dark:text-slate-500 text-gray-500">
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Slug</th>
                        <th class="px-5 py-3 text-right">Posts</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="dark:divide-slate-800 divide-gray-100 divide-y">
                    @forelse($categories as $c)
                        <tr class="dark:hover:bg-slate-800/40 hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background:{{ $c->color }}"></span>
                                    <div>
                                        <div class="font-semibold text-sm dark:text-white text-gray-900">{{ $c->name }}</div>
                                        @if($c->description)<div class="text-xs dark:text-slate-500 text-gray-400 line-clamp-1">{{ $c->description }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-xs dark:text-slate-400 text-gray-500 font-mono">/{{ $c->slug }}</td>
                            <td class="px-5 py-3 text-right text-xs dark:text-slate-300 text-gray-700 font-mono">{{ $c->post_count }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="startEdit('{{ $c->id }}')" title="Edit"
                                            class="p-1.5 rounded-md dark:text-slate-500 text-gray-400 dark:hover:text-primary hover:text-primary dark:hover:bg-slate-800 hover:bg-gray-100 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                    </button>
                                    <button wire:click="deleteCategory('{{ $c->id }}')" wire:confirm="Delete this category? Posts inside it will become uncategorised." title="Delete"
                                            class="p-1.5 rounded-md dark:text-slate-500 text-gray-400 dark:hover:text-red-400 hover:text-red-500 dark:hover:bg-slate-800 hover:bg-gray-100 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9M18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-sm dark:text-slate-500 text-gray-400">No categories yet. Add one above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
