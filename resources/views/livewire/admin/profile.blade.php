<div class="p-8 dark:text-white text-gray-900 max-w-2xl">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-600 font-body mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-amber-400 hover:text-amber-300 transition-colors">Admin</a>
        <span>/</span>
        <span class="text-slate-400">Profile</span>
    </div>

    <h1 class="font-display font-extrabold text-2xl text-white tracking-tight mb-8">Profile Settings</h1>

    {{-- ── Edit Name ─────────────────────────────────────────── --}}
    <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6 mb-5">
        <h2 class="font-heading font-bold text-sm text-white mb-4">Display Name</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">Name</label>
                <input type="text"
                       wire:model="name"
                       class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                              dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                @error('name')
                    <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button wire:click="updateName"
                        class="px-4 py-2 text-sm font-semibold font-body bg-primary text-white hover:bg-accent rounded-xl transition-colors">
                    Save Name
                </button>
                @if($nameSuccess)
                    <span class="text-xs text-emerald-400 font-body" x-data x-init="setTimeout(() => $wire.set('nameSuccess', null), 3000)">
                        {{ $nameSuccess }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Change Password ───────────────────────────────────── --}}
    <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6 mb-5">
        <h2 class="font-heading font-bold text-sm text-white mb-4">Change Password</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">Current Password</label>
                <input type="password"
                       wire:model="currentPassword"
                       autocomplete="current-password"
                       class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                              dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150">
                @error('currentPassword')
                    <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">New Password</label>
                <input type="password"
                       wire:model="newPassword"
                       autocomplete="new-password"
                       class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                              dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150">
                @error('newPassword')
                    <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">Confirm New Password</label>
                <input type="password"
                       wire:model="newPasswordConfirmation"
                       autocomplete="new-password"
                       class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                              dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150">
            </div>

            <div class="flex items-center gap-3">
                <button wire:click="updatePassword"
                        class="px-4 py-2 text-sm font-semibold font-body bg-primary text-white hover:bg-accent rounded-xl transition-colors">
                    Update Password
                </button>
                @if($passwordSuccess)
                    <span class="text-xs text-emerald-400 font-body" x-data x-init="setTimeout(() => $wire.set('passwordSuccess', null), 3000)">
                        {{ $passwordSuccess }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Change Email ──────────────────────────────────────── --}}
    <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6">
        <h2 class="font-heading font-bold text-sm text-white mb-1">Change Email</h2>
        <p class="text-xs text-slate-500 font-body mb-4">
            Current: <span class="font-mono text-slate-400">{{ auth()->user()->email }}</span>
        </p>

        @if(!$otpSent)
            {{-- Step 1: enter new email --}}
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">New Email Address</label>
                    <input type="email"
                           wire:model="newEmail"
                           placeholder="new@example.com"
                           class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                  dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                  dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                    @error('newEmail')
                        <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p>
                    @enderror
                </div>

                <button wire:click="requestEmailChange"
                        class="px-4 py-2 text-sm font-semibold font-body bg-primary text-white hover:bg-accent rounded-xl transition-colors">
                    Send Verification Code
                </button>
            </div>
        @else
            {{-- Step 2: enter OTP --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 p-3 rounded-xl bg-primary/10 border border-primary/20">
                    <svg class="w-4 h-4 text-blue-light flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                    </svg>
                    <p class="text-xs text-blue-light font-body">
                        A 6-digit code was sent to <span class="font-mono font-semibold">{{ $newEmail }}</span>. It expires in 10 minutes.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">Verification Code</label>
                    <input type="text"
                           wire:model="otpCode"
                           maxlength="6"
                           placeholder="000000"
                           class="w-48 px-4 py-2.5 text-sm font-mono tracking-widest dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                  dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                  dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                    @error('otpCode')
                        <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button wire:click="verifyEmailChange"
                            class="px-4 py-2 text-sm font-semibold font-body bg-primary text-white hover:bg-accent rounded-xl transition-colors">
                        Confirm Email Change
                    </button>
                    <button wire:click="cancelEmailChange"
                            class="px-4 py-2 text-sm font-semibold font-body dark:bg-slate-800 bg-gray-100 dark:text-slate-400 text-gray-600
                                   dark:hover:bg-slate-700 hover:bg-gray-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    @if($emailSuccess)
                        <span class="text-xs text-emerald-400 font-body" x-data x-init="setTimeout(() => $wire.set('emailSuccess', null), 3000)">
                            {{ $emailSuccess }}
                        </span>
                    @endif
                </div>
            </div>
        @endif

        @if($emailSuccess && !$otpSent)
            <div class="mt-3">
                <span class="text-xs text-emerald-400 font-body">{{ $emailSuccess }}</span>
            </div>
        @endif
    </div>

</div>
