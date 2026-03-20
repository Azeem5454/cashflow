<div class="p-4 sm:p-8 dark:text-white text-gray-900">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-600 font-body mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-amber-400 hover:text-amber-300 transition-colors">Admin</a>
        <span>/</span>
        <span class="text-slate-400">Users</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight">Users</h1>
        <span class="text-xs text-slate-500 font-body">{{ $users->total() }} total</span>
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-3 mb-5">
        <div class="flex-1 relative max-w-sm">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </div>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search by name or email…"
                   class="w-full pl-10 pr-4 py-2 text-sm font-body dark:bg-slate-800 bg-white border dark:border-slate-700 border-gray-300 dark:text-white text-gray-900 rounded-xl
                          dark:placeholder:text-slate-500 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                          transition-all duration-150">
        </div>

        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.outside="open = false"
                    class="flex items-center gap-1.5 px-3 py-2 dark:bg-slate-800 bg-white border dark:border-slate-700 border-gray-300 rounded-xl text-sm font-body
                           {{ $planFilter ? 'text-amber-400' : 'text-slate-400' }} hover:border-slate-600 transition-all duration-150">
                Plan: {{ $planFilter ?: 'All' }}
                <svg class="w-3 h-3 text-slate-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                </svg>
            </button>
            <div x-show="open" style="display:none;"
                 x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute top-full left-0 mt-1 w-36 dark:bg-slate-800 bg-white border dark:border-slate-700 border-gray-200 rounded-xl shadow-xl overflow-hidden z-20">
                @foreach(['' => 'All', 'pro' => 'Pro', 'free' => 'Free'] as $val => $label)
                    <button @click="$wire.set('planFilter', '{{ $val }}'); open = false"
                            class="w-full text-left px-4 py-2.5 text-sm font-body hover:bg-slate-700/50 transition-colors
                                   {{ $planFilter === $val ? 'text-white font-semibold' : 'text-slate-400' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead>
                <tr class="border-b border-gray-200 dark:border-slate-800">
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Name / Email</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Plan</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Businesses</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Joined</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Last Login</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse($users as $user)
                    <tr class="dark:hover:bg-slate-800/30 hover:bg-gray-50 transition-colors group">
                        <td class="px-5 py-3.5">
                            <a href="{{ route('admin.users.show', $user) }}" wire:navigate
                               class="font-medium dark:text-white text-gray-900 dark:hover:text-blue-light hover:text-primary transition-colors font-body">
                                {{ $user->name }}
                            </a>
                            <p class="text-xs text-slate-500 font-body mt-0.5">{{ $user->email }}</p>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide
                                         {{ $user->plan === 'pro' ? 'bg-amber-400/10 text-amber-400' : 'dark:bg-slate-800 dark:text-slate-400 bg-gray-100 text-gray-500' }}">
                                {{ $user->plan }}
                            </span>
                            @if($user->is_admin)
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-red-500/10 text-red-400">
                                    admin
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 font-mono text-slate-400">{{ $user->owned_businesses_count }}</td>
                        <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="px-5 py-3.5 font-mono text-xs text-slate-500">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : '—' }}
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity justify-end">
                                <a href="{{ route('admin.users.show', $user) }}" wire:navigate
                                   class="px-2.5 py-1 text-xs font-body bg-slate-700 text-slate-300 hover:bg-slate-600 rounded-lg transition-colors">
                                    View
                                </a>
                                @if($user->plan !== 'pro')
                                    <button wire:click="forcePro('{{ $user->id }}')"
                                            class="px-2.5 py-1 text-xs font-body bg-amber-400/10 text-amber-400 hover:bg-amber-400/20 rounded-lg transition-colors">
                                        → Pro
                                    </button>
                                @else
                                    <button wire:click="forceFree('{{ $user->id }}')"
                                            class="px-2.5 py-1 text-xs font-body bg-slate-700 text-slate-400 hover:bg-slate-600 rounded-lg transition-colors">
                                        → Free
                                    </button>
                                @endif
                                @if(!$user->is_admin)
                                    <button wire:click="deleteUser('{{ $user->id }}')"
                                            wire:confirm="Delete {{ $user->name }}? This cannot be undone."
                                            class="px-2.5 py-1 text-xs font-body bg-red-500/10 text-red-400 hover:bg-red-500/20 rounded-lg transition-colors">
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-600 font-body">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($users->hasPages())
            <div class="px-5 py-3 border-t border-gray-200 dark:border-slate-800">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
