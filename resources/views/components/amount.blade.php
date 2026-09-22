{{--
    Consistent money rendering: Geist Mono, tabular figures, AA-contrast colours.

    Props:
      value    number|string   the amount (may be negative)
      symbol   string          currency symbol prefix (e.g. "$", "Rs")
      type     'in'|'out'|null entry direction → +green / −red, sign always shown
      tone     'in'|'out'|'net'|'neutral'|'plain'
               in/out  = green/red regardless of sign
               net     = blue when ≥ 0, red when < 0
               neutral = slate when ≥ 0, red when < 0 (running balance)
               plain   = inherit colour
      decimals int             default 2
      sign     bool|null       force +/− prefix on/off (default: on for type, off otherwise;
                               negatives always get −)
--}}
@props([
    'value'    => 0,
    'symbol'   => '',
    'type'     => null,
    'tone'     => null,
    'decimals' => 2,
    'sign'     => null,
])
@php
    $num      = is_numeric($value) ? (float) $value : 0.0;
    $negative = $num < 0;
    $tone     = $tone ?? ($type ?? 'neutral');
    $showSign = $sign ?? ($type !== null);

    $prefix = '';
    if ($type === 'out') {
        $prefix = '−';
    } elseif ($type === 'in') {
        $prefix = $showSign ? '+' : '';
    } elseif ($negative) {
        $prefix = '−';
    } elseif ($showSign && $num > 0) {
        $prefix = '+';
    }

    $colour = match (true) {
        $tone === 'in'                   => 'text-emerald-600 dark:text-emerald-400',
        $tone === 'out'                  => 'text-red-600 dark:text-red-400',
        $tone === 'plain'                => '',
        $negative                        => 'text-red-600 dark:text-red-400',
        $tone === 'net'                  => 'text-primary dark:text-blue-light',
        default                          => 'text-slate-700 dark:text-slate-300',
    };
@endphp
<span {{ $attributes->merge(['class' => trim("font-mono tabular-nums whitespace-nowrap {$colour}")]) }}>{{ $prefix }}{{ $symbol }}{{ number_format(abs($num), (int) $decimals) }}</span>
