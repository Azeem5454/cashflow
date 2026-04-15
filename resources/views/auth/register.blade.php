<x-guest-layout>

    @php $intendedPlan = request('plan') === 'pro' ? 'pro' : null; @endphp

    <div class="anim-fade-up mb-8">
        @if($intendedPlan === 'pro')
            <div class="inline-flex items-center gap-2 px-3 py-1 mb-3 rounded-full text-xs font-semibold"
                 style="background:rgba(245,158,11,0.15);color:#fbbf24;border:1px solid rgba(245,158,11,0.3)">
                ⭐ Pro plan · $5/month
            </div>
            <h1 class="guest-display font-extrabold text-3xl text-slate-900 dark:text-white mb-2">Start with Pro</h1>
            <p class="guest-body text-sm text-slate-500 dark:text-slate-400">Create your account — you'll go to checkout right after.</p>
        @else
            <h1 class="guest-display font-extrabold text-3xl text-slate-900 dark:text-white mb-2">Create your account</h1>
            <p class="guest-body text-sm text-slate-500 dark:text-slate-400">Free forever — no credit card required</p>
        @endif
    </div>

    <form method="POST" action="{{ route('register') }}" class="anim-fade-up-d1 space-y-5">
        @csrf
        @if($intendedPlan)
            <input type="hidden" name="intended_plan" value="{{ $intendedPlan }}">
        @endif

        {{-- Name --}}
        <div>
            <label for="name" class="block guest-body text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Full name</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Ali Hassan"
                    class="auth-input w-full rounded-lg pl-10 pr-4 py-3 guest-body text-sm"
                >
            </div>
            @error('name')
                <p class="mt-1.5 guest-body text-xs text-red-500 dark:text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block guest-body text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email address</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </span>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', request('email')) }}"
                    required
                    autocomplete="username"
                    placeholder="you@example.com"
                    class="auth-input w-full rounded-lg pl-10 pr-4 py-3 guest-body text-sm"
                >
            </div>
            @error('email')
                <p class="mt-1.5 guest-body text-xs text-red-500 dark:text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block guest-body text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Password</label>
            <div class="relative" x-data="{ show: false }">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <input
                    id="password"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Min. 8 characters"
                    class="auth-input w-full rounded-lg pl-10 pr-10 py-3 guest-body text-sm"
                >
                <button type="button" @click="show = !show"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5
                               text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 guest-body text-xs text-red-500 dark:text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="block guest-body text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Confirm password</label>
            <div class="relative" x-data="{ show: false }">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </span>
                <input
                    id="password_confirmation"
                    :type="show ? 'text' : 'password'"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repeat your password"
                    class="auth-input w-full rounded-lg pl-10 pr-10 py-3 guest-body text-sm"
                >
                <button type="button" @click="show = !show"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5
                               text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            @error('password_confirmation')
                <p class="mt-1.5 guest-body text-xs text-red-500 dark:text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Terms note --}}
        <p class="guest-body text-xs text-slate-400 dark:text-slate-500 leading-relaxed">
            By creating an account you agree to our
            <a href="#" class="text-primary dark:text-blue-light hover:text-accent transition-colors underline underline-offset-2">Terms of Service</a>
            and
            <a href="#" class="text-primary dark:text-blue-light hover:text-accent transition-colors underline underline-offset-2">Privacy Policy</a>.
        </p>

        {{-- Submit --}}
        <button
            type="submit"
            class="anim-fade-up-d2 w-full guest-body font-medium text-sm text-white bg-primary hover:bg-accent rounded-lg px-4 py-3 transition-all duration-200 shadow-lg shadow-primary/25 hover:shadow-accent/30 hover:-translate-y-px"
        >
            {{ $intendedPlan === 'pro' ? 'Create account & continue to payment →' : 'Create free account' }}
        </button>

        {{-- Divider --}}
        <div class="relative flex items-center gap-4 py-1">
            <div class="flex-1 h-px bg-gray-200 dark:bg-white/8"></div>
            <span class="guest-body text-xs text-slate-400 dark:text-slate-600">Already have an account?</span>
            <div class="flex-1 h-px bg-gray-200 dark:bg-white/8"></div>
        </div>

        {{-- Login link --}}
        <a
            href="{{ route('login') }}"
            class="anim-fade-up-d3 flex items-center justify-center gap-2 w-full guest-body font-medium text-sm
                   text-primary dark:text-blue-light
                   hover:text-accent dark:hover:text-white
                   border border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20
                   rounded-lg px-4 py-3 transition-all duration-200
                   hover:bg-gray-50 dark:hover:bg-white/5"
        >
            Sign in instead
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
        </a>

    </form>

</x-guest-layout>
