<div class="relative" x-data="{ open: @entangle('open').live }" @keydown.escape.window="$wire.close()">

    {{-- Bell button --}}
    @if($sidebar)
        {{-- Sidebar: full-width row matching Dark Mode toggle style --}}
        <button wire:click="toggle"
                class="relative flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                       dark:text-slate-400 text-gray-600
                       dark:hover:bg-slate-800/80 hover:bg-gray-100
                       dark:hover:text-white hover:text-gray-900">
            <div class="relative flex-shrink-0">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                </svg>
                @if($unreadCount > 0)
                    <span class="absolute -top-1 -right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white font-mono leading-none">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                @endif
            </div>
            <span class="flex-1 text-left font-body">Notifications</span>
        </button>
    @else
        {{-- Compact icon button for mobile top bar --}}
        <button wire:click="toggle"
                class="relative flex items-center justify-center w-9 h-9 rounded-xl transition-all duration-150
                       dark:text-slate-400 text-gray-500
                       dark:hover:bg-slate-800 hover:bg-gray-100
                       dark:hover:text-white hover:text-gray-900">
            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
            </svg>
            @if($unreadCount > 0)
                <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white font-mono leading-none">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>
    @endif

    {{-- Dropdown panel --}}
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
         @click.outside="$wire.close()"
         class="absolute {{ $position === 'down' ? 'top-full mt-2 right-0 origin-top-right' : 'bottom-full mb-2 left-0 origin-bottom-left' }} w-80
                dark:bg-slate-900 bg-white
                dark:border dark:border-slate-700 border border-gray-200
                rounded-2xl shadow-2xl shadow-black/30 overflow-hidden z-50"
         style="display:none;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b dark:border-slate-800 border-gray-100">
            <h3 class="text-sm font-semibold font-heading dark:text-white text-gray-900">Notifications</h3>
            <div class="flex items-center gap-2">
                @if($notifications->isNotEmpty())
                    <button wire:click="markAllRead"
                            class="text-[11px] font-body dark:text-slate-400 text-gray-500 dark:hover:text-primary hover:text-primary transition-colors">
                        Mark all read
                    </button>
                @endif
                <button wire:click="close"
                        class="p-1 rounded-lg dark:text-slate-500 text-gray-400 dark:hover:bg-slate-800 hover:bg-gray-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- List --}}
        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                @php $data = $notification->data; $isUnread = is_null($notification->read_at); @endphp
                <div class="relative flex gap-3 px-4 py-3 border-b dark:border-slate-800/60 border-gray-50 last:border-0
                            {{ $isUnread ? 'dark:bg-primary/5 bg-blue-50/60' : '' }}
                            dark:hover:bg-slate-800/50 hover:bg-gray-50 transition-colors group">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-xs font-bold text-white">
                        {{ strtoupper(substr($data['commenter_name'] ?? '?', 0, 1)) }}
                    </div>
                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-body dark:text-slate-200 text-gray-800 leading-snug">
                            <span class="font-semibold">{{ $data['commenter_name'] ?? 'Someone' }}</span>
                            mentioned you in
                            <span class="font-semibold dark:text-white text-gray-900">{{ $data['entry_description'] ?? 'an entry' }}</span>
                        </p>
                        @if(!empty($data['comment_excerpt']))
                            <p class="text-[11px] font-body dark:text-slate-500 text-gray-400 mt-0.5 truncate italic">
                                "{{ $data['comment_excerpt'] }}"
                            </p>
                        @endif
                        <div class="flex items-center gap-2 mt-1">
                            @if(!empty($data['book_name']))
                                <span class="text-[10px] font-body dark:text-slate-600 text-gray-400">{{ $data['book_name'] }}</span>
                            @endif
                            <span class="text-[10px] font-mono dark:text-slate-600 text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        {{-- View link --}}
                        @if(!empty($data['business_id']) && !empty($data['book_id']))
                            <a href="{{ route('businesses.books.show', [$data['business_id'], $data['book_id']]) }}"
                               wire:navigate
                               @click="$wire.close()"
                               class="inline-block mt-1 text-[11px] font-body text-primary hover:underline">
                                View entry →
                            </a>
                        @endif
                    </div>
                    {{-- Unread dot --}}
                    @if($isUnread)
                        <div class="flex-shrink-0 w-2 h-2 rounded-full bg-primary mt-1.5"></div>
                    @endif
                    {{-- Delete --}}
                    <button wire:click="deleteNotification('{{ $notification->id }}')"
                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 p-1 rounded dark:text-slate-600 text-gray-300 dark:hover:text-red-400 hover:text-red-500 transition-all">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-10 text-center px-4">
                    <div class="w-10 h-10 rounded-full dark:bg-slate-800 bg-gray-100 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold font-heading dark:text-slate-300 text-gray-600">All caught up</p>
                    <p class="text-xs font-body dark:text-slate-500 text-gray-400 mt-0.5">No notifications yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
