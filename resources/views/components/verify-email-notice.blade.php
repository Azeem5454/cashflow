@props(['resent' => false, 'action' => 'do that'])

{{-- Inline soft-verification notice for Livewire actions that email other
     people. The host component uses App\Livewire\Concerns\RequiresVerifiedEmail. --}}
<div {{ $attributes->merge(['class' => 'flex items-start gap-3 px-4 py-3 rounded-xl bg-amber-50 dark:bg-slate-800 border border-amber-200 dark:border-slate-700']) }}
     role="alert">
    <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
    </svg>
    <div class="flex-1 min-w-0">
        <p class="text-sm font-body text-amber-800 dark:text-slate-200">
            Please verify your email address first. We only need this before you {{ $action }}.
        </p>
        @if($resent)
            <p class="mt-1 text-xs font-body text-emerald-600 dark:text-emerald-400">Verification email sent. Check your inbox.</p>
        @else
            <button type="button"
                    wire:click="resendVerificationEmail"
                    wire:loading.attr="disabled"
                    wire:target="resendVerificationEmail"
                    class="mt-1 text-xs font-semibold font-body text-primary dark:text-blue-light hover:text-accent transition-colors disabled:opacity-50">
                Resend verification email
            </button>
        @endif
    </div>
</div>
