{{-- Inline status/action icons for a ledger row (desktop: after the label; mobile: under the amounts). --}}
@php $mobile = $mobile ?? false; @endphp
@if($entry->is_flagged)
    <span x-data="{ open: false }"
          @click.stop.prevent="open = !open"
          @click.outside="open = false"
          @keydown.escape.window="open = false"
          @keydown.enter.prevent="open = !open"
          tabindex="0" role="button"
          class="relative flex-shrink-0 inline-flex items-center text-amber-600 dark:text-amber-400 cursor-pointer rounded"
          aria-label="Flagged entry. Show details.">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
        <span x-show="open" x-cloak x-transition.opacity.duration.150ms @click.stop
              class="absolute {{ $mobile ? 'right-0' : 'left-0' }} top-5 z-20 w-56 px-3 py-2 rounded-lg shadow-xl dark:bg-slate-900 bg-white border dark:border-amber-500/30 border-amber-300 text-[11px] font-body dark:text-amber-200 text-amber-800 whitespace-normal text-left">
            <span class="block font-semibold mb-0.5">Flagged</span>
            {{ $entry->flag_reason ?: 'Unusual amount for this category' }}
        </span>
    </span>
@endif
@if($entry->recurring_entry_id)
    <svg class="w-3.5 h-3.5 flex-shrink-0 text-primary dark:text-blue-light" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" role="img" aria-label="Recurring entry">
        <title>Recurring entry</title>
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/>
    </svg>
@endif
@if($entry->attachment_path)
    <button type="button" wire:click.stop="openAttachmentPreview('{{ $entry->id }}')"
            aria-label="View attachment for {{ $label }}" title="View attachment"
            class="flex-shrink-0 rounded text-amber-600 dark:text-amber-400 hover:brightness-110 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
        </svg>
    </button>
@endif
{{-- Comments: always visible with a count; revealed on row hover/focus when empty (desktop) --}}
<button type="button" wire:click.stop="openComments('{{ $entry->id }}')"
        aria-label="{{ $entry->comments_count > 0 ? $entry->comments_count . ' ' . Str::plural('comment', $entry->comments_count) . ' on ' . $label : 'Comment on ' . $label }}"
        title="{{ $entry->comments_count > 0 ? $entry->comments_count . ' ' . Str::plural('comment', $entry->comments_count) : ($business->isPro() ? 'Add comment' : 'Comments (Pro)') }}"
        class="flex-shrink-0 flex items-center gap-0.5 rounded transition-all
               {{ $entry->comments_count > 0
                   ? 'text-primary dark:text-blue-light'
                   : ($mobile ? 'dark:text-slate-500 text-gray-400' : 'dark:text-slate-400 text-gray-500 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 focus:opacity-100') }}">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
    </svg>
    @if($entry->comments_count > 0)
        <span class="text-[10px] font-mono leading-none">{{ $entry->comments_count }}</span>
    @endif
</button>
