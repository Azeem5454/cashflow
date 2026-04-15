<div>
    @if($status === 'expired')
        <div class="anim-fade-up text-center">
            <div class="w-14 h-14 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                </svg>
            </div>
            <h2 class="font-display font-extrabold text-2xl text-white mb-2 tracking-tight">Invitation Expired</h2>
            <p class="font-body text-sm text-slate-400 mb-8 leading-relaxed">
                This invitation link has expired.<br>Ask the business owner to send a new one.
            </p>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold font-body
                      bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white
                      border border-slate-700 hover:border-slate-600
                      rounded-xl transition-all duration-150">
                Go to Dashboard
            </a>
        </div>

    @elseif($status === 'accepted')
        <div class="anim-fade-up text-center">
            <div class="w-14 h-14 rounded-2xl bg-green-500/10 border border-green-500/20 flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-green-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
            </div>
            <h2 class="font-display font-extrabold text-2xl text-white mb-2 tracking-tight">Already Accepted</h2>
            <p class="font-body text-sm text-slate-400 mb-8">This invitation has already been used.</p>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold font-body
                      bg-primary hover:bg-accent text-white rounded-xl
                      transition-all duration-200 shadow-lg shadow-primary/25">
                Go to Dashboard
            </a>
        </div>

    @elseif($status === 'already_member')
        <div class="anim-fade-up text-center">
            <div class="w-14 h-14 rounded-2xl bg-primary/15 border border-primary/20 flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-blue-light" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                </svg>
            </div>
            <h2 class="font-display font-extrabold text-2xl text-white mb-2 tracking-tight">Already a Member</h2>
            <p class="font-body text-sm text-slate-400 mb-8">
                You're already a member of <span class="font-semibold text-white">{{ $invitation->business->name }}</span>.
            </p>
            <a href="{{ route('businesses.show', $invitation->business) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold font-body
                      bg-primary hover:bg-accent text-white rounded-xl
                      transition-all duration-200 shadow-lg shadow-primary/25">
                Open Business
            </a>
        </div>

    @elseif($status === 'seat_limit')
        <div class="anim-fade-up text-center">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-5"
                 style="background:rgba(245,158,11,0.15);border:1px solid rgba(245,158,11,0.3)">
                <svg class="w-7 h-7" style="color:#fbbf24" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                </svg>
            </div>
            <h2 class="font-display font-extrabold text-2xl text-white mb-2 tracking-tight">Team Full</h2>
            <p class="font-body text-sm text-slate-400 mb-8">
                <span class="font-semibold text-white">{{ $invitation->business->name }}</span> is on the Free plan, which is limited to 2 team members.<br>
                Ask the owner to upgrade to Pro so you can join.
            </p>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold font-body
                      bg-primary hover:bg-accent text-white rounded-xl
                      transition-all duration-200 shadow-lg shadow-primary/25">
                Go to Dashboard
            </a>
        </div>

    @else
        {{-- Pending — show accept UI --}}
        <div class="anim-fade-up">
            {{-- Header --}}
            <div class="mb-8">
                <div class="inline-flex items-center gap-2 bg-primary/10 border border-primary/20 rounded-full px-3.5 py-1.5 mb-5">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                    <span class="font-body text-xs text-blue-light font-medium tracking-wider uppercase">Team Invitation</span>
                </div>
                <h1 class="font-display font-extrabold text-3xl text-white tracking-tight mb-2">
                    You're invited!
                </h1>
                <p class="font-body text-sm text-slate-400 leading-relaxed">
                    Join
                    <span class="font-semibold text-white">{{ $invitation->business->name }}</span>
                    on {{ config('app.name', 'TheCashFox') }} as a
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                 bg-primary/20 text-blue-light border border-primary/30">
                        {{ ucfirst($invitation->role) }}
                    </span>.
                </p>
            </div>

            {{-- Permissions card --}}
            <div class="bg-white/[0.04] border border-white/[0.08] rounded-xl p-4 mb-6 space-y-2.5">
                <p class="font-body text-[11px] font-semibold uppercase tracking-widest text-slate-500 mb-3">
                    What you can do as {{ ucfirst($invitation->role) }}
                </p>
                @if($invitation->role === 'editor')
                    @foreach(['Create and manage books', 'Add, edit, and delete entries', 'View balance and history'] as $perm)
                        <div class="flex items-center gap-2.5 text-sm font-body text-slate-300">
                            <span class="w-4 h-4 rounded-full bg-green-500/15 border border-green-500/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-2.5 h-2.5 text-green-400" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                            </span>
                            {{ $perm }}
                        </div>
                    @endforeach
                @else
                    @foreach(['View books and entries', 'See live balance and history', 'No edit access'] as $perm)
                        <div class="flex items-center gap-2.5 text-sm font-body text-slate-300">
                            <span class="w-4 h-4 rounded-full {{ $loop->last ? 'bg-slate-700/50 border-slate-700' : 'bg-green-500/15 border-green-500/20' }} border flex items-center justify-center flex-shrink-0">
                                @if($loop->last)
                                    <svg class="w-2.5 h-2.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                @else
                                    <svg class="w-2.5 h-2.5 text-green-400" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                @endif
                            </span>
                            <span class="{{ $loop->last ? 'text-slate-500' : '' }}">{{ $perm }}</span>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- CTA --}}
            @guest
                <div class="space-y-3">
                    <a href="{{ route('login') }}?redirect={{ urlencode(request()->url()) }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-6 py-3
                              bg-primary hover:bg-accent text-white
                              font-body text-sm font-semibold rounded-xl
                              transition-all duration-200 shadow-lg shadow-primary/25">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                        </svg>
                        Log in to Accept
                    </a>
                    <a href="{{ route('register') }}?redirect={{ urlencode(request()->url()) }}"
                       class="w-full inline-flex items-center justify-center gap-1.5 px-6 py-3
                              font-body text-sm font-medium text-slate-400 hover:text-white
                              rounded-xl transition-colors duration-150">
                        Create an account
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                </div>
            @else
                <button wire:click="accept"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-70 cursor-wait"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3
                               bg-primary hover:bg-accent text-white
                               font-body text-sm font-semibold rounded-xl
                               transition-all duration-200 shadow-lg shadow-primary/25
                               disabled:opacity-70 disabled:cursor-wait">
                    <span wire:loading.remove wire:target="accept" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        Accept & Join Team
                    </span>
                    <span wire:loading wire:target="accept" class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Joining…
                    </span>
                </button>
                <p class="font-body text-xs text-slate-600 text-center mt-3">
                    Joining as <span class="text-slate-500">{{ auth()->user()->name }}</span>
                    · <span class="text-slate-600">{{ auth()->user()->email }}</span>
                </p>
            @endguest

            <p class="font-body text-xs text-slate-600 text-center mt-4">
                Expires {{ $invitation->expires_at->diffForHumans() }}
            </p>
        </div>
    @endif
</div>
