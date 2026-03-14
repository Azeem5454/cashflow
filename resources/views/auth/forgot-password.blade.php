<x-guest-layout>

    {{-- Heading --}}
    <div class="anim-fade-up mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-primary/15 border border-primary/25 mb-5">
            <svg class="w-6 h-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
        </div>
        <h1 class="font-display font-extrabold text-3xl text-white mb-2">Forgot your password?</h1>
        <p class="font-body text-sm text-slate-400 leading-relaxed">No worries. Enter your email and we'll send you a secure reset link.</p>
    </div>

    {{-- Session Status (link sent) --}}
    @if (session('status'))
        <div class="anim-fade-up mb-6 flex items-start gap-3 bg-green-500/10 border border-green-500/20 rounded-xl px-4 py-4">
            <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-green-500/15 flex items-center justify-center mt-0.5">
                <svg class="w-4 h-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            <div>
                <p class="font-body text-sm font-medium text-green-300 mb-0.5">Reset link sent!</p>
                <p class="font-body text-xs text-green-400/70">{{ session('status') }}</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="anim-fade-up-d1 space-y-5">
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

        {{-- Submit --}}
        <button
            type="submit"
            class="anim-fade-up-d2 w-full font-body font-medium text-sm text-white bg-primary hover:bg-accent rounded-lg px-4 py-3 transition-all duration-200 shadow-lg shadow-primary/25 hover:shadow-accent/30 hover:-translate-y-px mt-2"
        >
            Send reset link
        </button>

        {{-- Back to login --}}
        <div class="anim-fade-up-d3 flex items-center justify-center pt-1">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 font-body text-sm text-blue-light hover:text-white transition-colors duration-150">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to sign in
            </a>
        </div>

    </form>

</x-guest-layout>
