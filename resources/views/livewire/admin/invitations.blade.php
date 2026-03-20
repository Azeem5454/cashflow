<div class="p-4 sm:p-8 dark:text-white text-gray-900">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-600 font-body mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-amber-400 hover:text-amber-300 transition-colors">Admin</a>
        <span>/</span>
        <span class="text-slate-400">Invitations</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight">Invitations</h1>
        <span class="text-xs text-slate-500 font-body">{{ $invitations->total() }} total</span>
    </div>

    {{-- Status filter --}}
    <div class="flex items-center gap-2 mb-5">
        @foreach(['' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'expired' => 'Expired'] as $val => $label)
            <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-3 py-1.5 text-xs font-semibold font-body rounded-lg transition-all duration-150
                           {{ $statusFilter === $val ? 'bg-primary/15 text-blue-light border border-primary/30' : 'dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700 text-slate-400 hover:border-slate-600' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
            <thead>
                <tr class="border-b border-gray-200 dark:border-slate-800">
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Email</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Business</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Role</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Status</th>
                    <th class="text-left px-5 py-3 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Expires</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse($invitations as $inv)
                    @php
                        $invStatus = $inv->accepted_at ? 'accepted'
                            : ($inv->expires_at->isPast() ? 'expired' : 'pending');
                        $statusColor = match($invStatus) {
                            'accepted' => 'bg-emerald-500/10 text-emerald-400',
                            'expired'  => 'bg-red-500/10 text-red-400',
                            default    => 'bg-amber-400/10 text-amber-400',
                        };
                    @endphp
                    <tr class="dark:hover:bg-slate-800/30 hover:bg-gray-50 transition-colors group">
                        <td class="px-5 py-3.5 dark:text-white text-gray-900 font-body">{{ $inv->email }}</td>
                        <td class="px-5 py-3.5 text-slate-400 font-body">{{ $inv->business?->name ?? '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide dark:bg-slate-800 dark:text-slate-400 bg-gray-100 text-gray-500">
                                {{ $inv->role }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide {{ $statusColor }}">
                                {{ $invStatus }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 font-mono text-xs {{ $inv->expires_at->isPast() ? 'text-red-400' : 'text-slate-500' }}">
                            {{ $inv->expires_at->format('d M Y') }}
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity justify-end">
                                @if($invStatus === 'pending' || $invStatus === 'expired')
                                    <button wire:click="resendInvitation('{{ $inv->id }}')"
                                            class="px-2.5 py-1 text-xs font-body bg-primary/10 text-blue-light hover:bg-primary/20 rounded-lg transition-colors">
                                        Resend
                                    </button>
                                @endif
                                @if($invStatus !== 'accepted')
                                    <button wire:click="cancelInvitation('{{ $inv->id }}')"
                                            wire:confirm="Cancel this invitation?"
                                            class="px-2.5 py-1 text-xs font-body bg-red-500/10 text-red-400 hover:bg-red-500/20 rounded-lg transition-colors">
                                        Cancel
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-600 font-body">No invitations found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($invitations->hasPages())
            <div class="px-5 py-3 border-t border-gray-200 dark:border-slate-800">
                {{ $invitations->links() }}
            </div>
        @endif
    </div>

</div>
