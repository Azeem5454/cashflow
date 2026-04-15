<div class="p-4 sm:p-8 dark:text-white text-gray-900">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-600 font-body mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-amber-400 hover:text-amber-300 transition-colors">Admin</a>
        <span>/</span>
        <a href="{{ route('admin.users') }}" wire:navigate class="text-slate-400 hover:text-white transition-colors">Users</a>
        <span>/</span>
        <span class="text-slate-400">{{ $user->name }}</span>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- ===== LEFT: Profile + Actions ===== --}}
        <div class="space-y-4">

            {{-- Profile card --}}
            <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-heading font-bold dark:text-white text-gray-900">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500 font-body truncate">{{ $user->email }}</p>
                    </div>
                </div>

                <div class="space-y-2.5 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-body">Plan</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide
                                     {{ $user->plan === 'pro' ? 'bg-amber-400/10 text-amber-400' : 'dark:bg-slate-800 dark:text-slate-400 bg-gray-100 text-gray-500' }}">
                            {{ $user->plan }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-body">Admin</span>
                        <span class="text-slate-400 font-body text-xs">{{ $user->is_admin ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-body">Joined</span>
                        <span class="font-mono text-xs text-slate-400">{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-body">Last Login</span>
                        <span class="font-mono text-xs text-slate-400">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                        </span>
                    </div>
                    @if($user->stripe_id)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-body">Stripe ID</span>
                            <span class="font-mono text-xs text-slate-500 truncate max-w-[120px]">{{ $user->stripe_id }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Subscription card --}}
            @if($subscription)
                <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-5">
                    <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-3">Subscription</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-body">Status</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide
                                         {{ $subscription->stripe_status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'dark:bg-slate-800 dark:text-slate-400 bg-gray-100 text-gray-500' }}">
                                {{ $subscription->stripe_status }}
                            </span>
                        </div>
                        @if($subscription->ends_at)
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-body">Ends at</span>
                                <span class="font-mono text-xs {{ $subscription->ends_at->isFuture() ? 'text-amber-400' : 'text-red-400' }}">
                                    {{ $subscription->ends_at->format('d M Y') }}
                                </span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-body">Stripe Sub ID</span>
                            <span class="font-mono text-xs text-slate-500 truncate max-w-[120px]">{{ $subscription->stripe_id }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-5">
                <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-3">Actions</h3>
                <div class="space-y-2">
                    @if($user->plan !== 'pro')
                        <button wire:click="forcePro"
                                class="w-full py-2 text-sm font-semibold font-body bg-amber-400/10 text-amber-400 hover:bg-amber-400/20 rounded-xl transition-colors">
                            Force Pro Plan
                        </button>
                    @else
                        <button wire:click="forceFree"
                                class="w-full py-2 text-sm font-semibold font-body dark:bg-slate-800 bg-gray-100 dark:text-slate-400 text-gray-600 dark:hover:bg-slate-700 hover:bg-gray-200 rounded-xl transition-colors">
                            Force Free Plan
                        </button>
                    @endif

                    <button wire:click="resyncStripe"
                            wire:loading.attr="disabled"
                            wire:target="resyncStripe"
                            wire:confirm="Resync this user's Stripe customer against current keys? This will clear any stale test customer IDs."
                            class="w-full py-2 text-sm font-semibold font-body bg-primary/10 text-blue-light hover:bg-primary/20 rounded-xl transition-colors disabled:opacity-50">
                        <span wire:loading.remove wire:target="resyncStripe">Resync Stripe Customer</span>
                        <span wire:loading wire:target="resyncStripe">Syncing with Stripe…</span>
                    </button>

                    @if($resyncMessage)
                        <div class="mt-2 px-3 py-2 text-xs font-body rounded-lg
                                    @if(str_starts_with($resyncMessage, 'Error'))
                                        bg-red-500/10 text-red-400 border border-red-500/20
                                    @else
                                        bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @endif">
                            {{ $resyncMessage }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Danger zone --}}
            @if(!$user->is_admin)
                <div class="bg-dark border border-red-500/20 rounded-xl p-5">
                    <h3 class="font-heading font-bold text-sm text-red-400 mb-3">Danger Zone</h3>
                    <div class="space-y-2">
                        <button wire:click="impersonate"
                                wire:confirm="Log in as {{ $user->name }}? You will be redirected to the app."
                                class="w-full py-2 text-sm font-semibold font-body bg-amber-400/10 text-amber-400 hover:bg-amber-400/20 border border-amber-400/20 rounded-xl transition-colors">
                            Impersonate User
                        </button>
                        <button wire:click="deleteUser"
                                wire:confirm="Delete {{ $user->name }} and ALL their data? This cannot be undone."
                                class="w-full py-2 text-sm font-semibold font-body bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 rounded-xl transition-colors">
                            Delete Account
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===== RIGHT: Businesses + Invitations ===== --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Businesses --}}
            <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800">
                    <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900">Businesses</h3>
                </div>
                @forelse($businesses as $biz)
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 dark:border-slate-800/60 last:border-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium dark:text-white text-gray-900 font-body truncate">{{ $biz->name }}</p>
                            <p class="text-xs text-slate-500 font-body mt-0.5">
                                {{ $biz->books_count }} book{{ $biz->books_count !== 1 ? 's' : '' }} ·
                                {{ $biz->currency }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide ml-3
                                     {{ $biz->pivot->role === 'owner' ? 'bg-primary/10 text-blue-light' : 'dark:bg-slate-800 dark:text-slate-400 bg-gray-100 text-gray-500' }}">
                            {{ $biz->pivot->role }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-600 font-body">No businesses.</div>
                @endforelse
            </div>

            {{-- Invitations --}}
            <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-800">
                    <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900">Invitations Sent</h3>
                </div>
                @forelse($invitations as $inv)
                    @php
                        $invStatus = $inv->accepted_at ? 'accepted'
                            : ($inv->expires_at->isPast() ? 'expired' : 'pending');
                    @endphp
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-200 dark:border-slate-800/60 last:border-0">
                        <div class="min-w-0">
                            <p class="text-sm dark:text-white text-gray-900 font-body truncate">{{ $inv->email }}</p>
                            <p class="text-xs text-slate-500 font-body mt-0.5">
                                {{ $inv->role }} · expires {{ $inv->expires_at->format('d M Y') }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide ml-3
                                     {{ $invStatus === 'accepted' ? 'bg-emerald-500/10 text-emerald-400'
                                         : ($invStatus === 'expired' ? 'bg-red-500/10 text-red-400'
                                             : 'bg-amber-400/10 text-amber-400') }}">
                            {{ $invStatus }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-slate-600 font-body">No invitations sent.</div>
                @endforelse
            </div>

        </div>
    </div>

</div>
