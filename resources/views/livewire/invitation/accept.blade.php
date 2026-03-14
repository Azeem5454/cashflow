<div class="min-h-screen dark:bg-navy bg-gray-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <x-app-logo size="md" />
        </div>

        @if($status === 'expired')
            <div class="dark:bg-dark bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl p-8 text-center shadow-xl shadow-black/10">
                <div class="w-14 h-14 rounded-2xl bg-red-500/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                    </svg>
                </div>
                <h2 class="font-display font-extrabold text-xl dark:text-white text-gray-900 mb-2">Invitation Expired</h2>
                <p class="text-sm dark:text-slate-400 text-gray-500 mb-6 leading-relaxed">
                    This invitation link has expired. Ask the business owner to send a new one.
                </p>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold
                          dark:bg-slate-800 bg-gray-100
                          dark:text-slate-300 text-gray-700
                          dark:hover:bg-slate-700 hover:bg-gray-200
                          rounded-xl transition-all duration-150">
                    Go to Dashboard
                </a>
            </div>

        @elseif($status === 'accepted')
            <div class="dark:bg-dark bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl p-8 text-center shadow-xl shadow-black/10">
                <div class="w-14 h-14 rounded-2xl bg-green-500/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                </div>
                <h2 class="font-display font-extrabold text-xl dark:text-white text-gray-900 mb-2">Already Accepted</h2>
                <p class="text-sm dark:text-slate-400 text-gray-500 mb-6">This invitation has already been used.</p>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold
                          bg-primary hover:bg-accent text-white rounded-xl
                          transition-all duration-200 shadow-md shadow-primary/25">
                    Go to Dashboard
                </a>
            </div>

        @elseif($status === 'already_member')
            <div class="dark:bg-dark bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl p-8 text-center shadow-xl shadow-black/10">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                    </svg>
                </div>
                <h2 class="font-display font-extrabold text-xl dark:text-white text-gray-900 mb-2">Already a Member</h2>
                <p class="text-sm dark:text-slate-400 text-gray-500 mb-6">
                    You're already a member of <strong class="dark:text-white text-gray-900">{{ $invitation->business->name }}</strong>.
                </p>
                <a href="{{ route('businesses.show', $invitation->business) }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold
                          bg-primary hover:bg-accent text-white rounded-xl
                          transition-all duration-200 shadow-md shadow-primary/25">
                    Open Business
                </a>
            </div>

        @else
            {{-- Pending — show accept UI --}}
            <div class="dark:bg-dark bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl overflow-hidden shadow-xl shadow-black/10">
                <div class="h-1 w-full bg-gradient-to-r from-primary to-accent"></div>
                <div class="p-8">
                    <div class="w-14 h-14 rounded-2xl bg-primary/10 dark:bg-primary/15 flex items-center justify-center mb-5">
                        <svg class="w-7 h-7 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                        </svg>
                    </div>

                    <h2 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight mb-1">
                        You're invited!
                    </h2>
                    <p class="text-sm dark:text-slate-400 text-gray-500 mb-6 leading-relaxed">
                        You've been invited to join
                        <span class="font-semibold dark:text-white text-gray-900">{{ $invitation->business->name }}</span>
                        as a
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                     dark:bg-primary/20 bg-primary/10 dark:text-blue-light text-primary">
                            {{ ucfirst($invitation->role) }}
                        </span>.
                    </p>

                    <div class="dark:bg-slate-800/50 bg-gray-50 rounded-xl p-4 mb-6 space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 mb-2">What you can do as {{ ucfirst($invitation->role) }}</p>
                        @if($invitation->role === 'editor')
                            @foreach(['Create and manage books', 'Add, edit, and delete entries', 'View balance and history'] as $perm)
                                <div class="flex items-center gap-2 text-sm dark:text-slate-300 text-gray-700">
                                    <span class="w-4 h-4 rounded-full bg-green-500/15 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-2.5 h-2.5 text-green-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    </span>
                                    {{ $perm }}
                                </div>
                            @endforeach
                        @else
                            @foreach(['View books and entries', 'See balance and history'] as $perm)
                                <div class="flex items-center gap-2 text-sm dark:text-slate-300 text-gray-700">
                                    <span class="w-4 h-4 rounded-full bg-green-500/15 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-2.5 h-2.5 text-green-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    </span>
                                    {{ $perm }}
                                </div>
                            @endforeach
                        @endif
                    </div>

                    @guest
                        <p class="text-xs dark:text-slate-500 text-gray-400 mb-4 text-center">
                            You need to be logged in to accept this invitation.
                        </p>
                        <div class="flex flex-col gap-3">
                            <a href="{{ route('login') }}?redirect={{ urlencode(request()->url()) }}"
                               class="inline-flex items-center justify-center gap-2 w-full px-6 py-3
                                      bg-primary hover:bg-accent text-white
                                      text-sm font-semibold rounded-xl
                                      transition-all duration-200 shadow-lg shadow-primary/25">
                                Log in to Accept
                            </a>
                            <a href="{{ route('register') }}?redirect={{ urlencode(request()->url()) }}"
                               class="inline-flex items-center justify-center w-full px-6 py-3
                                      dark:text-slate-400 text-gray-500
                                      dark:hover:text-white hover:text-gray-900
                                      text-sm font-medium rounded-xl transition-colors duration-150">
                                Create an account →
                            </a>
                        </div>
                    @else
                        <button wire:click="accept"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-70 cursor-wait"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3
                                       bg-primary hover:bg-accent text-white
                                       text-sm font-semibold rounded-xl
                                       transition-all duration-200 shadow-lg shadow-primary/25
                                       disabled:opacity-70 disabled:cursor-wait">
                            <span wire:loading.remove>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                            </span>
                            <span wire:loading>
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </span>
                            <span wire:loading.remove>Accept & Join Team</span>
                            <span wire:loading>Joining…</span>
                        </button>
                        <p class="text-xs dark:text-slate-600 text-gray-400 text-center mt-3">
                            Joining as {{ auth()->user()->name }} ({{ auth()->user()->email }})
                        </p>
                    @endguest

                    <p class="text-xs dark:text-slate-600 text-gray-400 text-center mt-4">
                        Expires {{ $invitation->expires_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        @endif

    </div>
</div>
