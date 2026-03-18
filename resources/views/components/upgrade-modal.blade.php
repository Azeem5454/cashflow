@props([
    'show'         => false,
    'feature'      => 'business',   // 'business' | 'team' | 'export' | 'recurring' | 'ai' | 'comments' | 'daterange'
    'isOwner'      => true,
    'businessName' => null,
    'dismissHref'  => null,
])

@php
    $isExport    = $feature === 'export';
    $isTeam      = $feature === 'team';
    $isRecurring = $feature === 'recurring';
    $isAi        = $feature === 'ai';
    $isComments  = $feature === 'comments';
    $isDaterange = $feature === 'daterange';

    $heading = match($feature) {
        'export'    => 'Export is a Pro feature',
        'recurring' => 'Recurring entries is a Pro feature',
        'ai'        => 'AI features are Pro-only',
        'comments'  => 'Comments are a Pro feature',
        'daterange' => 'Date range filtering is Pro',
        default     => 'Upgrade to Pro',
    };

    $features = match($feature) {
        'business'  => ['Unlimited businesses', 'Unlimited team members', 'PDF & CSV export', 'Priority support'],
        'export'    => ['PDF export with professional layout', 'CSV export for Excel / Google Sheets', 'Unlimited businesses', 'Unlimited team members'],
        'recurring' => ['Auto-create entries on a daily, weekly, monthly, or yearly schedule', 'Pause or delete rules anytime', 'All Pro features included'],
        'ai'        => ['AI receipt scanning — photo a receipt, fields fill themselves', 'AI auto-categorization on description', 'AI cash flow insights on the Reports tab', '200 OCR scans/month included'],
        'comments'  => ['Comment on any entry and @mention teammates', 'In-app notification bell for mentions', 'Full comment history per entry'],
        'daterange' => ['Filter entries by any custom date range', 'Compare two periods side-by-side (this month vs last month)', 'See % change in Cash In, Cash Out, and Net'],
        default     => [],
    };

    $ctaLabel = $isTeam ? 'View Plans' : 'Upgrade to Pro — $5/mo';
@endphp

@if($show)
<div class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 9999;">

    {{-- Backdrop --}}
    @if($dismissHref)
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    @else
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"
             wire:click="$set('upgradeModalFeature', '')"></div>
    @endif

    {{-- Card --}}
    <div class="relative w-full max-w-md dark:bg-slate-900 bg-white rounded-2xl shadow-2xl shadow-black/40 overflow-hidden"
         x-data="{ shown: false }"
         x-init="requestAnimationFrame(() => shown = true)"
         :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
         style="transition: opacity 250ms ease, transform 250ms ease;">

        {{-- Accent bar --}}
        <div class="h-1 w-full bg-gradient-to-r from-amber-400 to-amber-500"></div>

        <div class="p-8 text-center">

            {{-- Icon --}}
            <div class="w-16 h-16 rounded-2xl bg-amber-400/10 flex items-center justify-center mx-auto mb-5">
                @if($isExport)
                    <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                @elseif($isAi)
                    <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                    </svg>
                @elseif($isRecurring)
                    <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/>
                    </svg>
                @elseif($isComments)
                    <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                    </svg>
                @elseif($isDaterange)
                    <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                    </svg>
                @else
                    <svg class="w-8 h-8 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                    </svg>
                @endif
            </div>

            {{-- Heading --}}
            <h2 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 mb-2">{{ $heading }}</h2>

            {{-- Body copy --}}
            @if($isExport && !$isOwner)
                <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed mb-1">
                    Export is available on <strong class="dark:text-white text-gray-900">Pro</strong> businesses.
                    Ask the owner of <strong class="dark:text-white text-gray-900">{{ $businessName }}</strong>
                    to upgrade to unlock PDF and CSV export for the whole team.
                </p>
            @elseif($isExport)
                <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed mb-1">
                    Upgrade to <strong class="dark:text-white text-gray-900">CashFlow Pro</strong> to export
                    books as PDF or CSV. Unlocks export for your whole team.
                </p>
            @elseif($isTeam)
                <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed mb-1">
                    You've reached the <strong class="dark:text-white text-gray-900">2 member limit</strong>
                    on the Free plan. Upgrade to Pro to invite unlimited team members.
                </p>
            @elseif($isRecurring)
                <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed mb-1">
                    Set entries to repeat automatically on any schedule —
                    <strong class="dark:text-white text-gray-900">daily, weekly, monthly, or yearly</strong>.
                    Stop re-entering the same transactions every period.
                </p>
            @elseif($isAi)
                <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed mb-1">
                    Upgrade to <strong class="dark:text-white text-gray-900">CashFlow Pro</strong> to unlock
                    AI receipt scanning, auto-categorization, and cash flow insights.
                </p>
            @elseif($isComments)
                <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed mb-1">
                    Upgrade to <strong class="dark:text-white text-gray-900">CashFlow Pro</strong> to add comments
                    on entries, @mention teammates, and get notified when someone mentions you.
                </p>
            @elseif($isDaterange)
                <p class="text-sm dark:text-slate-400 text-gray-500 leading-relaxed mb-1">
                    Filter by any custom date range and compare two periods side-by-side.
                    See exactly how your cash flow changed — <strong class="dark:text-white text-gray-900">this month vs last month</strong>, this quarter vs previous, any range you choose.
                </p>
            @else
                <p class="text-sm dark:text-slate-400 text-gray-500 mb-1 leading-relaxed">
                    The Free plan includes <strong class="dark:text-white text-gray-900">1 business</strong>.
                    Upgrade to Pro to manage unlimited businesses, team members, and more.
                </p>
            @endif

            <p class="text-xs dark:text-slate-500 text-gray-400 mb-7">Just $5/month — cancel anytime.</p>

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

                {{-- Primary CTA — hidden for non-owner export --}}
                @if(!$isExport || $isOwner)
                    <a href="{{ route('billing') }}"
                       class="inline-flex items-center justify-center gap-2 w-full px-6 py-3
                              bg-amber-400 hover:bg-amber-300 text-gray-900 shadow-lg shadow-amber-400/25
                              text-sm font-bold rounded-xl transition-all duration-200">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                        </svg>
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
                    <button wire:click="$set('upgradeModalFeature', '')"
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
