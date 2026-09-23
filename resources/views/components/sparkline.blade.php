{{--
    Tiny 7-day net bar sparkline — plain divs, no JS, no chart library.
    Bars grow from a centre baseline: green above for positive days, red below
    for negative ones, so an overdrawn week reads at a glance.

    Props:
      values  array<float>  daily nets, oldest → newest
      label   string        accessible description (required for screen readers)
--}}
@props([
    'values' => [],
    'label'  => 'Last 7 days of daily net',
])
@php
    $values = array_values(array_map(fn ($v) => (float) $v, (array) $values));
    $peak   = 0.0;
    foreach ($values as $v) { $peak = max($peak, abs($v)); }
@endphp
@if(count($values) > 0)
    <span role="img" aria-label="{{ $label }}"
          {{ $attributes->merge(['class' => 'inline-flex items-end gap-[2px] h-5 flex-shrink-0']) }}>
        @foreach($values as $v)
            @php
                // 0 → a 1px tick so empty days still read as days, not gaps.
                $pct = $peak > 0 ? max(8, (int) round(abs($v) / $peak * 100)) : 0;
            @endphp
            <span class="w-[3px] h-full flex flex-col" aria-hidden="true">
                {{-- Top half: positive days grow up from the centre line. --}}
                <span class="w-full h-1/2 flex flex-col justify-end">
                    @if($v > 0)
                        <span class="w-full rounded-t-[1px] bg-emerald-500 dark:bg-emerald-400" style="height:{{ $pct }}%"></span>
                    @endif
                </span>
                {{-- Bottom half: negative days grow down. --}}
                <span class="w-full h-1/2 flex flex-col justify-start">
                    @if($v < 0)
                        <span class="w-full rounded-b-[1px] bg-red-500 dark:bg-red-400" style="height:{{ $pct }}%"></span>
                    @endif
                </span>
            </span>
        @endforeach
    </span>
@endif
