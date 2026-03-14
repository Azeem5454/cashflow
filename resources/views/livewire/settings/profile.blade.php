<div
    x-data="{
        tab: 'profile',
        showToast: false,
        toastMessage: '',
        showSaved(msg) {
            this.toastMessage = msg;
            this.showToast = true;
            setTimeout(() => this.showToast = false, 3200);
        }
    }"
    @profile-saved.window="showSaved('Profile updated successfully')"
    @password-saved.window="showSaved('Password changed successfully')"
    class="min-h-full"
>

    {{-- ===== STICKY HEADER ===== --}}
    <div class="px-6 lg:px-8 py-5
                dark:bg-navy/95 bg-white/95
                dark:border-b dark:border-slate-800 border-b border-gray-200
                sticky top-0 z-10 backdrop-blur-md">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight leading-none">
                        Account Settings
                    </h1>
                    <p class="text-sm dark:text-slate-500 text-gray-400 mt-1">Manage your profile and security</p>
                </div>
            </div>

            {{-- Tab switcher --}}
            <div class="flex gap-1 mt-4 p-1 dark:bg-slate-800/60 bg-gray-100 rounded-xl w-fit">
                <button
                    @click="tab = 'profile'"
                    :class="tab === 'profile'
                        ? 'dark:bg-[#1e293b] bg-white dark:text-white text-gray-900 shadow-sm'
                        : 'dark:text-slate-400 text-gray-500 hover:dark:text-slate-300 hover:text-gray-700'"
                    class="px-4 py-1.5 text-sm font-semibold rounded-lg transition-all duration-150">
                    Profile
                </button>
                <button
                    @click="tab = 'password'"
                    :class="tab === 'password'
                        ? 'dark:bg-[#1e293b] bg-white dark:text-white text-gray-900 shadow-sm'
                        : 'dark:text-slate-400 text-gray-500 hover:dark:text-slate-300 hover:text-gray-700'"
                    class="px-4 py-1.5 text-sm font-semibold rounded-lg transition-all duration-150">
                    Password
                </button>
            </div>
        </div>
    </div>

    {{-- ===== CONTENT ===== --}}
    <div class="px-6 lg:px-8 py-7 max-w-2xl mx-auto space-y-6">

            {{-- ===== PROFILE TAB ===== --}}
            <div x-show="tab === 'profile'">

                {{-- Identity card + form --}}
                <div class="dark:bg-[#1e293b] bg-white dark:border dark:border-slate-700/60 border border-gray-200 rounded-2xl overflow-hidden shadow-sm">

                    {{-- Avatar section --}}
                    <div class="flex items-center gap-5 px-6 py-6 dark:border-b dark:border-slate-700/40 border-b border-gray-100 dark:bg-gradient-to-r dark:from-primary/5 dark:to-transparent">
                        <div class="relative flex-shrink-0">
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-2xl font-bold font-display shadow-lg shadow-primary/30">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="absolute bottom-0 right-0 w-4 h-4 rounded-full bg-green-500 ring-2 dark:ring-[#1e293b] ring-white"></div>
                        </div>
                        <div class="min-w-0">
                            <p class="font-heading font-bold text-base dark:text-white text-gray-900 truncate leading-tight">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-sm dark:text-slate-400 text-gray-500 truncate mt-0.5">
                                {{ auth()->user()->email }}
                            </p>
                            <span class="inline-flex items-center gap-1 mt-2 text-xs px-2 py-0.5 rounded font-semibold
                                         {{ auth()->user()->isPro()
                                             ? 'bg-primary/15 text-primary dark:text-blue-light'
                                             : 'dark:bg-slate-700/60 bg-gray-100 dark:text-slate-400 text-gray-500' }}">
                                @if(auth()->user()->isPro())
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292Z"/>
                                    </svg>
                                    Pro Plan
                                @else
                                    Free Plan
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Form --}}
                    <div class="p-6 space-y-5">
                        <div>
                            <label class="block text-xs font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-2">
                                Full Name
                            </label>
                            <input
                                type="text"
                                wire:model="name"
                                placeholder="Your full name"
                                class="w-full px-4 py-2.5 text-sm rounded-xl
                                       dark:bg-navy bg-gray-50
                                       dark:border-slate-700 border-gray-200 border
                                       dark:text-white text-gray-900
                                       dark:placeholder-slate-600 placeholder-gray-400
                                       focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary
                                       transition-all duration-150"
                            >
                            @error('name')
                                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-2">
                                Email Address
                            </label>
                            <input
                                type="email"
                                wire:model="email"
                                placeholder="you@example.com"
                                class="w-full px-4 py-2.5 text-sm rounded-xl
                                       dark:bg-navy bg-gray-50
                                       dark:border-slate-700 border-gray-200 border
                                       dark:text-white text-gray-900
                                       dark:placeholder-slate-600 placeholder-gray-400
                                       focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary
                                       transition-all duration-150"
                            >
                            @error('email')
                                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 dark:border-t dark:border-slate-700/40 border-t border-gray-100 flex items-center justify-between">
                        <p class="text-xs dark:text-slate-500 text-gray-400">
                            Member since {{ auth()->user()->created_at->format('F Y') }}
                        </p>
                        <button
                            wire:click="saveProfile"
                            wire:loading.attr="disabled"
                            wire:target="saveProfile"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold
                                   bg-primary hover:bg-accent text-white rounded-xl
                                   transition-all duration-200 shadow-md shadow-primary/25
                                   disabled:opacity-70 disabled:cursor-wait">
                            <svg wire:loading.remove wire:target="saveProfile" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 3v4H7V3"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 1 0 4 0 2 2 0 0 0-4 0"/>
                            </svg>
                            <svg wire:loading wire:target="saveProfile" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="saveProfile">Save Profile</span>
                            <span wire:loading wire:target="saveProfile">Saving…</span>
                        </button>
                    </div>
                </div>

            </div>

            {{-- ===== PASSWORD TAB ===== --}}
            <div x-show="tab === 'password'" x-cloak>

                <div class="dark:bg-[#1e293b] bg-white dark:border dark:border-slate-700/60 border border-gray-200 rounded-2xl overflow-hidden shadow-sm">

                    {{-- Header --}}
                    <div class="px-6 py-4 dark:border-b dark:border-slate-700/40 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-primary dark:text-blue-light" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="font-heading font-bold text-base dark:text-white text-gray-900">Change Password</h2>
                            <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">Choose a strong password of at least 8 characters</p>
                        </div>
                    </div>

                    {{-- Form --}}
                    <div class="p-6 space-y-5">
                        <div>
                            <label class="block text-xs font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-2">
                                Current Password
                            </label>
                            <input
                                type="password"
                                wire:model="currentPassword"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                class="w-full px-4 py-2.5 text-sm rounded-xl
                                       dark:bg-navy bg-gray-50
                                       dark:border-slate-700 border-gray-200 border
                                       dark:text-white text-gray-900
                                       dark:placeholder-slate-600 placeholder-gray-400
                                       focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary
                                       transition-all duration-150"
                            >
                            @error('currentPassword')
                                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="dark:border-t dark:border-slate-700/40 border-t border-gray-100 pt-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-2">
                                    New Password
                                </label>
                                <input
                                    type="password"
                                    wire:model="newPassword"
                                    placeholder="At least 8 characters"
                                    autocomplete="new-password"
                                    class="w-full px-4 py-2.5 text-sm rounded-xl
                                           dark:bg-navy bg-gray-50
                                           dark:border-slate-700 border-gray-200 border
                                           dark:text-white text-gray-900
                                           dark:placeholder-slate-600 placeholder-gray-400
                                           focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary
                                           transition-all duration-150"
                                >
                                @error('newPassword')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-2">
                                    Confirm New Password
                                </label>
                                <input
                                    type="password"
                                    wire:model="newPasswordConfirmation"
                                    placeholder="Repeat new password"
                                    autocomplete="new-password"
                                    class="w-full px-4 py-2.5 text-sm rounded-xl
                                           dark:bg-navy bg-gray-50
                                           dark:border-slate-700 border-gray-200 border
                                           dark:text-white text-gray-900
                                           dark:placeholder-slate-600 placeholder-gray-400
                                           focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary
                                           transition-all duration-150"
                                >
                                @error('newPasswordConfirmation')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 dark:border-t dark:border-slate-700/40 border-t border-gray-100 flex justify-end">
                        <button
                            wire:click="savePassword"
                            wire:loading.attr="disabled"
                            wire:target="savePassword"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold
                                   bg-primary hover:bg-accent text-white rounded-xl
                                   transition-all duration-200 shadow-md shadow-primary/25
                                   disabled:opacity-70 disabled:cursor-wait">
                            <svg wire:loading.remove wire:target="savePassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                            <svg wire:loading wire:target="savePassword" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="savePassword">Update Password</span>
                            <span wire:loading wire:target="savePassword">Updating…</span>
                        </button>
                    </div>
                </div>

            </div>

            {{-- ===== DANGER ZONE (always shown) ===== --}}
            <div x-data="{ confirm: false }">
                <div class="dark:bg-[#1e293b] bg-white dark:border dark:border-red-900/40 border border-red-200 rounded-2xl overflow-hidden shadow-sm">

                    {{-- Header --}}
                    <div class="px-6 py-4 dark:border-b dark:border-red-900/30 border-b border-red-100">
                        <h2 class="font-heading font-bold text-base text-red-500">Danger Zone</h2>
                        <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">Irreversible actions — proceed with caution</p>
                    </div>

                    <div class="p-6">
                        <div x-show="!confirm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold dark:text-white text-gray-900">Delete Account</p>
                                    <p class="text-xs dark:text-slate-400 text-gray-500 mt-1 leading-relaxed">
                                        Permanently delete your account and all associated data.<br>
                                        This action cannot be undone.
                                    </p>
                                </div>
                                <button
                                    @click="confirm = true"
                                    class="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
                                           text-red-500 dark:border-red-800/60 border border-red-200 rounded-xl
                                           dark:hover:bg-red-950/40 hover:bg-red-50
                                           transition-all duration-150">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                    Delete Account
                                </button>
                            </div>
                        </div>

                        <div x-show="confirm" x-cloak class="space-y-4">
                            <div class="flex items-center gap-3 p-3 rounded-xl dark:bg-red-950/30 bg-red-50 dark:border dark:border-red-900/40 border border-red-100">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                                </svg>
                                <p class="text-xs text-red-500 font-medium">
                                    This will permanently delete your account, all businesses you own, and all associated data.
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold dark:text-slate-400 text-gray-500 uppercase tracking-wider mb-2">
                                    Type your email to confirm
                                </label>
                                <input
                                    type="email"
                                    wire:model="deleteConfirmInput"
                                    placeholder="{{ auth()->user()->email }}"
                                    class="w-full px-4 py-2.5 text-sm rounded-xl
                                           dark:bg-navy bg-gray-50
                                           dark:border-red-800/60 border-red-200 border
                                           dark:text-white text-gray-900
                                           dark:placeholder-slate-600 placeholder-gray-400
                                           focus:outline-none focus:ring-2 focus:ring-red-500/30 focus:border-red-500
                                           transition-all duration-150"
                                >
                                @error('deleteConfirmInput')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    wire:click="deleteAccount"
                                    wire:loading.attr="disabled"
                                    wire:target="deleteAccount"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold
                                           bg-red-600 hover:bg-red-500 text-white rounded-xl
                                           transition-all duration-200 shadow-md shadow-red-900/30
                                           disabled:opacity-70 disabled:cursor-wait">
                                    <svg wire:loading.remove wire:target="deleteAccount" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                    <svg wire:loading wire:target="deleteAccount" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="deleteAccount">Permanently Delete Account</span>
                                    <span wire:loading wire:target="deleteAccount">Deleting…</span>
                                </button>

                                <button
                                    @click="confirm = false; $wire.set('deleteConfirmInput', '')"
                                    class="px-4 py-2.5 text-sm font-semibold
                                           dark:text-slate-400 text-gray-500
                                           dark:hover:text-white hover:text-gray-900
                                           transition-colors duration-150">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

    </div>

    {{-- ===== SAVED TOAST ===== --}}
    <div
        x-show="showToast"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        x-cloak
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50
               flex items-center gap-2.5 px-4 py-3
               dark:bg-slate-800 bg-white
               dark:border dark:border-slate-700 border border-gray-200
               rounded-xl shadow-xl text-sm font-semibold
               dark:text-white text-gray-900"
        style="display:none;">
        <div class="w-5 h-5 rounded-full bg-green-500/15 flex items-center justify-center flex-shrink-0">
            <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
        </div>
        <span x-text="toastMessage"></span>
    </div>

</div>
