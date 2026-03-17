<div class="p-8 dark:text-white text-gray-900">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-600 font-body mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-amber-400 hover:text-amber-300 transition-colors">Admin</a>
        <span>/</span>
        <span class="text-slate-400">Announcement</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight">Announcement Banner</h1>

        {{-- Status badge --}}
        @if($isActive && $message)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide bg-emerald-500/10 text-emerald-400">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Live
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide dark:bg-slate-800 bg-gray-100 dark:text-slate-500 text-gray-400">
                <span class="w-1.5 h-1.5 rounded-full dark:bg-slate-600 bg-gray-300"></span>
                Inactive
            </span>
        @endif
    </div>

    {{-- Success flash --}}
    @if(session('announcement_saved'))
        <div class="mb-5 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-body flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
            Announcement saved.
        </div>
    @endif

    @if(session('announcement_cleared'))
        <div class="mb-5 px-4 py-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 text-sm font-body flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
            Announcement cleared.
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Left: Form --}}
        <div class="lg:col-span-2">
            <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6">

                {{-- Message --}}
                <div class="mb-5">
                    <label class="block text-xs font-bold uppercase tracking-wider dark:text-slate-400 text-gray-500 font-body mb-2">
                        Message
                    </label>
                    <textarea wire:model="message"
                              rows="3"
                              placeholder="Enter your announcement message…"
                              class="w-full px-4 py-3 text-sm font-body dark:bg-slate-800 bg-white border dark:border-slate-700 border-gray-300 dark:text-white text-gray-900 rounded-xl
                                     dark:placeholder:text-slate-500 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                     transition-all duration-150 resize-none"></textarea>
                    @error('message')
                        <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-[10px] dark:text-slate-600 text-gray-400 font-body">Max 500 characters. Plain text only.</p>
                </div>

                {{-- Type --}}
                <div class="mb-5">
                    <label class="block text-xs font-bold uppercase tracking-wider dark:text-slate-400 text-gray-500 font-body mb-2">
                        Type
                    </label>
                    <div class="flex flex-wrap gap-3">
                        @php
                            $typeOptions = [
                                'info'    => ['label' => 'Info',    'icon' => 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z', 'selected' => 'border-blue-400 ring-2 ring-blue-400/30 bg-blue-500/10 text-blue-400'],
                                'warning' => ['label' => 'Warning', 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z', 'selected' => 'border-amber-400 ring-2 ring-amber-400/30 bg-amber-500/10 text-amber-400'],
                                'success' => ['label' => 'Success', 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'selected' => 'border-emerald-400 ring-2 ring-emerald-400/30 bg-emerald-500/10 text-emerald-400'],
                            ];
                        @endphp

                        @foreach($typeOptions as $value => $opt)
                            <button type="button"
                                    wire:click="$set('type', '{{ $value }}')"
                                    class="px-4 py-2.5 rounded-xl border text-sm font-body font-medium transition-all duration-150
                                           {{ $type === $value
                                               ? $opt['selected']
                                               : 'dark:border-slate-700 border-gray-300 dark:text-slate-400 text-gray-500 dark:hover:border-slate-600 hover:border-gray-400' }}">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $opt['icon'] }}"/>
                                    </svg>
                                    {{ $opt['label'] }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Expiry date --}}
                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider dark:text-slate-400 text-gray-500 font-body mb-2">
                        Expires At <span class="font-normal normal-case dark:text-slate-600 text-gray-400">(optional)</span>
                    </label>
                    <input type="datetime-local"
                           wire:model="expiresAt"
                           class="w-full max-w-xs px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border dark:border-slate-700 border-gray-300 dark:text-white text-gray-900 rounded-xl
                                  focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all duration-150
                                  dark:[color-scheme:dark]">
                    @error('expiresAt')
                        <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-[10px] dark:text-slate-600 text-gray-400 font-body">Leave empty for no expiry. Banner auto-hides after this date.</p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3">
                    <button wire:click="save"
                            class="px-5 py-2.5 text-sm font-semibold font-body text-white bg-primary rounded-xl hover:bg-accent transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-primary/40">
                        Save Announcement
                    </button>

                    @if($message)
                        <button wire:click="toggleActive"
                                class="px-5 py-2.5 text-sm font-semibold font-body rounded-xl border transition-colors duration-150 focus:outline-none
                                       {{ $isActive
                                           ? 'border-amber-500/30 text-amber-400 hover:bg-amber-500/10'
                                           : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10' }}">
                            {{ $isActive ? 'Deactivate' : 'Activate' }}
                        </button>

                        <button wire:click="clear"
                                wire:confirm="Clear announcement? This will remove the banner for all users."
                                class="px-5 py-2.5 text-sm font-semibold font-body text-red-400 rounded-xl border border-red-500/30 hover:bg-red-500/10 transition-colors duration-150 focus:outline-none">
                            Clear
                        </button>
                    @endif
                </div>

            </div>
        </div>

        {{-- Right: Preview --}}
        <div>
            <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-5">
                <h3 class="text-xs font-bold uppercase tracking-wider dark:text-slate-400 text-gray-500 font-body mb-4">Preview</h3>

                @if($message)
                    @php
                        $previewStyle = match($type) {
                            'warning' => 'bg-amber-50 dark:bg-amber-900 border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200',
                            'success' => 'bg-emerald-50 dark:bg-emerald-900 border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200',
                            default   => 'bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200',
                        };
                        $previewIcon = match($type) {
                            'warning' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
                            'success' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                            default   => 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
                        };
                    @endphp

                    <div class="flex items-center gap-2.5 px-4 py-3 rounded-xl border {{ $previewStyle }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $previewIcon }}"/>
                        </svg>
                        <p class="text-sm font-body flex-1">{{ $message }}</p>
                        <button class="flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity cursor-default">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <p class="mt-3 text-[10px] dark:text-slate-600 text-gray-400 font-body">This is how users will see the banner at the top of every page.</p>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <svg class="w-8 h-8 dark:text-slate-700 text-gray-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"/>
                        </svg>
                        <p class="text-xs dark:text-slate-600 text-gray-400 font-body">Enter a message to see the preview</p>
                    </div>
                @endif
            </div>

            {{-- Info card --}}
            <div class="mt-4 dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-5">
                <h3 class="text-xs font-bold uppercase tracking-wider dark:text-slate-400 text-gray-500 font-body mb-3">How it works</h3>
                <ul class="space-y-2 text-xs dark:text-slate-500 text-gray-400 font-body">
                    <li class="flex items-start gap-2">
                        <span class="text-primary mt-0.5">•</span>
                        Banner appears at the top of every page for all logged-in users
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-primary mt-0.5">•</span>
                        Users can dismiss it — stays dismissed until you update the message
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-primary mt-0.5">•</span>
                        If set, banner auto-hides after the expiry date
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-primary mt-0.5">•</span>
                        Updating the message resets all dismissals
                    </li>
                </ul>
            </div>
        </div>

    </div>

</div>
