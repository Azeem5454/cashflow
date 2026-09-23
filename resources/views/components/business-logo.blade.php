{{--
    A business's logo, falling back to an initial avatar when none is set.

    Props:
      business  App\Models\Business
      size      tailwind size classes for the square (default w-10 h-10)
      rounded   corner radius class (default rounded-xl)
      text      initial's text size class (default text-sm)
--}}
@props([
    'business',
    'size'    => 'w-10 h-10',
    'rounded' => 'rounded-xl',
    'text'    => 'text-sm',
])
@php $logo = $business->logoUrl(); @endphp
<div {{ $attributes->merge(['class' => "flex-shrink-0 $size $rounded overflow-hidden flex items-center justify-center " . ($logo ? 'dark:bg-slate-800 bg-gray-100' : 'bg-primary/10 dark:bg-primary/15')]) }}>
    @if($logo)
        <img src="{{ $logo }}" alt="" class="w-full h-full object-contain">
    @else
        <span class="font-display font-extrabold {{ $text }} text-primary dark:text-blue-light leading-none">
            {{ strtoupper(mb_substr($business->name, 0, 1)) }}
        </span>
    @endif
</div>
