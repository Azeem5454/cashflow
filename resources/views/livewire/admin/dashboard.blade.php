<div class="p-8 dark:text-white text-gray-900">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-600 font-body mb-6">
        <span class="text-amber-400">Admin</span>
        <span>/</span>
        <span class="text-slate-400">Dashboard</span>
    </div>

    <h1 class="font-display font-extrabold text-2xl text-white tracking-tight mb-6">Overview</h1>

    {{-- ===== KPI STRIP ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-8">

        @php
            $kpis = [
                ['label' => 'Total Users',    'value' => number_format($totalUsers),  'color' => 'text-blue-light',  'bg' => 'bg-primary/10'],
                ['label' => 'Pro Users',       'value' => number_format($proUsers),    'color' => 'text-amber-400',   'bg' => 'bg-amber-400/10'],
                ['label' => 'MRR',             'value' => '$' . number_format($mrr),  'color' => 'text-emerald-400', 'bg' => 'bg-emerald-500/10'],
                ['label' => 'Active Subs',     'value' => number_format($activeSubs),  'color' => 'text-emerald-400', 'bg' => 'bg-emerald-500/10'],
                ['label' => 'Churned (30d)',   'value' => number_format($churned30),   'color' => 'text-red-400',     'bg' => 'bg-red-500/10'],
            ];
        @endphp

        @foreach($kpis as $kpi)
            <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 font-body mb-2">{{ $kpi['label'] }}</p>
                <p class="font-mono font-bold text-2xl {{ $kpi['color'] }} leading-none">{{ $kpi['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mb-8">

        {{-- ===== SIGNUPS CHART ===== --}}
        <div class="lg:col-span-2 dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-5">
            <h2 class="font-heading font-bold text-sm text-white mb-4">New Signups — Last 30 Days</h2>
            @php $maxCount = max(1, collect($chartDays)->max('count')); @endphp
            <div class="flex items-end gap-0.5 h-24">
                @foreach($chartDays as $i => $day)
                    @php $pct = ($day['count'] / $maxCount) * 100; @endphp
                    <div class="flex-1 flex flex-col items-center justify-end h-full group relative"
                         title="{{ $day['day'] }}: {{ $day['count'] }} signup(s)">
                        <div class="w-full rounded-sm transition-colors duration-150
                                    {{ $day['count'] > 0 ? 'bg-primary/60 group-hover:bg-primary' : 'bg-slate-800' }}"
                             style="height: {{ max(2, $pct) }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between mt-2 text-[9px] text-slate-600 font-body">
                <span>{{ $chartDays->first()['day'] }}</span>
                <span>{{ $chartDays->last()['day'] }}</span>
            </div>
        </div>

        {{-- ===== TOP BUSINESSES ===== --}}
        <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-5">
            <h2 class="font-heading font-bold text-sm text-white mb-4">Top Businesses by Entries</h2>
            <div class="space-y-2.5">
                @forelse($topBusinesses as $biz)
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-sm dark:text-white text-gray-900 font-body truncate">{{ $biz->name }}</p>
                            <p class="text-xs text-slate-500 font-body truncate">{{ $biz->owner?->email }}</p>
                        </div>
                        <span class="font-mono text-xs text-blue-light ml-3 flex-shrink-0">{{ number_format($biz->entries_count) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-600 font-body">No businesses yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== RECENT SIGNUPS ===== --}}
    <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="font-heading font-bold text-sm text-white">Recent Signups</h2>
            <a href="{{ route('admin.users') }}" wire:navigate class="text-xs text-primary hover:text-accent font-body transition-colors">View all →</a>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-slate-800">
                    <th class="text-left px-5 py-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Name</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Email</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Plan</th>
                    <th class="text-left px-5 py-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 font-body">Joined</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @foreach($recentUsers as $user)
                    <tr class="dark:hover:bg-slate-800/30 hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3 font-body text-white">
                            <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="hover:text-blue-light transition-colors">
                                {{ $user->name }}
                            </a>
                        </td>
                        <td class="px-5 py-3 text-slate-400 font-body">{{ $user->email }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide
                                         {{ $user->plan === 'pro' ? 'bg-amber-400/10 text-amber-400' : 'bg-slate-800 text-slate-500' }}">
                                {{ $user->plan }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 font-body text-xs font-mono">{{ $user->created_at->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
