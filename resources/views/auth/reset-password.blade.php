<x-guest-layout>

    {{-- Heading --}}
    <div class="anim-fade-up mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-primary/15 border border-primary/25 mb-5">
            <svg class="w-6 h-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <h1 class="guest-display font-extrabold text-3xl text-slate-900 dark:text-white mb-2">Set new password</h1>
        <p class="guest-body text-sm text-slate-500 dark:text-slate-400 leading-relaxed">Choose a strong password to secure your {{ config('app.name', 'TheCashFox') }} account.</p>
    </div>

    {{-- General error (e.g. invalid/expired token) --}}
    @if ($errors->any() && !$errors->has('email') && !$errors->has('password') && !$errors->has('password_confirmation'))
        <div class="anim-fade-up mb-6 flex items-start gap-3 bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-4">
            <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-red-500/15 flex items-center justify-center mt-0.5">
                <svg class="w-4 h-4 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </span>
            <div>
                <p class="guest-body text-sm font-medium text-red-300 mb-0.5">Something went wrong</p>
                <p class="guest-body text-xs text-red-400/70">{{ $errors->first() }}</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="anim-fade-up-d1 space-y-5"
          x-data="{
              password: '',
              strength: 0,
              get strengthLabel() {
                  if (this.password.length === 0) return '';
                  if (this.strength <= 1) return 'Weak';
                  if (this.strength === 2) return 'Fair';
                  if (this.strength === 3) return 'Good';
                  return 'Strong';
              },
              get strengthColor() {
                  if (this.strength <= 1) return 'bg-red-500';
                  if (this.strength === 2) return 'bg-yellow-500';
                  if (this.strength === 3) return 'bg-blue-400';
                  return 'bg-green-500';
              },
              get strengthWidth() {
                  return (this.strength / 4 * 100) + '%';
              },
              checkStrength(val) {
                  this.password = val;
                  let score = 0;
                  if (val.length >= 8)  score++;
                  if (/[A-Z]/.test(val)) score++;
                  if (/[0-9]/.test(val)) score++;
                  if (/[^A-Za-z0-9]/.test(val)) score++;
                  this.strength = score;
              }
          }">
        @csrf

        {{-- Hidden token --}}
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

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
                    value="{{ old('email', $request->email) }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                    class="auth-input w-full rounded-lg pl-10 pr-4 py-3 guest-body text-sm"
                >
            </div>
            @error('email')
                <p class="mt-1.5 guest-body text-xs text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- New Password --}}
        <div>
            <label for="password" class="block guest-body text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">New password</label>
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
                    @input="checkStrength($event.target.value)"
                    class="auth-input w-full rounded-lg pl-10 pr-10 py-3 guest-body text-sm"
                >
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center justify-center w-11 pr-2 text-slate-500 hover:text-slate-300 transition-colors">
                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>

            {{-- Password strength bar --}}
            <div x-show="password.length > 0" x-transition class="mt-2 space-y-1.5" style="display:none">
                <div class="h-1 w-full bg-white/8 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="strengthColor"
                        :style="'width:' + strengthWidth"
                    ></div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="guest-body text-xs text-slate-500">Password strength</span>
                    <span class="guest-body text-xs font-medium transition-colors duration-200"
                          :class="{
                              'text-red-400':    strength <= 1,
                              'text-yellow-400': strength === 2,
                              'text-blue-400':   strength === 3,
                              'text-green-400':  strength >= 4
                          }"
                          x-text="strengthLabel"></span>
                </div>
            </div>

            @error('password')
                <p class="mt-1.5 guest-body text-xs text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="block guest-body text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Confirm new password</label>
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
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center justify-center w-11 pr-2 text-slate-500 hover:text-slate-300 transition-colors">
                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            @error('password_confirmation')
                <p class="mt-1.5 guest-body text-xs text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            class="anim-fade-up-d2 w-full guest-body font-medium text-sm text-white bg-primary hover:bg-accent rounded-lg px-4 py-3 transition-all duration-200 shadow-lg shadow-primary/25 hover:shadow-accent/30 hover:-translate-y-px mt-2"
        >
            Reset password
        </button>

        {{-- Back to login --}}
        <div class="anim-fade-up-d3 flex items-center justify-center pt-1">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 guest-body text-sm text-primary dark:text-blue-light hover:text-accent dark:hover:text-white transition-colors duration-150">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to sign in
            </a>
        </div>

    </form>

</x-guest-layout>
