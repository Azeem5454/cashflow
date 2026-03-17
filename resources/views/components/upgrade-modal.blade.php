@props([
    'show'         => false,
    'feature'      => 'business',   // 'business' | 'team' | 'export'
    'isOwner'      => true,          // export only: false shows "ask your owner" copy
    'businessName' => null,          // export + !isOwner: shown in body text
    'dismissHref'  => null,          // if set, dismiss becomes a back-link instead of wire:click
])

@php
    $isExport = $feature === 'export';
    $isTeam   = $feature === 'team';

    $heading = $isExport ? 'Export is a Pro feature' : 'Upgrade to Pro';

    $features = match($feature) {
        'business' => ['Unlimited businesses', 'Unlimited team members', 'PDF & CSV export', 'Priority support'],
        'export'   => ['PDF export with professional layout', 'CSV export for Excel / Google Sheets', 'Unlimited businesses', 'Unlimited team members'],
        default    => [],   // team: compact, no list
    };

    $accentBar  = $isExport ? 'from-primary to-accent'        : 'from-amber-400 to-amber-500';
    $iconBg     = $isExport ? 'bg-primary/10'                 : 'bg-amber-400/10';
    $ctaClasses = $isExport ? 'bg-primary hover:bg-accent text-white shadow-lg shadow-primary/25'
                            : 'bg-amber-400 hover:bg-amber-300 text-gray-900 shadow-lg shadow-amber-400/25';
    $ctaLabel   = $isExport ? 'Upgrade to Pro'
                : ($isTeam  ? 'View Plans'
                            : 'Upgrade to Pro — $3/mo');
@endphp

@if($show)
<div class="fixed inset-0 z-50 flex items-center justify-center p-4">

    {{-- Backdrop --}}
    @if($dismissHref)
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    @else
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"
             wire:click="$set('showUpgradeModal', false)"></div>
    @endif

    {{-- Card --}}
    <div class="relative w-full max-w-md dark:bg-slate-900 bg-white rounded-2xl shadow-2xl shadow-black/40 overflow-hidden"
         x-data="{ shown: false }"
         x-init="requestAnimationFrame(() => shown = true)"
         :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
         style="transition: opacity 250ms ease, transform 250ms ease;">

        {{-- Accent bar --}}
        <div class="h-1 w-full bg-gradient-to-r {{ $accentBar }}"></div>

        <div class="p-8 text-center">

            {{-- Icon --}}
            <div class="w-16 h-16 rounded-2xl {{ $iconBg }} flex items-center justify-center mx-auto mb-5">
                @if($isExport)
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                @else
                    <svg class="w-8 h-8 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                    </svg>
                @endif
            </div>

            {{-- Heading --}}
            <h2 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 mb-2">{{ $heading }}</h2>

            {{-- Body copy (feature-specific) --}}
            @if($isExport && !$isOwner)
                <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed mb-1">
                    Export is available on <strong class="dark:text-white text-gray-900">Pro</strong> businesses.
                    Ask the owner of <strong class="dark:text-white text-gray-900">{{ $businessName }}</strong>
                    to upgrade to unlock PDF and CSV export for the whole team.
                </p>
            @elseif($isExport)
                <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed mb-1">
                    Upgrade to <strong class="dark:text-white text-gray-900">CashFlow Pro</strong> to export
                    books as PDF or CSV. Only $3/month — unlocks export for your whole team.
                </p>
            @elseif($isTeam)
                <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed mb-1">
                    You've reached the <strong class="dark:text-white text-gray-900">2 member limit</strong>
                    on the Free plan. Upgrade to Pro for just $3/month to invite unlimited team members.
                </p>
            @else
                <p class="text-sm dark:text-slate-400 text-gray-500 mb-1 leading-relaxed">
                    The Free plan includes <strong class="dark:text-white text-gray-900">1 business</strong>.
                    Upgrade to Pro to manage unlimited businesses, team members, and more.
                </p>
            @endif

            <p class="text-xs dark:text-slate-500 text-gray-400 mb-7">Just $3/month — cancel anytime.</p>

            {{-- Feature list --}}
            @if(count($features))
                <ul class="text-left space-y-2.5 mb-7">
                    @foreach($features as $feat)
                        <li class="flex items-center gap-3 text-sm dark:text-slate-300 text-gray-700">
                            <span class="w-5 h-5 rounded-full bg-emerald-500/15 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                            </span>
                            {{ $feat }}
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Actions --}}
            <div class="flex flex-col gap-3">

                {{-- Primary CTA — hidden for non-owner export (they can't upgrade) --}}
                @if(!$isExport || $isOwner)
                    <a href="{{ route('billing') }}"
                       class="inline-flex items-center justify-center gap-2 w-full px-6 py-3
                              {{ $ctaClasses }}
                              text-sm font-bold rounded-xl transition-all duration-200">
                        @if(!$isExport)
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                            </svg>
                        @endif
                        {{ $ctaLabel }}
                    </a>
                @endif

                {{-- Dismiss --}}
                @if($dismissHref)
                    <a href="{{ $dismissHref }}"
                       class="inline-flex items-center justify-center w-full px-6 py-3
                              dark:text-slate-400 text-gray-500
                              dark:hover:text-white hover:text-gray-900
                              text-sm font-medium rounded-xl transition-colors duration-150">
                        ← Back to Dashboard
                    </a>
                @else
                    <button wire:click="$set('showUpgradeModal', false)"
                            class="w-full px-4 py-2.5 text-sm font-medium rounded-xl
                                   dark:text-slate-400 text-gray-500
                                   dark:hover:text-white hover:text-gray-900
                                   dark:hover:bg-slate-800 hover:bg-gray-100
                                   transition-all duration-150">
                        {{ ($isExport && !$isOwner) ? 'Got it' : 'Not Now' }}
                    </button>
                @endif

            </div>
        </div>
    </div>
</div>
@endif
