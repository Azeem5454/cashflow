<div class="p-8 dark:text-white text-gray-900">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-600 font-body mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-amber-400 hover:text-amber-300 transition-colors">Admin</a>
        <span>/</span>
        <span class="text-slate-400">Subscriptions</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h1 class="font-display font-extrabold text-2xl text-white tracking-tight">Subscriptions</h1>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <p class="text-[10px] uppercase tracking-widest text-slate-600 font-body">MRR</p>
                <p class="font-mono font-bold text-xl text-emerald-400">${{ number_format($mrr) }}</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] uppercase tracking-widest text-slate-600 font-body">Active</p>
                <p class="font-mono font-bold text-xl text-blue-light">{{ number_format($activeSubs) }}</p>
            </div>
        </div>
    </div>

    {{-- Status filter --}}
    <div class="flex items-center gap-2 mb-5">
        @foreach(['' => 'All', 'active' => 'Active', 'canceled' => 'Canceled', 'on_grace_period' => 'Grace Period', 'ended' => 'Ended'] as $val => $label)
            <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-3 py-1.5 text-xs font-semibold font-body rounded-lg transition-all duration-150
                           {{ $statusFilter === $val ? 'bg-primary/15 text-blue-light border border-primary/30' : 'dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700 text-slate-400 hover:border-slate-600' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-slate-800">
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">User</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Status</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Stripe Sub ID</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Ends At</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse($subscriptions as $sub)
                    @php
                        $statusColor = match($sub->stripe_status) {
                            'active'   => 'bg-emerald-500/10 text-emerald-400',
                            'canceled' => 'bg-red-500/10 text-red-400',
                            default    => 'bg-slate-800 text-slate-500',
                        };
                    @endphp
                    <tr class="dark:hover:bg-slate-800/30 hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3.5">
                            @if($sub->owner)
                                <a href="{{ route('admin.users.show', $sub->owner) }}" wire:navigate
                                   class="text-white hover:text-blue-light font-body transition-colors">
                                    {{ $sub->owner->name }}
                                </a>
                                <p class="text-xs text-slate-500 font-body mt-0.5">{{ $sub->owner->email }}</p>
                            @else
                                <span class="text-slate-600 font-body text-xs">Unknown user</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide {{ $statusColor }}">
                                {{ $sub->stripe_status }}
                            </span>
                            @if($sub->ends_at && $sub->ends_at->isFuture() && $sub->stripe_status !== 'active')
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-amber-400/10 text-amber-400">
                                    grace
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 font-mono text-xs text-slate-500 max-w-[160px] truncate">{{ $sub->stripe_id }}</td>
                        <td class="px-5 py-3.5 font-mono text-xs {{ $sub->ends_at ? ($sub->ends_at->isFuture() ? 'text-amber-400' : 'text-red-400') : 'text-slate-600' }}">
                            {{ $sub->ends_at ? $sub->ends_at->format('d M Y') : '—' }}
                        </td>
                        <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ $sub->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-600 font-body">No subscriptions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($subscriptions->hasPages())
            <div class="px-5 py-3 border-t border-gray-200 dark:border-slate-800">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>

</div>
