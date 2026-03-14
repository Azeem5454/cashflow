<x-guest-layout>

    <div class="anim-fade-up mb-8">
        <h1 class="font-display font-extrabold text-3xl text-white mb-2">Welcome back</h1>
        <p class="font-body text-sm text-slate-400">Sign in to your CashFlow account</p>
    </div>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="anim-fade-up mb-5 flex items-center gap-2.5 bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-3">
            <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-body text-sm text-green-300">{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="anim-fade-up-d1 space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block font-body text-sm font-medium text-slate-300 mb-1.5">Email address</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </span>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                    class="auth-input w-full rounded-lg pl-10 pr-4 py-3 font-body text-sm"
                >
            </div>
            @error('email')
                <p class="mt-1.5 font-body text-xs text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="font-body text-sm font-medium text-slate-300">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="font-body text-xs text-blue-light hover:text-accent transition-colors duration-150">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="relative" x-data="{ show: false }">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <input
                    id="password"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="auth-input w-full rounded-lg pl-10 pr-10 py-3 font-body text-sm"
                >
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 hover:text-slate-300 transition-colors">
                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 font-body text-xs text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center">
            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                <div class="relative">
                    <input id="remember_me" type="checkbox" name="remember" class="sr-only peer">
                    <div class="w-4 h-4 rounded border border-white/15 bg-white/5 peer-checked:bg-primary peer-checked:border-primary transition-all duration-150 flex items-center justify-center">
                        <svg class="w-2.5 h-2.5 text-white hidden peer-checked:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <span class="font-body text-sm text-slate-400">Remember me for 30 days</span>
            </label>
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            class="anim-fade-up-d2 w-full font-body font-medium text-sm text-white bg-primary hover:bg-accent rounded-lg px-4 py-3 transition-all duration-200 shadow-lg shadow-primary/25 hover:shadow-accent/30 hover:-translate-y-px mt-2"
        >
            Sign in to CashFlow
        </button>

        {{-- Divider --}}
        <div class="relative flex items-center gap-4 py-1">
            <div class="flex-1 h-px bg-white/8"></div>
            <span class="font-body text-xs text-slate-600">New here?</span>
            <div class="flex-1 h-px bg-white/8"></div>
        </div>

        {{-- Register link --}}
        <a
            href="{{ route('register') }}"
            class="anim-fade-up-d3 flex items-center justify-center gap-2 w-full font-body font-medium text-sm text-blue-light hover:text-white border border-white/10 hover:border-white/20 rounded-lg px-4 py-3 transition-all duration-200 hover:bg-white/5"
        >
            Create a free account
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
        </a>

    </form>

</x-guest-layout>
