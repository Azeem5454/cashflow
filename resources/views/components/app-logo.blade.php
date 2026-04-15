@props(['size' => 'md'])

@php
    $sizes = [
        'sm' => ['icon' => 'w-7 h-7', 'svg' => 'w-3.5 h-3.5', 'text' => 'text-lg',  'img' => 'h-7'],
        'md' => ['icon' => 'w-8 h-8',  'svg' => 'w-4 h-4',   'text' => 'text-xl',  'img' => 'h-8'],
        'lg' => ['icon' => 'w-10 h-10', 'svg' => 'w-5 h-5',  'text' => 'text-2xl', 'img' => 'h-10'],
    ];
    $s = $sizes[$size] ?? $sizes['md'];

    $darkPath  = public_path('brand/logo-dark.png');
    $lightPath = public_path('brand/logo-light.png');
    $hasDark   = file_exists($darkPath);
    $hasLight  = file_exists($lightPath);

    $darkUrl  = $hasDark  ? asset('brand/logo-dark.png')  . '?v=' . filemtime($darkPath)  : null;
    $lightUrl = $hasLight ? asset('brand/logo-light.png') . '?v=' . filemtime($lightPath) : null;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    @if($hasDark && $hasLight)
        {{-- Both variants uploaded: swap based on theme --}}
        <img src="{{ $darkUrl }}"  alt="{{ config('app.name', 'CashFlow') }}" class="{{ $s['img'] }} w-auto object-contain hidden dark:block">
        <img src="{{ $lightUrl }}" alt="{{ config('app.name', 'CashFlow') }}" class="{{ $s['img'] }} w-auto object-contain dark:hidden">
    @elseif($hasDark)
        {{-- Only dark uploaded: use it on both modes --}}
        <img src="{{ $darkUrl }}" alt="{{ config('app.name', 'CashFlow') }}" class="{{ $s['img'] }} w-auto object-contain">
    @elseif($hasLight)
        {{-- Only light uploaded: use it on both modes --}}
        <img src="{{ $lightUrl }}" alt="{{ config('app.name', 'CashFlow') }}" class="{{ $s['img'] }} w-auto object-contain">
    @else
        {{-- No logo uploaded: fallback to default icon + text --}}
        <div class="{{ $s['icon'] }} rounded-lg bg-primary flex items-center justify-center flex-shrink-0 shadow-lg shadow-primary/30">
            <svg class="{{ $s['svg'] }} text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
        </div>
        <span class="font-display font-extrabold {{ $s['text'] }} dark:text-white text-gray-900 tracking-tight">{{ config('app.name', 'CashFlow') }}</span>
    @endif
</div>
