<div class="p-4 sm:p-8 dark:text-white text-gray-900">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-600 font-body mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-amber-400 hover:text-amber-300 transition-colors">Admin</a>
        <span>/</span>
        <span class="text-slate-400">Businesses</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight">Businesses</h1>
        <span class="text-xs text-slate-500 font-body">{{ $businesses->total() }} total</span>
    </div>

    {{-- Search --}}
    <div class="mb-5 max-w-sm">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </div>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search by name or owner email…"
                   class="w-full pl-10 pr-4 py-2 text-sm font-body dark:bg-slate-800 bg-white border dark:border-slate-700 border-gray-300 dark:text-white text-gray-900 rounded-xl
                          dark:placeholder:text-slate-500 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                          transition-all duration-150">
        </div>
    </div>

    {{-- Table --}}
    <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead>
                <tr class="border-b border-gray-200 dark:border-slate-800">
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body w-5"></th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Business</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Owner</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Members</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Books</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Entries</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                @forelse($businesses as $biz)
                    {{-- Row --}}
                    <tr wire:key="biz-row-{{ $biz->id }}"
                        wire:click="toggleExpand('{{ $biz->id }}')"
                        class="cursor-pointer transition-colors
                               {{ $expandedId === $biz->id ? 'dark:bg-slate-800 bg-blue-50' : 'dark:hover:bg-slate-800/30 hover:bg-gray-50' }}">
                        <td class="pl-5 pr-0 py-3.5 w-5">
                            @if($expandedId === $biz->id)
                                {{-- Down chevron (expanded) --}}
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                </svg>
                            @else
                                {{-- Right chevron (collapsed) --}}
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                                </svg>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="font-medium dark:text-white text-gray-900 font-body">{{ $biz->name }}</p>
                            <p class="text-xs text-slate-600 font-mono mt-0.5">{{ $biz->currency }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($biz->owner)
                                <a href="{{ route('admin.users.show', $biz->owner) }}" wire:navigate
                                   wire:click.stop
                                   class="dark:text-slate-300 text-gray-700 hover:text-blue-light font-body transition-colors">
                                    {{ $biz->owner->name }}
                                </a>
                                <p class="text-xs text-slate-600 font-body mt-0.5">{{ $biz->owner->email }}</p>
                            @else
                                <span class="text-slate-600 font-body text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 font-mono dark:text-slate-400 text-gray-500">{{ $biz->members_count }}</td>
                        <td class="px-5 py-3.5 font-mono dark:text-slate-400 text-gray-500">{{ $biz->books_count }}</td>
                        <td class="px-5 py-3.5 font-mono dark:text-slate-400 text-gray-500">{{ number_format($biz->entries_count) }}</td>
                        <td class="px-5 py-3.5 font-mono text-xs dark:text-slate-500 text-gray-400">{{ $biz->created_at->format('d M Y') }}</td>
                    </tr>

                    {{-- Expanded detail panel --}}
                    @if($expandedId === $biz->id && $expandedBusiness)
                        <tr wire:key="biz-detail-{{ $biz->id }}">
                            <td colspan="7" class="p-0">
                                <div class="px-6 py-5 dark:bg-slate-800 bg-slate-50 border-t border-b border-gray-200 dark:border-slate-700">
                                    <div class="grid md:grid-cols-2 gap-5">

                                        {{-- Members --}}
                                        <div>
                                            <h4 class="text-[10px] font-bold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-3">
                                                Members ({{ $expandedBusiness->members->count() }})
                                            </h4>
                                            @forelse($expandedBusiness->members as $member)
                                                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-200 dark:border-slate-700' : '' }}">
                                                    <div class="flex items-center gap-2.5 min-w-0">
                                                        <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">
                                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                                        </div>
                                                        <div class="min-w-0">
                                                            <a href="{{ route('admin.users.show', $member) }}" wire:navigate
                                                               class="text-sm font-body dark:text-white text-gray-900 hover:text-blue-light transition-colors truncate block">
                                                                {{ $member->name }}
                                                            </a>
                                                            <p class="text-[10px] dark:text-slate-500 text-gray-400 font-body truncate">{{ $member->email }}</p>
                                                        </div>
                                                    </div>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide ml-2 flex-shrink-0
                                                                 {{ $member->pivot->role === 'owner' ? 'bg-primary/10 text-blue-light' : ($member->pivot->role === 'editor' ? 'bg-emerald-500/10 text-emerald-400' : 'dark:bg-slate-700 bg-gray-200 dark:text-slate-400 text-gray-500') }}">
                                                        {{ $member->pivot->role }}
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-xs text-slate-600 font-body">No members.</p>
                                            @endforelse
                                        </div>

                                        {{-- Books --}}
                                        <div>
                                            <h4 class="text-[10px] font-bold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-3">
                                                Books ({{ $expandedBusiness->books->count() }})
                                            </h4>
                                            @forelse($expandedBusiness->books as $book)
                                                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-200 dark:border-slate-700' : '' }}">
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-body dark:text-white text-gray-900 truncate">{{ $book->name }}</p>
                                                        <p class="text-[10px] dark:text-slate-500 text-gray-400 font-body mt-0.5">
                                                            {{ $book->entries_count }} {{ \Illuminate\Support\Str::plural('entry', $book->entries_count) }}
                                                            · updated {{ $book->updated_at->diffForHumans() }}
                                                        </p>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-xs text-slate-600 font-body">No books.</p>
                                            @endforelse
                                        </div>

                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-600 font-body">No businesses found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($businesses->hasPages())
            <div class="px-5 py-3 border-t border-gray-200 dark:border-slate-800">
                {{ $businesses->links() }}
            </div>
        @endif
    </div>

</div>
