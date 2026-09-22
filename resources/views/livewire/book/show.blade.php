<div class="min-h-full"
     x-init="
         const p = new URLSearchParams(window.location.search);
         if (p.get('addEntry') === 'in' || p.get('addEntry') === 'out') {
             $nextTick(() => $wire.call('openAddEntry', p.get('addEntry')));
         }
     "
     x-data="{
         selectedIds: [],
         filteredIds: [],
         selectAll: false,

         toggleEntry(id) {
             const idx = this.selectedIds.indexOf(id);
             if (idx > -1) {
                 this.selectedIds.splice(idx, 1);
             } else {
                 this.selectedIds.push(id);
             }
             this.syncSelectAll();
         },

         toggleSelectAll() {
             if (this.selectAll) {
                 this.selectedIds = [];
                 this.selectAll = false;
             } else {
                 this.selectedIds = [...this.filteredIds];
                 this.selectAll = true;
             }
         },

         syncSelectAll() {
             this.selectAll = this.filteredIds.length > 0 && this.selectedIds.length === this.filteredIds.length;
         },

         isSelected(id) {
             return this.selectedIds.includes(id);
         },

         clearSelection() {
             this.selectedIds = [];
             this.selectAll = false;
         },

         get hasSelection() {
             return this.selectedIds.length > 0;
         }
     }"
     x-on:bulk-operation-complete.window="clearSelection()">

    {{-- ===== STICKY HEADER ===== --}}
    <div class="sticky top-0 z-30 px-4 sm:px-6 lg:px-8 py-4
                dark:bg-navy/80 bg-white/90 backdrop-blur-xl
                dark:border-b dark:border-white/5 border-b border-gray-200/70">
        <div class="max-w-5xl mx-auto flex items-center gap-3 sm:gap-4">

            <a href="{{ route('businesses.show', $business) }}" wire:navigate
               aria-label="Back to {{ $business->name }}"
               class="p-2 rounded-lg dark:text-slate-500 text-gray-400
                      dark:hover:bg-slate-800 hover:bg-gray-100
                      dark:hover:text-white hover:text-gray-700
                      transition-all duration-150 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="font-display font-extrabold text-xl dark:text-white text-gray-900 tracking-tight leading-none truncate">
                        {{ $book->name }}
                    </h1>
                    @php
                        $bookStatus = null;
                        if ($book->period_starts_at && $book->period_ends_at) {
                            $bookStatus = now()->between($book->period_starts_at, $book->period_ends_at) ? 'active'
                                        : ($book->period_ends_at->lt(now()) ? 'archived' : 'upcoming');
                        }
                    @endphp
                    @if($bookStatus === 'upcoming')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold flex-shrink-0
                                     bg-blue-500/10 text-blue-600 dark:text-blue-400">Upcoming</span>
                    @endif
                    @if($userRole !== 'owner')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider flex-shrink-0
                                     {{ $userRole === 'editor'
                                         ? 'dark:bg-blue-500/10 dark:text-blue-400 bg-blue-50 text-blue-600'
                                         : 'dark:bg-slate-800 dark:text-slate-500 bg-gray-100 text-gray-500' }}">
                            {{ $userRole }}
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs dark:text-slate-500 text-gray-400 font-body">{{ $business->name }}</span>
                    <span class="w-1 h-1 rounded-full dark:bg-slate-700 bg-gray-300 flex-shrink-0"></span>
                    <span class="text-xs font-mono dark:text-slate-500 text-gray-400 uppercase tracking-wider">{{ $business->currency }}</span>
                    @if($book->period_starts_at && $book->period_ends_at)
                        @php
                            $hPs = $book->period_starts_at; $hPe = $book->period_ends_at;
                            $hPeriod = $hPs->format('M Y') === $hPe->format('M Y')
                                ? $hPs->format('M Y')
                                : $hPs->format('j M') . ' – ' . $hPe->format('j M Y');
                        @endphp
                        <span class="w-1 h-1 rounded-full dark:bg-slate-700 bg-gray-300 flex-shrink-0"></span>
                        <span class="text-xs dark:text-slate-500 text-gray-400 font-body">{{ $hPeriod }}</span>
                    @endif
                </div>
            </div>

            {{-- Cash In / Cash Out — split primary CTA --}}
            @if($userRole !== 'viewer')
                <div class="flex items-center gap-2 flex-shrink-0" role="group" aria-label="Add entry">
                    <button type="button" wire:click="openAddEntry('in')" x-on:click="$dispatch('open-entry-panel')"
                            aria-label="Add cash in entry"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-lg border text-sm font-semibold font-body
                                   bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100 hover:border-emerald-300
                                   dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800 dark:hover:bg-emerald-900
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:focus-visible:ring-offset-navy
                                   transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span><span class="hidden sm:inline">Cash </span>In</span>
                    </button>
                    <button type="button" wire:click="openAddEntry('out')" x-on:click="$dispatch('open-entry-panel')"
                            aria-label="Add cash out entry"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-lg border text-sm font-semibold font-body
                                   bg-red-50 text-red-700 border-red-200 hover:bg-red-100 hover:border-red-300
                                   dark:bg-red-950 dark:text-red-300 dark:border-red-800 dark:hover:bg-red-900
                                   focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 dark:focus-visible:ring-offset-navy
                                   transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                        </svg>
                        <span><span class="hidden sm:inline">Cash </span>Out</span>
                    </button>
                </div>
            @endif

            {{-- Export dropdown --}}
            <div x-data="{ exportOpen: false }" class="relative flex-shrink-0">
                <button @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                        title="Export" aria-label="Export book" :aria-expanded="exportOpen.toString()"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold font-body
                               dark:bg-slate-800 bg-gray-100
                               dark:text-slate-300 text-gray-700
                               dark:hover:bg-slate-700 hover:bg-gray-200
                               dark:border dark:border-slate-700 border border-gray-200
                               transition-all duration-150">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    <span class="hidden sm:inline">Export</span>
                    <svg class="w-3 h-3 dark:text-slate-500 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div x-show="exportOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="absolute top-full right-0 mt-1 w-52 z-40
                            dark:bg-slate-800 bg-white
                            dark:border dark:border-slate-700 border border-gray-200
                            rounded-xl shadow-xl shadow-black/20 overflow-hidden py-1"
                     style="display:none;">

                    {{-- Export header --}}
                    <div class="px-4 py-2 border-b dark:border-slate-700 border-gray-100">
                        <span class="text-[10px] font-semibold uppercase tracking-wider
                                     dark:text-slate-500 text-gray-400 font-body">Export Book</span>
                        @if(!$business->isPro())
                            <x-pro-badge class="ml-2" />
                        @endif
                    </div>

                    <button @click="$wire.exportPdf(); exportOpen = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                   dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        Export as PDF
                    </button>

                    <button @click="$wire.exportCsv(); exportOpen = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                   dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75.125v-5.25c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V19.5M3.375 19.5H3m17.25 0h-1.5m1.5 0c.621 0 1.125-.504 1.125-1.125V4.125C21.75 3.504 21.246 3 20.625 3H3.375C2.754 3 2.25 3.504 2.25 4.125v14.25"/>
                        </svg>
                        Export as CSV
                    </button>
                </div>
            </div>

            {{-- Settings gear (owner/editor only) --}}
            @if($userRole !== 'viewer')
                <div x-data="{ open: false }" class="relative flex-shrink-0">
                    <button @click="open = !open" @click.outside="open = false"
                            aria-label="Book settings" title="Book settings"
                            class="p-2 rounded-lg dark:text-slate-400 text-gray-500
                                   dark:hover:bg-slate-800 hover:bg-gray-100
                                   dark:hover:text-white hover:text-gray-700
                                   transition-all duration-150">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="absolute top-full right-0 mt-1 w-52 z-40
                                dark:bg-slate-800 bg-white
                                dark:border dark:border-slate-700 border border-gray-200
                                rounded-xl shadow-xl shadow-black/20 overflow-hidden py-1"
                         style="display:none;">

                        <button @click="$wire.openEditBook(); open = false"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                            </svg>
                            Edit Book
                        </button>

                        <button @click="$wire.openDuplicateBook(); open = false"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
                            </svg>
                            Duplicate Book
                        </button>

                        <button @click="$wire.openEmailReportModal(); open = false"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                            </svg>
                            Email Reports
                            @if(!$business->isPro())
                                <x-pro-badge class="ml-auto" />
                            @endif
                        </button>

                        @if($userRole === 'owner')
                        <div class="my-1 dark:border-t dark:border-slate-700 border-t border-gray-100"></div>
                        <button @click="$wire.openDeleteBook(); open = false"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                       text-red-600 dark:text-red-400
                                       dark:hover:bg-red-500/10 hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                            </svg>
                            Delete Book
                        </button>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- ===== nlpVoice Alpine factory (voice input in the entry slide-over) ===== --}}
    <script>
    /**
     * nlpVoice — Alpine component for voice input on the NL entry field,
     * using the browser's Web Speech API. Zero backend cost: transcription
     * runs locally (Google on Chrome/Android, Apple on Safari/iOS).
     *
     * Degradation:
     *  - no SpeechRecognition class → mic hidden entirely (Firefox)
     *  - permission denied / mic missing / network → visible amber status
     *  - user closes slide-over mid-recording → abort() on teardown
     *
     * Debugging: append `?voice_debug=1` to the URL to log every state
     * transition to the console. Use this when reproducing on a user's
     * device — the logs identify exactly where the flow breaks.
     */
    function nlpVoice() {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        const DEBUG = new URLSearchParams(location.search).has('voice_debug');
        const log = (...a) => DEBUG && console.log('[voice]', ...a);

        return {
            supported: !!SR,
            open: false,
            listening: false,
            voiceStatus: '',
            _rec: null,
            _finalText: '',
            _silenceTimer: null,

            init() {
                log('init', { supported: this.supported, secure: window.isSecureContext, ua: navigator.userAgent });

                // Web Speech requires a secure context (https). Firing from
                // http:// will silently fail — tell the user up front.
                if (this.supported && !window.isSecureContext) {
                    this.supported = false;
                    return;
                }
            },

            _makeRec() {
                // Build a fresh SpeechRecognition per session. Some browsers
                // (mobile Safari) get into a bad state if reused after an
                // error — safer to discard and rebuild each toggle.
                const rec = new SR();
                rec.continuous      = false;                   // single utterance — simpler, more reliable than continuous across devices
                rec.interimResults  = true;
                rec.lang            = navigator.language || 'en-US';
                rec.maxAlternatives = 1;

                rec.onstart = () => {
                    log('onstart');
                    this.listening = true;
                    this.voiceStatus = 'Listening — speak now…';
                };

                rec.onresult = (e) => {
                    let finalSoFar = '';
                    let interim    = '';
                    for (let i = e.resultIndex; i < e.results.length; i++) {
                        const r = e.results[i];
                        if (r.isFinal) finalSoFar += r[0].transcript;
                        else           interim    += r[0].transcript;
                    }
                    if (finalSoFar) this._finalText += (this._finalText ? ' ' : '') + finalSoFar.trim();
                    const merged = (this._finalText + ' ' + interim).trim();
                    log('onresult', { final: finalSoFar, interim, merged });

                    // Write to BOTH the DOM input (for wire:model pickup) AND
                    // the Livewire property — belt & braces so the value
                    // survives whichever sync path fires first.
                    this._setInput(merged);

                    // Auto-stop after short silence once we have committed text
                    if (this._finalText && !interim) {
                        clearTimeout(this._silenceTimer);
                        this._silenceTimer = setTimeout(() => this.stop(), 1200);
                    }
                };

                rec.onerror = (e) => {
                    log('onerror', e.error);
                    this.listening = false;
                    clearTimeout(this._silenceTimer);
                    const map = {
                        'not-allowed':         'Microphone blocked. Tap the lock icon in the address bar → allow microphone.',
                        'service-not-allowed': 'Microphone blocked by your OS. Check System Settings → Privacy → Microphone.',
                        'no-speech':           'Didn\'t catch that — try again.',
                        'audio-capture':       'No microphone found on this device.',
                        'network':             'Speech service unreachable — check your connection.',
                        'aborted':             '', // user-initiated, don't show
                        'language-not-supported': 'Your language isn\'t supported — set browser language to English and retry.',
                    };
                    const msg = map[e.error] ?? 'Voice input failed. Please type instead.';
                    if (msg) {
                        this.voiceStatus = msg;
                        // Keep error visible until user dismisses or starts again.
                    }
                };

                rec.onend = () => {
                    log('onend', { finalText: this._finalText });
                    this.listening = false;
                    clearTimeout(this._silenceTimer);

                    // Clear the transient "Listening…" status (but leave any
                    // error message that onerror set).
                    if (this.voiceStatus === 'Listening — speak now…') {
                        this.voiceStatus = '';
                    }

                    // Auto-submit if we got a meaningful transcript
                    if (this._finalText.trim().length >= 4) {
                        log('auto-submit', this._finalText);
                        // Small delay so the DOM→Livewire sync from _setInput lands first.
                        setTimeout(() => this.$wire.parseEntryText(), 80);
                    }
                };

                return rec;
            },

            // Push transcript into the input element AND into Livewire state.
            _setInput(value) {
                // 1. Direct DOM update — visible to the user immediately,
                //    bypasses any Livewire round-trip delay.
                if (this.$refs.nlpField) {
                    this.$refs.nlpField.value = value;
                    // Fire input event so wire:model picks it up
                    this.$refs.nlpField.dispatchEvent(new Event('input', { bubbles: true }));
                }
                // 2. Explicit Livewire set (defers server sync with `false`)
                try { this.$wire.set('nlpInput', value, false); } catch (_) {}
            },

            toggle() {
                log('toggle', { listening: this.listening });
                this.listening ? this.stop() : this.start();
            },

            start() {
                if (!this.supported) {
                    this.voiceStatus = 'Voice input isn\'t supported in this browser. Try Chrome or Safari.';
                    return;
                }
                if (this.listening) return;

                this._finalText = '';
                this._setInput('');
                try { this.$wire.set('nlpError', '', false); } catch (_) {}
                this.voiceStatus = 'Starting microphone…';

                // Rebuild per session for robustness
                this._rec = this._makeRec();

                try {
                    this._rec.start();
                    // listening=true happens in onstart for accuracy
                } catch (err) {
                    log('start threw', err);
                    this.listening = false;
                    this.voiceStatus = 'Could not start microphone. Make sure no other tab is using it.';
                }
            },

            stop() {
                log('stop');
                if (!this._rec) return;
                try { this._rec.stop(); } catch (_) {}
                this.listening = false;
                clearTimeout(this._silenceTimer);
            },

            dismissVoiceStatus() {
                this.voiceStatus = '';
            },

            teardown() {
                log('teardown');
                if (this._rec) {
                    try { this._rec.abort(); } catch (_) {}
                }
                clearTimeout(this._silenceTimer);
            },
        };
    }
    </script>

    {{-- ===== EDIT / DUPLICATE BOOK MODALS (shared with the business page) ===== --}}
    @include('livewire.partials.book-modals')

    {{-- ===== DELETE BOOK MODAL ===== --}}
    @if($showDeleteBook)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="$set('showDeleteBook', false)"></div>
            <div class="relative w-full max-w-md dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 p-6">
                <div class="w-12 h-12 rounded-xl bg-red-500/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-lg dark:text-white text-gray-900 text-center mb-1">Delete Book</h3>
                <p class="text-sm dark:text-slate-400 text-gray-500 font-body text-center mb-4">
                    This will permanently delete <strong class="dark:text-white text-gray-900">{{ $book->name }}</strong>
                    and all its entries. This action cannot be undone.
                </p>
                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                        Type <span class="dark:text-red-400 text-red-500 normal-case tracking-normal">{{ $book->name }}</span> to confirm
                    </label>
                    <input type="text"
                           wire:model="deleteConfirmName"
                           wire:keydown.enter="deleteBook"
                           placeholder="{{ $book->name }}"
                           class="w-full px-4 py-2.5 text-sm font-body
                                  dark:bg-slate-800 bg-white
                                  dark:border dark:border-slate-700 border border-gray-300
                                  dark:text-white text-gray-900 rounded-xl
                                  placeholder:dark:text-slate-600 placeholder:text-gray-400
                                  focus:outline-none focus:ring-2 focus:ring-red-500/40 focus:border-red-500/50
                                  transition-all duration-150">
                    @error('deleteConfirmName')<p class="text-xs text-red-600 dark:text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-2">
                    <button wire:click="$set('showDeleteBook', false)"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200
                                   rounded-lg transition-all duration-200">
                        Cancel
                    </button>
                    <button wire:click="deleteBook"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-red-500 text-white hover:bg-red-400
                                   rounded-lg transition-all duration-200 shadow-lg shadow-red-500/20">
                        Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== EMAIL REPORT SETTINGS MODAL ===== --}}
    @if($showEmailReportModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-data="{ shown: false }"
         x-init="requestAnimationFrame(() => shown = true)"
         @keydown.escape.window="$wire.set('showEmailReportModal', false)">

        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showEmailReportModal', false)"></div>

        <div class="relative w-full max-w-md dark:bg-dark bg-white rounded-2xl shadow-2xl
                    dark:border dark:border-slate-700 border border-gray-200 overflow-hidden"
             :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
             style="transition: opacity 200ms ease, transform 200ms ease;">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4
                        dark:border-b dark:border-slate-700 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                        </svg>
                    </div>
                    <h3 class="font-heading font-bold text-lg dark:text-white text-gray-900">Email Reports</h3>
                </div>
                <button aria-label="Close" @click="$wire.set('showEmailReportModal', false)"
                        class="p-2 rounded-lg dark:text-slate-500 text-gray-400
                               dark:hover:bg-slate-700 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-5">

                {{-- Enable/disable toggle --}}
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold font-body dark:text-white text-gray-900">Enable email reports</p>
                        <p class="text-xs dark:text-slate-500 text-gray-400 mt-0.5">Automatically send summaries for this book</p>
                    </div>
                    <button aria-label="Toggle email reports" wire:click="$toggle('emailReportActive')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200
                                   {{ $emailReportActive ? 'bg-primary' : 'dark:bg-slate-600 bg-gray-300' }}">
                        <span class="inline-block h-4 w-4 rounded-full bg-white shadow-sm transform transition-transform duration-200
                                     {{ $emailReportActive ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </div>

                {{-- Frequency --}}
                <div>
                    <label class="block text-sm font-medium font-body dark:text-slate-300 text-gray-700 mb-2">Frequency</label>
                    <div class="flex gap-2">
                        <button wire:click="$set('emailReportFrequency', 'weekly')"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold font-body rounded-lg border transition-all duration-150
                                       {{ $emailReportFrequency === 'weekly'
                                          ? 'bg-primary/10 border-primary text-primary dark:bg-primary/15 dark:border-primary dark:text-blue-light'
                                          : 'dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 dark:text-slate-400 text-gray-500 hover:border-primary/50' }}">
                            <svg class="w-4 h-4 mx-auto mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                            </svg>
                            Weekly
                            <p class="text-[10px] mt-0.5 font-normal dark:text-slate-500 text-gray-400">Every Monday</p>
                        </button>
                        <button wire:click="$set('emailReportFrequency', 'monthly')"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold font-body rounded-lg border transition-all duration-150
                                       {{ $emailReportFrequency === 'monthly'
                                          ? 'bg-primary/10 border-primary text-primary dark:bg-primary/15 dark:border-primary dark:text-blue-light'
                                          : 'dark:bg-slate-800 bg-gray-50 dark:border-slate-700 border-gray-200 dark:text-slate-400 text-gray-500 hover:border-primary/50' }}">
                            <svg class="w-4 h-4 mx-auto mb-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-8.25h.008v.008H12V11.25Z"/>
                            </svg>
                            Monthly
                            <p class="text-[10px] mt-0.5 font-normal dark:text-slate-500 text-gray-400">1st of month</p>
                        </button>
                    </div>
                </div>

                {{-- Recipients --}}
                <div>
                    <label class="block text-sm font-medium font-body dark:text-slate-300 text-gray-700 mb-2">Recipients</label>
                    <textarea wire:model="emailReportRecipients"
                              rows="2"
                              placeholder="email@example.com, teammate@example.com"
                              class="w-full px-4 py-2.5 rounded-lg text-sm font-body resize-none
                                     dark:bg-slate-800 bg-white
                                     dark:border-slate-700 border-gray-300 border
                                     dark:text-white text-gray-900
                                     dark:placeholder-slate-500 placeholder-gray-400
                                     focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                     transition-all duration-150"></textarea>
                    <p class="text-[11px] dark:text-slate-500 text-gray-400 mt-1.5">Comma-separated. Max 10 recipients.</p>
                    @error('emailReportRecipients')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if($emailVerificationRequired)
                    <x-verify-email-notice :resent="$verificationEmailResent" action="send email reports" />
                @endif

                {{-- Last sent + Send test --}}
                @if($hasExistingSchedule)
                <div class="flex items-center justify-between p-3 rounded-xl dark:bg-slate-800/50 bg-gray-50 border dark:border-slate-700 border-gray-200">
                    <div>
                        <p class="text-[11px] font-medium dark:text-slate-500 text-gray-400 uppercase tracking-wider">Last sent</p>
                        <p class="text-sm font-body dark:text-slate-300 text-gray-700 mt-0.5">
                            {{ $emailReportLastSent ?: 'Never' }}
                        </p>
                    </div>
                    <button wire:click="sendTestReport"
                            wire:loading.attr="disabled"
                            wire:target="sendTestReport"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold font-body rounded-lg
                                   dark:bg-slate-700 bg-white dark:text-slate-300 text-gray-600
                                   border dark:border-slate-600 border-gray-300
                                   dark:hover:bg-slate-600 hover:bg-gray-100
                                   disabled:opacity-50 transition-all duration-150">
                        <span wire:loading.remove wire:target="sendTestReport">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                            </svg>
                        </span>
                        <span wire:loading wire:target="sendTestReport">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="sendTestReport">Send Test</span>
                        <span wire:loading wire:target="sendTestReport">Sending...</span>
                    </button>
                </div>
                @endif

                {{-- Info note --}}
                <div class="flex items-start gap-2.5 p-3 rounded-xl dark:bg-slate-800/50 bg-blue-50 border dark:border-slate-700 border-blue-100">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0 dark:text-blue-light text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                    </svg>
                    <p class="text-xs dark:text-slate-400 text-gray-600 leading-relaxed">
                        Reports include period totals, top spending categories, and 10 most recent entries. Sent {{ $emailReportFrequency === 'weekly' ? 'every Monday' : 'on the 1st of each month' }} at 9:00 AM UTC.
                        @if(!$hasExistingSchedule)
                            Your first report will be sent immediately when you enable.
                        @endif
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 dark:border-t dark:border-slate-700 border-t border-gray-200
                        flex items-center {{ $hasExistingSchedule ? 'justify-between' : 'justify-end' }} gap-3">
                @if($hasExistingSchedule)
                    <button wire:click="deleteEmailReport"
                            class="text-sm font-body text-red-600 dark:text-red-400 hover:text-red-300 transition-colors">
                        Remove schedule
                    </button>
                @endif
                <div class="flex items-center gap-3">
                    <button wire:click="$set('showEmailReportModal', false)"
                            class="px-4 py-2.5 text-sm font-medium font-body rounded-lg
                                   dark:text-slate-400 text-gray-500
                                   dark:hover:bg-slate-700 hover:bg-gray-100
                                   transition-all duration-150">
                        Cancel
                    </button>
                    <button wire:click="saveEmailReport"
                            class="inline-flex items-center gap-2 px-5 py-2.5
                                   bg-primary hover:bg-accent text-white
                                   text-sm font-semibold font-body rounded-lg
                                   shadow-lg shadow-primary/25 hover:shadow-accent/30
                                   transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                        </svg>
                        {{ $hasExistingSchedule ? 'Update Schedule' : 'Enable Reports' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ===== UPGRADE MODAL ===== --}}
    <x-upgrade-modal :show="$upgradeModalFeature !== ''" :feature="$upgradeModalFeature"
        :is-owner="auth()->user()->id === $business->owner_id"
        :business-name="$business->name" />

    {{-- ===== ATTACHMENT PREVIEW MODAL ===== --}}
    @if($showAttachmentPreview && $previewAttachmentPath)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/80 backdrop-blur-sm" wire:click="closeAttachmentPreview"></div>
            <div class="relative w-full max-w-lg max-h-[80vh] dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 overflow-hidden flex flex-col"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-3.5
                            dark:border-b dark:border-slate-700 border-b border-gray-100">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-5 h-5 flex-shrink-0 dark:text-amber-400 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                        </svg>
                        <span class="text-sm font-heading font-semibold dark:text-white text-gray-900 truncate">
                            {{ $previewAttachmentName }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        @if($previewEntryId)
                            <a href="{{ route('businesses.books.entries.attachment', [$business, $book, $previewEntryId]) }}"
                               target="_blank"
                               class="p-2 rounded-xl dark:text-slate-400 text-gray-500
                                      dark:hover:bg-slate-800 hover:bg-gray-100
                                      dark:hover:text-white hover:text-gray-700
                                      transition-all duration-150"
                               title="Open in new tab">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                </svg>
                            </a>
                        @endif
                        <button aria-label="Close preview" wire:click="closeAttachmentPreview"
                                class="p-2 rounded-lg dark:bg-slate-800 bg-gray-100
                                       dark:text-white text-gray-700
                                       dark:hover:bg-slate-700 hover:bg-gray-200
                                       transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex-1 overflow-auto p-4 flex items-center justify-center dark:bg-slate-900/50 bg-gray-50">
                    @php $previewExt = strtolower(pathinfo($previewAttachmentPath, PATHINFO_EXTENSION)); @endphp
                    @if(in_array($previewExt, ['png', 'jpg', 'jpeg']) && $previewEntryId)
                        <img src="{{ route('businesses.books.entries.attachment', [$business, $book, $previewEntryId]) }}"
                             alt="{{ $previewAttachmentName }}"
                             class="max-w-full max-h-[60vh] object-contain rounded-lg">
                    @elseif($previewExt === 'pdf')
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto dark:text-red-600/70 dark:text-red-400/60 text-red-600 dark:text-red-400 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                            </svg>
                            <p class="text-sm dark:text-slate-400 text-gray-600 font-body mb-3">PDF document</p>
                            @if($previewEntryId)
                                <a href="{{ route('businesses.books.entries.attachment', [$business, $book, $previewEntryId]) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold font-body text-white bg-primary hover:bg-accent rounded-xl transition-colors duration-150">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                    </svg>
                                    Open PDF
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ===== CUSTOM DATE MODAL ===== --}}
    @if($showCustomDateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="cancelCustomDate"></div>
            <div class="relative w-full max-w-sm dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 p-6"
                 x-data="{
                     mode: 'range',
                     fpFrom: null, fpTo: null, fpSingle: null,
                     initPickers() {
                         const dark = document.documentElement.classList.contains('dark');
                         const base = {
                             appendTo: document.body,
                             dateFormat: 'Y-m-d',
                             disableMobile: true,
                         };
                         this.fpFrom = flatpickr(this.$refs.fpFrom, { ...base,
                             defaultDate: $wire.filterCustomFrom || null,
                             onChange: (_, s) => { if (s) $wire.set('filterCustomFrom', s); }
                         });
                         this.fpTo = flatpickr(this.$refs.fpTo, { ...base,
                             defaultDate: $wire.filterCustomTo || null,
                             onChange: (_, s) => { if (s) $wire.set('filterCustomTo', s); }
                         });
                         this.fpSingle = flatpickr(this.$refs.fpSingle, { ...base,
                             defaultDate: $wire.filterCustomFrom || null,
                             onChange: (_, s) => { if (s) { $wire.set('filterCustomFrom', s); $wire.set('filterCustomTo', s); } }
                         });
                     },
                     destroyPickers() {
                         [this.fpFrom, this.fpTo, this.fpSingle].forEach(fp => fp && fp.destroy());
                     }
                 }"
                 x-init="$nextTick(() => initPickers())"
                 x-on:close-custom-date-modal.window="destroyPickers()"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-heading font-bold text-base dark:text-white text-gray-900">Custom Date</h3>
                    <button aria-label="Close" type="button" wire:click="cancelCustomDate"
                            class="p-1.5 rounded-lg dark:text-slate-500 text-gray-400
                                   dark:hover:bg-slate-800 hover:bg-gray-100 dark:hover:text-white hover:text-gray-700
                                   transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Mode tabs --}}
                <div class="flex gap-1 p-1 dark:bg-slate-900 bg-gray-100 rounded-xl mb-4">
                    <button type="button" @click="mode = 'range'"
                            :class="mode === 'range' ? 'dark:bg-slate-700 bg-white dark:text-white text-gray-900 shadow-sm' : 'dark:text-slate-500 text-gray-500'"
                            class="flex-1 py-1.5 text-xs font-semibold font-body rounded-lg transition-all duration-150">
                        Date Range
                    </button>
                    <button type="button" @click="mode = 'single'"
                            :class="mode === 'single' ? 'dark:bg-slate-700 bg-white dark:text-white text-gray-900 shadow-sm' : 'dark:text-slate-500 text-gray-500'"
                            class="flex-1 py-1.5 text-xs font-semibold font-body rounded-lg transition-all duration-150">
                        Single Day
                    </button>
                </div>

                {{-- Date range mode --}}
                <div x-show="mode === 'range'" class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">From</p>
                        <div class="relative">
                            <input x-ref="fpFrom" type="text" readonly placeholder="Select date…"
                                   class="w-full pl-3 pr-9 py-2 text-sm font-body rounded-lg cursor-pointer
                                          dark:bg-slate-800 bg-white
                                          dark:border dark:border-slate-700 border border-gray-300
                                          dark:text-white text-gray-900
                                          focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                          transition-all duration-150">
                            <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none dark:text-slate-500 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">To</p>
                        <div class="relative">
                            <input x-ref="fpTo" type="text" readonly placeholder="Select date…"
                                   class="w-full pl-3 pr-9 py-2 text-sm font-body rounded-lg cursor-pointer
                                          dark:bg-slate-800 bg-white
                                          dark:border dark:border-slate-700 border border-gray-300
                                          dark:text-white text-gray-900
                                          focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                          transition-all duration-150">
                            <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none dark:text-slate-500 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Single day mode --}}
                <div x-show="mode === 'single'">
                    <p class="text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">Date</p>
                    <div class="relative">
                        <input x-ref="fpSingle" type="text" readonly placeholder="Select date…"
                               class="w-full pl-3 pr-9 py-2 text-sm font-body rounded-lg cursor-pointer
                                      dark:bg-slate-800 bg-white
                                      dark:border dark:border-slate-700 border border-gray-300
                                      dark:text-white text-gray-900
                                      focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      transition-all duration-150">
                        <span class="absolute inset-y-0 right-3 flex items-center pointer-events-none dark:text-slate-500 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                            </svg>
                        </span>
                    </div>
                </div>

                {{-- Compare toggle --}}
                <div class="mt-5 dark:bg-slate-900 bg-gray-50 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-3.5 py-3">
                        <div>
                            <p class="text-sm font-semibold font-body dark:text-slate-200 text-gray-800">Compare with previous period</p>
                            <p class="text-xs font-body dark:text-slate-500 text-gray-400 mt-0.5">Show side-by-side % change</p>
                        </div>
                        <button aria-label="Compare with another period" type="button" wire:click="toggleComparison"
                                class="relative flex-shrink-0 w-10 h-6 rounded-full transition-colors duration-200
                                       {{ $compareEnabled ? 'bg-primary' : 'dark:bg-slate-700 bg-gray-300' }}">
                            <span class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200
                                         {{ $compareEnabled ? 'translate-x-4' : 'translate-x-0' }}"></span>
                        </button>
                    </div>

                    {{-- Mode selector — only shown when compare is on --}}
                    @if($compareEnabled)
                    <div class="px-3.5 pb-3 flex gap-2">
                        <button type="button"
                                wire:click="$set('compareMode', 'previous_period')"
                                class="flex-1 flex items-center gap-2 px-3 py-2 rounded-lg border text-xs font-semibold font-body transition-all duration-150
                                       {{ $compareMode === 'previous_period'
                                           ? 'border-primary bg-primary/10 text-primary dark:text-blue-light'
                                           : 'dark:border-slate-700 border-gray-200 dark:text-slate-400 text-gray-500 dark:hover:border-slate-600 hover:border-gray-300' }}">
                            <div class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                        {{ $compareMode === 'previous_period' ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                @if($compareMode === 'previous_period')
                                    <div class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                                @endif
                            </div>
                            Previous period
                        </button>
                        <button type="button"
                                wire:click="$set('compareMode', 'same_period_last_year')"
                                class="flex-1 flex items-center gap-2 px-3 py-2 rounded-lg border text-xs font-semibold font-body transition-all duration-150
                                       {{ $compareMode === 'same_period_last_year'
                                           ? 'border-primary bg-primary/10 text-primary dark:text-blue-light'
                                           : 'dark:border-slate-700 border-gray-200 dark:text-slate-400 text-gray-500 dark:hover:border-slate-600 hover:border-gray-300' }}">
                            <div class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                        {{ $compareMode === 'same_period_last_year' ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                @if($compareMode === 'same_period_last_year')
                                    <div class="w-1.5 h-1.5 rounded-full bg-primary"></div>
                                @endif
                            </div>
                            Same period last year
                        </button>
                    </div>
                    @endif
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" wire:click="cancelCustomDate"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200
                                   rounded-lg transition-all duration-200">
                        Cancel
                    </button>
                    <button type="button" wire:click="applyCustomDate"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-primary text-white hover:bg-accent
                                   rounded-lg transition-all duration-200">
                        Apply
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== PAGE BODY ===== --}}
    <div class="px-6 lg:px-8 py-6">
        <div class="max-w-5xl mx-auto space-y-3">

            {{-- ===== BALANCE SUMMARY STRIP ===== --}}
            @php
                $isPositive  = bccomp((string)$balance, '0', 2) >= 0;
                $currSymbol  = $business->currencySymbol();
                $openingBal  = (float)$book->opening_balance;
            @endphp

            {{-- Active filter summary — the strip reflects what's shown --}}
            @if($hasFilters)
                <div class="flex items-center gap-2 flex-wrap text-xs font-body" aria-live="polite">
                    <span class="dark:text-slate-400 text-gray-600">Showing:</span>
                    <span class="font-medium dark:text-white text-gray-900">{{ implode(' · ', $activeFilters) }}</span>
                    <button type="button" wire:click="clearFilters"
                            class="text-primary dark:text-blue-light hover:underline rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                        Clear
                    </button>
                </div>
            @endif

            <div class="dark:bg-dark bg-white rounded-xl
                        dark:border dark:border-slate-700 border border-gray-200 overflow-hidden">
                {{-- Mobile: net balance on top, in/out side by side below. sm+: three columns. --}}
                <div class="grid grid-cols-2 sm:grid-cols-3">

                    {{-- Cash In --}}
                    <div class="min-w-0 px-4 py-3 sm:px-6 sm:py-5 border-r border-gray-200 dark:border-slate-700">
                        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body">Cash In</p>
                        <x-amount :value="$totalIn" :symbol="$currSymbol" tone="in"
                                  class="block font-bold text-base sm:text-2xl leading-none mt-1.5 sm:mt-2 truncate" />
                    </div>

                    {{-- Cash Out --}}
                    <div class="min-w-0 px-4 py-3 sm:px-6 sm:py-5 sm:border-r border-gray-200 dark:border-slate-700">
                        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body">Cash Out</p>
                        <x-amount :value="$totalOut" :symbol="$currSymbol" tone="out"
                                  class="block font-bold text-base sm:text-2xl leading-none mt-1.5 sm:mt-2 truncate" />
                    </div>

                    {{-- Net Balance — centrepiece --}}
                    <div class="col-span-2 sm:col-span-1 order-first sm:order-none min-w-0 px-4 py-4 sm:px-6 sm:py-5 border-b sm:border-b-0 border-gray-200 dark:border-slate-700 {{ $isPositive ? 'dark:bg-primary/[0.04] bg-primary/[0.02]' : 'dark:bg-red-500/[0.04] bg-red-500/[0.02]' }}">
                        <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body flex items-center gap-1.5">
                            {{ $hasFilters ? 'Net (this view)' : 'Net Balance' }}
                            @if(!$isPositive)
                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded normal-case tracking-normal text-[10px] font-semibold bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181"/>
                                    </svg>
                                    Negative
                                </span>
                            @endif
                        </p>
                        <x-amount :value="$balance" :symbol="$currSymbol" tone="net"
                                  class="block font-extrabold text-2xl sm:text-3xl leading-none mt-2 truncate" />
                        @if($hasFilters)
                            <p class="text-[10px] dark:text-slate-400 text-gray-500 font-body mt-1 truncate">
                                Book balance <x-amount :value="$bookBalance" :symbol="$currSymbol" tone="net" decimals="0" />
                            </p>
                        @elseif($openingBal != 0)
                            <p class="text-[10px] dark:text-slate-400 text-gray-500 font-body mt-1 truncate">
                                incl. <x-amount :value="$openingBal" :symbol="$currSymbol" tone="plain" decimals="0" /> opening
                            </p>
                        @endif
                    </div>

                </div>
            </div>

            {{-- ===== PERIOD COMPARISON CARD (Pro, custom date range) ===== --}}
            @if($comparisonData)
            @php
                $cmpSymbol = $business->currencySymbol();
                $pctBadge = function(?float $pct, bool $invertSign = false) {
                    if ($pct === null) return ['text' => '—', 'class' => 'dark:text-slate-500 text-gray-400'];
                    $up = $pct >= 0;
                    if ($invertSign) $up = !$up; // for Cash Out: higher is worse
                    $arrow = $pct >= 0 ? '↑' : '↓';
                    $cls = $up ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                    return ['text' => $arrow . ' ' . abs($pct) . '%', 'class' => $cls];
                };
                $inBadge  = $pctBadge($comparisonData['changes']['in']);
                $outBadge = $pctBadge($comparisonData['changes']['out'], true);
                $netBadge = $pctBadge($comparisonData['changes']['net']);
            @endphp
            <div class="dark:bg-dark bg-white rounded-xl dark:border dark:border-slate-700 border border-gray-200 overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-3 dark:border-b dark:border-slate-800 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                        <span class="text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">
                            {{ $compareMode === 'same_period_last_year' ? 'vs Same Period Last Year' : 'vs Previous Period' }}
                        </span>
                    </div>
                    <button wire:click="toggleComparison"
                            class="text-xs font-body dark:text-slate-600 text-gray-400 dark:hover:text-slate-400 hover:text-gray-600 transition-colors">
                        Dismiss
                    </button>
                </div>

                {{-- Labels row --}}
                <div class="grid grid-cols-3 gap-px dark:bg-slate-800 bg-gray-100">
                    <div class="dark:bg-dark bg-white px-4 py-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body truncate">Metric</p>
                    </div>
                    <div class="dark:bg-dark bg-white px-4 py-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-primary font-body truncate">{{ $comparisonData['currentLabel'] }}</p>
                    </div>
                    <div class="dark:bg-dark bg-white px-4 py-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body truncate">{{ $comparisonData['previousLabel'] }}</p>
                    </div>
                </div>

                {{-- Cash In row --}}
                <div class="grid grid-cols-3 gap-px dark:bg-slate-800 bg-gray-100">
                    <div class="dark:bg-dark bg-white flex items-center gap-2 px-4 py-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-400 flex-shrink-0"></div>
                        <span class="text-xs font-semibold font-body dark:text-slate-300 text-gray-700">Cash In</span>
                    </div>
                    <div class="dark:bg-dark bg-white px-4 py-3 flex items-baseline gap-2">
                        <x-amount :value="$comparisonData['current']['in']" :symbol="$cmpSymbol" tone="in" decimals="0" class="text-sm font-bold truncate" />
                        <span class="text-[10px] font-semibold font-body {{ $inBadge['class'] }} flex-shrink-0">{{ $inBadge['text'] }}</span>
                    </div>
                    <div class="dark:bg-dark bg-white px-4 py-3">
                        <x-amount :value="$comparisonData['previous']['in']" :symbol="$cmpSymbol" tone="plain" decimals="0" class="text-sm dark:text-slate-400 text-gray-600 truncate" />
                    </div>
                </div>

                {{-- Cash Out row --}}
                <div class="grid grid-cols-3 gap-px dark:bg-slate-800 bg-gray-100">
                    <div class="dark:bg-dark bg-white flex items-center gap-2 px-4 py-3">
                        <div class="w-2 h-2 rounded-full bg-red-400 flex-shrink-0"></div>
                        <span class="text-xs font-semibold font-body dark:text-slate-300 text-gray-700">Cash Out</span>
                    </div>
                    <div class="dark:bg-dark bg-white px-4 py-3 flex items-baseline gap-2">
                        <x-amount :value="$comparisonData['current']['out']" :symbol="$cmpSymbol" tone="out" decimals="0" class="text-sm font-bold truncate" />
                        <span class="text-[10px] font-semibold font-body {{ $outBadge['class'] }} flex-shrink-0">{{ $outBadge['text'] }}</span>
                    </div>
                    <div class="dark:bg-dark bg-white px-4 py-3">
                        <x-amount :value="$comparisonData['previous']['out']" :symbol="$cmpSymbol" tone="plain" decimals="0" class="text-sm dark:text-slate-400 text-gray-600 truncate" />
                    </div>
                </div>

                {{-- Net row --}}
                @php
                    $currNetPos = $comparisonData['current']['net'] >= 0;
                    $prevNetPos = $comparisonData['previous']['net'] >= 0;
                @endphp
                <div class="grid grid-cols-3 gap-px dark:bg-slate-800 bg-gray-100">
                    <div class="dark:bg-dark bg-white flex items-center gap-2 px-4 py-3 rounded-bl-xl">
                        <div class="w-2 h-2 rounded-full {{ $currNetPos ? 'bg-primary' : 'bg-red-400' }} flex-shrink-0"></div>
                        <span class="text-xs font-semibold font-body dark:text-slate-300 text-gray-700">Net</span>
                    </div>
                    <div class="dark:bg-dark bg-white px-4 py-3 flex items-baseline gap-2">
                        <x-amount :value="$comparisonData['current']['net']" :symbol="$cmpSymbol" tone="net" decimals="0" class="text-sm font-bold truncate" />
                        <span class="text-[10px] font-semibold font-body {{ $netBadge['class'] }} flex-shrink-0">{{ $netBadge['text'] }}</span>
                    </div>
                    <div class="dark:bg-dark bg-white px-4 py-3 rounded-br-xl">
                        <x-amount :value="$comparisonData['previous']['net']" :symbol="$cmpSymbol" tone="neutral" decimals="0" class="text-sm truncate" />
                    </div>
                </div>
            </div>
            @endif

            {{-- ===== VIEW TABS ===== --}}
            <div class="overflow-x-auto">
            <div class="flex items-center gap-1 dark:bg-slate-800/60 bg-gray-100 rounded-xl p-1 w-max">
                <button wire:click="$set('activeTab', 'entries')"
                        class="px-4 py-2 rounded-lg text-sm font-semibold font-body transition-all duration-150
                               {{ $activeTab === 'entries'
                                   ? 'dark:bg-dark bg-white dark:text-white text-gray-900 shadow-sm'
                                   : 'dark:text-slate-400 text-gray-500 hover:dark:text-white hover:text-gray-900' }}">
                    Entries
                </button>
                <button wire:click="$set('activeTab', 'activity')"
                        class="px-4 py-2 rounded-lg text-sm font-semibold font-body transition-all duration-150
                               {{ $activeTab === 'activity'
                                   ? 'dark:bg-dark bg-white dark:text-white text-gray-900 shadow-sm'
                                   : 'dark:text-slate-400 text-gray-500 dark:hover:text-white hover:text-gray-900' }}">
                    Activity
                </button>
                <button wire:click="$set('activeTab', 'reports')"
                        class="px-4 py-2 rounded-lg text-sm font-semibold font-body transition-all duration-150 flex items-center gap-1.5
                               {{ $activeTab === 'reports'
                                   ? 'dark:bg-dark bg-white dark:text-white text-gray-900 shadow-sm'
                                   : 'dark:text-slate-400 text-gray-500 hover:dark:text-white hover:text-gray-900' }}">
                    Reports
                    @if(!$business->isPro())<x-pro-badge />@endif
                </button>
                <button wire:click="$set('activeTab', 'recurring')"
                        class="px-4 py-2 rounded-lg text-sm font-semibold font-body transition-all duration-150 flex items-center gap-1.5
                               {{ $activeTab === 'recurring'
                                   ? 'dark:bg-dark bg-white dark:text-white text-gray-900 shadow-sm'
                                   : 'dark:text-slate-400 text-gray-500 dark:hover:text-white hover:text-gray-900' }}">
                    Recurring
                    @if(!$business->isPro())<x-pro-badge />@endif
                </button>
            </div>
            </div>{{-- end overflow-x-auto tab scroller --}}
            {{-- ===== SEARCH + FILTERS (entries + reports) ===== --}}
            @if(in_array($activeTab, ['entries', 'reports'], true))
            <div class="space-y-2">
                <div class="relative">
                    <label for="ledger-search" class="sr-only">Search entries</label>
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                        <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                    </div>
                    <input type="search" id="ledger-search"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search by description, category, amount…"
                           class="w-full pl-10 pr-4 py-2.5 text-sm font-body
                                  dark:bg-dark bg-white
                                  dark:border dark:border-slate-700 border border-gray-200
                                  dark:text-white text-gray-900 rounded-lg
                                  placeholder:dark:text-slate-500 placeholder:text-gray-400
                                  focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                  transition-all duration-150">
                </div>
            {{-- ===== FILTER BAR ===== --}}
            <div class="flex items-center gap-2 flex-wrap">

                {{-- Type segmented control --}}
                <div class="flex items-center dark:bg-slate-800/60 bg-gray-100 rounded-lg p-0.5">
                    @foreach(['all' => 'All', 'in' => 'Cash In', 'out' => 'Cash Out'] as $val => $label)
                        <button wire:click="$set('filterType', '{{ $val }}')" type="button"
                                class="px-3 py-1.5 rounded-md text-xs font-semibold font-body transition-all duration-150
                                       {{ $filterType === $val
                                           ? 'dark:bg-dark bg-white dark:text-white text-gray-900 shadow-sm'
                                           : 'dark:text-slate-400 text-gray-500 dark:hover:text-white hover:text-gray-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Duration filter --}}
                @php
                    $durationLabel = match($filterDuration) {
                        'today'        => 'Today',
                        'yesterday'    => 'Yesterday',
                        'last_7_days'  => 'Last 7 Days',
                        'last_30_days' => 'Last 30 Days',
                        'custom'       => 'Custom',
                        default        => 'All Time',
                    };
                @endphp
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" type="button"
                            class="flex items-center gap-1.5 px-3 py-1.5
                                   dark:bg-dark bg-white
                                   dark:border dark:border-slate-700 border border-gray-200
                                   dark:text-slate-300 text-gray-700
                                   text-xs font-semibold font-body rounded-lg
                                   hover:dark:border-slate-600 hover:border-gray-300
                                   transition-all duration-150">
                        <svg class="w-3 h-3 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                        </svg>
                        <span>Duration: <span class="{{ $filterDuration !== 'all_time' ? 'text-primary' : '' }}">{{ $durationLabel }}</span></span>
                        <svg class="w-3 h-3 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="absolute top-full left-0 mt-1 w-48 z-20
                                dark:bg-slate-800 bg-white
                                dark:border dark:border-slate-700 border border-gray-200
                                rounded-xl shadow-xl shadow-black/20 overflow-hidden"
                         style="display:none;">
                        @foreach(['all_time' => 'All Time', 'today' => 'Today', 'yesterday' => 'Yesterday', 'last_7_days' => 'Last 7 Days', 'last_30_days' => 'Last 30 Days'] as $val => $label)
                            <button @click="$wire.set('filterDuration', '{{ $val }}'); open = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                           dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors
                                           {{ $filterDuration === $val ? 'dark:text-white text-gray-900 font-semibold' : 'dark:text-slate-400 text-gray-600' }}">
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                            {{ $filterDuration === $val ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                    @if($filterDuration === $val)
                                        <div class="w-2 h-2 rounded-full bg-primary"></div>
                                    @endif
                                </div>
                                {{ $label }}
                            </button>
                        @endforeach
                        <div class="dark:border-t dark:border-slate-700 border-t border-gray-100 my-0.5"></div>
                        <button @click="$wire.openCustomDateModal(); open = false"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors
                                       {{ $filterDuration === 'custom' ? 'dark:text-white text-gray-900 font-semibold' : 'dark:text-slate-400 text-gray-600' }}">
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                        {{ $filterDuration === 'custom' ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                @if($filterDuration === 'custom')
                                    <div class="w-2 h-2 rounded-full bg-primary"></div>
                                @endif
                            </div>
                            Custom…
                            @if(!$business->isPro())
                                <x-pro-badge class="ml-auto" />
                            @endif
                        </button>
                    </div>
                </div>

                {{-- Category filter --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" type="button"
                            class="flex items-center gap-1.5 px-3 py-1.5
                                   dark:bg-dark bg-white
                                   dark:border dark:border-slate-700 border border-gray-200
                                   dark:text-slate-300 text-gray-700
                                   text-xs font-semibold font-body rounded-lg
                                   hover:dark:border-slate-600 hover:border-gray-300
                                   transition-all duration-150">
                        <span>Category:
                            <span class="{{ count($filterCategories) > 0 ? 'text-primary' : '' }}">
                                {{ count($filterCategories) > 0 ? count($filterCategories) . ' selected' : 'All' }}
                            </span>
                        </span>
                        <svg class="w-3 h-3 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="absolute top-full left-0 mt-1 w-56 z-20 max-h-60 overflow-y-auto
                                dark:bg-slate-800 bg-white
                                dark:border dark:border-slate-700 border border-gray-200
                                rounded-xl shadow-xl shadow-black/20"
                         style="display:none;">
                        @forelse($categories as $cat)
                            <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer
                                          dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                <input type="checkbox"
                                       wire:model.live="filterCategories"
                                       value="{{ $cat->name }}"
                                       class="w-3.5 h-3.5 rounded accent-primary flex-shrink-0">
                                <span class="text-sm font-body dark:text-slate-300 text-gray-700 truncate">{{ $cat->name }}</span>
                            </label>
                        @empty
                            <div class="px-4 py-3 text-xs dark:text-slate-500 text-gray-400 font-body text-center">
                                No categories yet
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Payment mode filter --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" type="button"
                            class="flex items-center gap-1.5 px-3 py-1.5
                                   dark:bg-dark bg-white
                                   dark:border dark:border-slate-700 border border-gray-200
                                   dark:text-slate-300 text-gray-700
                                   text-xs font-semibold font-body rounded-lg
                                   hover:dark:border-slate-600 hover:border-gray-300
                                   transition-all duration-150">
                        <span>Payment method:
                            <span class="{{ count($filterPaymentModes) > 0 ? 'text-primary' : '' }}">
                                {{ count($filterPaymentModes) > 0 ? count($filterPaymentModes) . ' selected' : 'All' }}
                            </span>
                        </span>
                        <svg class="w-3 h-3 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="absolute top-full left-0 mt-1 w-56 z-20 max-h-60 overflow-y-auto
                                dark:bg-slate-800 bg-white
                                dark:border dark:border-slate-700 border border-gray-200
                                rounded-xl shadow-xl shadow-black/20"
                         style="display:none;">
                        @forelse($paymentModes as $mode)
                            <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer
                                          dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                <input type="checkbox"
                                       wire:model.live="filterPaymentModes"
                                       value="{{ $mode->name }}"
                                       class="w-3.5 h-3.5 rounded accent-primary flex-shrink-0">
                                <span class="text-sm font-body dark:text-slate-300 text-gray-700 truncate">{{ $mode->name }}</span>
                            </label>
                        @empty
                            <div class="px-4 py-3 text-xs dark:text-slate-500 text-gray-400 font-body text-center">
                                No payment methods yet
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Clear filters (shown only when any filter is active) --}}
                @if($filterType !== 'all' || $filterDuration !== 'all_time' || count($filterCategories) > 0 || count($filterPaymentModes) > 0 || $compareEnabled)
                    <button wire:click="clearFilters"
                            class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-body rounded-lg
                                   dark:text-slate-500 text-gray-400
                                   dark:hover:text-white hover:text-gray-700
                                   dark:hover:bg-slate-800 hover:bg-gray-100
                                   transition-all duration-150">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                        Clear
                    </button>
                @endif

            </div>


            </div>
            @endif

            @if($userRole !== 'viewer')
                {{-- Mobile bulk toolbar --}}
                <div x-show="hasSelection"
                     x-cloak
                     x-transition
                     class="md:hidden fixed bottom-0 left-0 right-0 z-40
                            dark:bg-dark bg-white
                            dark:border-t dark:border-slate-700 border-t border-gray-200
                            px-4 py-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))]">
                    <div class="flex items-center gap-2">
                        <span class="text-xs dark:text-slate-400 text-gray-600 font-body font-semibold whitespace-nowrap"
                              x-text="selectedIds.length + ' selected'"></span>
                        <div class="flex-1 flex gap-1.5 overflow-x-auto">
                            <button @click="$wire.set('showBulkDeleteConfirm', true)"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold font-body
                                           text-red-600 dark:text-red-400 bg-red-500/10 rounded-lg whitespace-nowrap">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                                Delete
                            </button>
                            <button @click="$wire.openBulkBookPicker('move')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold font-body
                                           dark:text-slate-300 text-gray-700 dark:bg-slate-800 bg-gray-100 rounded-lg whitespace-nowrap">
                                Move
                            </button>
                            <button @click="$wire.openBulkBookPicker('copy')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold font-body
                                           dark:text-slate-300 text-gray-700 dark:bg-slate-800 bg-gray-100 rounded-lg whitespace-nowrap">
                                Copy
                            </button>
                            <button @click="$wire.openBulkBookPicker('copy_opposite')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold font-body
                                           dark:text-slate-300 text-gray-700 dark:bg-slate-800 bg-gray-100 rounded-lg whitespace-nowrap">
                                Flip Type
                            </button>
                            <button @click="$wire.set('showBulkChangeCategory', true)"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold font-body
                                           dark:text-slate-300 text-gray-700 dark:bg-slate-800 bg-gray-100 rounded-lg whitespace-nowrap">
                                Category
                            </button>
                            <button @click="$wire.set('showBulkChangePaymentMode', true)"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold font-body
                                           dark:text-slate-300 text-gray-700 dark:bg-slate-800 bg-gray-100 rounded-lg whitespace-nowrap">
                                Payment method
                            </button>
                        </div>
                        <button aria-label="Clear selection" @click="clearSelection()"
                                class="p-1.5 dark:text-slate-500 text-gray-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif


            @if($activeTab === 'entries')

            {{-- ===== BULK ACTIONS TOOLBAR ===== --}}
            @if($userRole !== 'viewer')
                <div x-show="hasSelection"
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="hidden md:flex items-center gap-1 px-4 py-2.5
                            dark:bg-dark bg-white rounded-xl
                            dark:border dark:border-slate-700 border border-gray-200">

                    {{-- Select all + count --}}
                    <label class="flex items-center gap-2.5 cursor-pointer mr-2">
                        <input type="checkbox"
                               x-ref="toolbarSelectAll"
                               :checked="selectAll"
                               @change="toggleSelectAll()"
                               class="w-3.5 h-3.5 rounded border-slate-600 text-primary focus:ring-primary/40 dark:bg-slate-800 bg-white">
                        <span class="text-sm font-semibold dark:text-white text-gray-900 font-body whitespace-nowrap"
                              x-text="selectedIds.length + ' selected'"></span>
                        <span class="text-xs dark:text-slate-400 text-gray-500 font-body whitespace-nowrap">of {{ $entries->count() }} shown</span>
                    </label>

                    <div class="w-px h-6 dark:bg-slate-700 bg-gray-200 mx-1"></div>

                    {{-- Delete --}}
                    <button @click="$wire.set('showBulkDeleteConfirm', true)"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold font-body
                                   text-red-600 dark:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                        </svg>
                        Delete
                    </button>

                    <div class="w-px h-6 dark:bg-slate-700 bg-gray-200 mx-1"></div>

                    {{-- Move or Copy dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-800 hover:bg-gray-100 rounded-lg transition-colors duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                            </svg>
                            Move or Copy
                            <svg class="w-3 h-3 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition
                             class="absolute left-0 top-full mt-1 w-52 py-1.5
                                    dark:bg-[#1e293b] bg-white dark:border-slate-700 border border-gray-200
                                    rounded-xl shadow-2xl z-20"
                             style="display:none;">
                            <button @click="$wire.openBulkBookPicker('move'); open = false"
                                    class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm font-body
                                           dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                                </svg>
                                Move Entries
                            </button>
                            <button @click="$wire.openBulkBookPicker('copy'); open = false"
                                    class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm font-body
                                           dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
                                </svg>
                                Copy Entries
                            </button>
                            <button @click="$wire.openBulkBookPicker('copy_opposite'); open = false"
                                    class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm font-body
                                           dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                Copy Opposite Entries
                            </button>
                        </div>
                    </div>

                    <div class="w-px h-6 dark:bg-slate-700 bg-gray-200 mx-1"></div>

                    {{-- Change Fields dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-800 hover:bg-gray-100 rounded-lg transition-colors duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75M10.5 18a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 18H7.5m-3-6h9.75m0 0a1.5 1.5 0 0 1 3 0m-3 0a1.5 1.5 0 0 0 3 0m0 0h3.75"/>
                            </svg>
                            Change Fields
                            <svg class="w-3 h-3 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition
                             class="absolute left-0 top-full mt-1 w-56 py-1.5
                                    dark:bg-[#1e293b] bg-white dark:border-slate-700 border border-gray-200
                                    rounded-xl shadow-2xl z-20"
                             style="display:none;">
                            <button @click="$wire.set('showBulkChangeCategory', true); open = false"
                                    class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm font-body
                                           dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                                </svg>
                                Change Category
                            </button>
                            <button @click="$wire.set('showBulkChangePaymentMode', true); open = false"
                                    class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm font-body
                                           dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                                </svg>
                                Change Payment Method
                            </button>
                        </div>
                    </div>

                    <div class="flex-1"></div>

                    {{-- Clear selection --}}
                    <button aria-label="Clear selection" @click="clearSelection()"
                            class="p-1.5 rounded-lg dark:text-slate-500 text-gray-400
                                   dark:hover:bg-slate-800 hover:bg-gray-100
                                   dark:hover:text-white hover:text-gray-700 transition-colors duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            {{-- ===== ENTRIES TABLE / EMPTY STATE ===== --}}
            @if($entries->isEmpty() && !$hasFilters)

                <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-dashed border-gray-200
                            rounded-xl px-8 py-20 text-center">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <h2 class="font-heading font-bold text-base dark:text-white text-gray-900 mb-1.5">No entries yet</h2>
                    <p class="text-sm dark:text-slate-400 text-gray-500 font-body mb-6 max-w-xs mx-auto">
                        Record your first transaction to start tracking this book's balance.
                    </p>
                    @if($userRole !== 'viewer')
                        <div class="flex items-center justify-center gap-3">
                            <button type="button" wire:click="openAddEntry('in')" x-on:click="$dispatch('open-entry-panel')"
                                    class="flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white hover:brightness-110 hover:shadow-md
                                           text-sm font-semibold font-body rounded-lg transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Cash In
                            </button>
                            <button type="button" wire:click="openAddEntry('out')" x-on:click="$dispatch('open-entry-panel')"
                                    class="flex items-center gap-2 px-4 py-2.5 bg-red-600 text-white hover:brightness-110 hover:shadow-md
                                           text-sm font-semibold font-body rounded-lg transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                                Cash Out
                            </button>
                        </div>
                    @endif
                </div>

            @else

                @php
                    $filteredIds = $entries->pluck('id')->values()->toJson();
                    $gridCols = $userRole !== 'viewer'
                        ? 'md:grid-cols-[36px_110px_1fr_120px_110px_140px_130px_64px]'
                        : 'md:grid-cols-[110px_1fr_120px_110px_140px_130px_64px]';
                @endphp
                <div class="dark:bg-dark bg-white rounded-xl
                            dark:border dark:border-slate-700 border border-gray-200 overflow-hidden"
                     x-init="filteredIds = {{ $filteredIds }}; selectedIds = selectedIds.filter(id => filteredIds.includes(id)); syncSelectAll();">

                    {{-- Column headers --}}
                    <div class="hidden md:grid {{ $gridCols }} px-5 py-3
                                border-b border-gray-100 dark:border-slate-700
                                dark:bg-navy/50 bg-gray-50/70" role="row">
                        @if($userRole !== 'viewer')
                            <label class="flex items-center justify-center cursor-pointer" title="Select all rows shown">
                                <span class="sr-only">Select all {{ $entries->count() }} rows shown</span>
                                <input type="checkbox"
                                       x-ref="headerSelectAll"
                                       :checked="selectAll"
                                       @change="toggleSelectAll()"
                                       x-effect="if ($refs.headerSelectAll) $refs.headerSelectAll.indeterminate = selectedIds.length > 0 && selectedIds.length < filteredIds.length"
                                       class="w-3.5 h-3.5 rounded border-slate-600 text-primary focus:ring-primary/40 dark:bg-slate-800 bg-white">
                            </label>
                        @endif
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body">Date</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body">Description</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body">Category</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body">Payment</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body text-right pr-4">Amount</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-400 text-gray-500 font-body text-right pr-2">Balance</span>
                        <span class="sr-only">Actions</span>
                    </div>

                    <div class="divide-y dark:divide-slate-800 divide-gray-100">
                        @forelse($entries as $entry)
                            @php
                                $rb       = $entry->running_balance ?? '0.00';
                                $rbPos    = bccomp((string)$rb, '0', 2) >= 0;
                                $label    = $entry->displayLabel();
                                $hasDesc  = trim((string) $entry->description) !== '';
                                $stagger  = $loop->index < 10;
                                $byLabel  = $entry->creator ? ($entry->created_by === auth()->id() ? 'You' : $entry->creator->name) : null;
                            @endphp
                            <div wire:key="{{ $entry->id }}"
                                 @if($stagger)
                                     x-data="{ shown: false }"
                                     x-init="setTimeout(() => shown = true, {{ $loop->index * 30 }})"
                                     :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-1'"
                                 @endif
                                 class="group relative transition-all duration-300
                                        before:absolute before:inset-y-0 before:left-0 before:w-0.5 before:bg-transparent
                                        hover:before:bg-primary focus-within:before:bg-primary
                                        dark:hover:bg-slate-800/30 hover:bg-gray-50/80">

                                {{-- Desktop --}}
                                <div class="hidden md:grid {{ $gridCols }} items-center px-5 py-3.5"
                                     :class="isSelected('{{ $entry->id }}') ? 'dark:!bg-primary/5 !bg-blue-50/50' : ''">
                                    @if($userRole !== 'viewer')
                                        <label class="flex items-center justify-center cursor-pointer" @click.stop>
                                            <span class="sr-only">Select {{ $label }}</span>
                                            <input type="checkbox"
                                                   :checked="isSelected('{{ $entry->id }}')"
                                                   @change="toggleEntry('{{ $entry->id }}')"
                                                   class="w-3.5 h-3.5 rounded border-slate-600 text-primary focus:ring-primary/40 dark:bg-slate-800 bg-white">
                                        </label>
                                    @endif

                                    <div class="flex flex-col leading-none">
                                        <span class="text-xs font-semibold font-body dark:text-slate-300 text-gray-700">{{ $entry->date->format('d M') }}</span>
                                        <span class="text-[10px] font-body dark:text-slate-400 text-gray-500 mt-0.5">{{ $entry->date->format('Y') }}</span>
                                    </div>

                                    <div class="min-w-0 pr-3">
                                        <p class="text-sm font-medium font-body truncate flex items-center gap-1.5
                                                  {{ $hasDesc ? 'dark:text-white text-gray-900' : 'dark:text-slate-300 text-gray-600' }}">
                                            <span class="truncate">{{ $label }}</span>
                                            @include('livewire.book.partials.entry-row-icons', ['entry' => $entry, 'label' => $label])
                                        </p>
                                        @if($entry->reference)
                                            <p class="text-[11px] font-mono dark:text-slate-400 text-gray-500 mt-0.5 truncate">{{ $entry->reference }}</p>
                                        @endif
                                        @if($byLabel)
                                            <p class="text-[11px] font-body dark:text-slate-400 text-gray-500 mt-0.5 truncate">by {{ $byLabel }}</p>
                                        @endif
                                    </div>

                                    <span class="text-xs dark:text-slate-400 text-gray-600 font-body truncate pr-2">{{ $entry->category ?: '—' }}</span>
                                    <span class="text-xs dark:text-slate-400 text-gray-600 font-body truncate pr-2">{{ $entry->payment_mode ?: '—' }}</span>

                                    <div class="text-right pr-4">
                                        <x-amount :value="$entry->amount" :symbol="$currSymbol" :type="$entry->type" class="text-base font-bold" />
                                    </div>

                                    <div class="text-right pr-2">
                                        <x-amount :value="$rb" :symbol="$currSymbol" tone="neutral" class="text-sm font-medium" />
                                    </div>

                                    <div class="flex items-center justify-end gap-0.5 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-150">
                                        @if($userRole !== 'viewer')
                                            <button type="button" @click.stop="$wire.openEditEntry('{{ $entry->id }}')"
                                                    aria-label="Edit {{ $label }}" title="Edit"
                                                    class="p-2 rounded-lg dark:text-slate-400 text-gray-500
                                                           dark:hover:bg-slate-700 hover:bg-gray-200
                                                           dark:hover:text-white hover:text-gray-700 transition-colors duration-150">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                                </svg>
                                            </button>
                                            <button type="button" @click.stop="$wire.confirmDeleteEntry('{{ $entry->id }}')"
                                                    aria-label="Delete {{ $label }}" title="Delete"
                                                    class="p-2 rounded-lg dark:text-slate-400 text-gray-500
                                                           dark:hover:bg-red-500/10 hover:bg-red-50
                                                           dark:hover:text-red-400 hover:text-red-600 transition-colors duration-150">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Mobile --}}
                                <div class="md:hidden flex items-center gap-3 px-4 py-3.5"
                                     :class="isSelected('{{ $entry->id }}') ? 'dark:!bg-primary/5 !bg-blue-50/50' : ''">
                                    @if($userRole !== 'viewer')
                                        <label class="flex items-center cursor-pointer flex-shrink-0" @click.stop>
                                            <span class="sr-only">Select {{ $label }}</span>
                                            <input type="checkbox"
                                                   :checked="isSelected('{{ $entry->id }}')"
                                                   @change="toggleEntry('{{ $entry->id }}')"
                                                   class="w-4 h-4 rounded border-slate-600 text-primary focus:ring-primary/40 dark:bg-slate-800 bg-white">
                                        </label>
                                    @endif
                                    <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $entry->type === 'in' ? 'bg-emerald-500' : 'bg-red-500' }}" aria-hidden="true"></div>
                                    @php $rowTag = $userRole !== 'viewer' ? 'button' : 'div'; @endphp
                                    <{{ $rowTag }} @if($userRole !== 'viewer') type="button" wire:click="openEditEntry('{{ $entry->id }}')" aria-label="Edit {{ $label }}" @endif
                                            class="flex-1 min-w-0 text-left rounded-lg block">
                                        <span class="text-sm font-medium font-body truncate flex items-center gap-1.5
                                                     {{ $hasDesc ? 'dark:text-white text-gray-900' : 'dark:text-slate-300 text-gray-600' }}">
                                            <span class="truncate">{{ $label }}</span>
                                        </span>
                                        <span class="flex items-center gap-2 mt-0.5 flex-wrap text-xs dark:text-slate-400 text-gray-500 font-body">
                                            <span>{{ $entry->date->format('d M Y') }}</span>
                                            @if($byLabel)<span>· by {{ $byLabel }}</span>@endif
                                            @if($entry->category && $hasDesc)<span>· {{ $entry->category }}</span>@endif
                                        </span>
                                    </{{ $rowTag }}>
                                    <div class="text-right flex-shrink-0 flex flex-col items-end gap-1">
                                        <x-amount :value="$entry->amount" :symbol="$currSymbol" :type="$entry->type" class="text-sm font-semibold" />
                                        <x-amount :value="$rb" :symbol="$currSymbol" tone="neutral" class="text-xs" />
                                        <div class="flex items-center gap-1.5">
                                            @include('livewire.book.partials.entry-row-icons', ['entry' => $entry, 'label' => $label, 'mobile' => true])
                                        </div>
                                    </div>
                                </div>

                            </div>
                        @empty
                            <div class="px-6 py-14 text-center">
                                <p class="text-sm dark:text-slate-400 text-gray-500 font-body">No entries match your current filters.</p>
                                <button type="button" wire:click="clearFilters" class="mt-2 text-sm text-primary dark:text-blue-light hover:underline">Clear filters</button>
                            </div>
                        @endforelse
                    </div>

                    @if($entries->isNotEmpty())
                        <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-800
                                    dark:bg-navy/30 bg-gray-50/50
                                    flex items-center justify-between gap-3 flex-wrap">
                            <span class="text-xs dark:text-slate-400 text-gray-500 font-body">
                                Showing {{ number_format($entries->count()) }} of {{ number_format($filteredCount) }} {{ Str::plural('entry', $filteredCount) }}
                                @if($userRole !== 'viewer' && $hasMoreEntries)
                                    <span class="hidden sm:inline">· select all picks the rows shown</span>
                                @endif
                            </span>
                            @if($hasMoreEntries)
                                <button type="button" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold font-body
                                               dark:bg-slate-800 bg-white border border-gray-200 dark:border-slate-700
                                               dark:text-slate-200 text-gray-700 hover:brightness-110 hover:shadow-sm transition-all">
                                    <span wire:loading.remove wire:target="loadMore">Load {{ min(\App\Livewire\Book\Show::LEDGER_PAGE_SIZE, $filteredCount - $entries->count()) }} more</span>
                                    <span wire:loading wire:target="loadMore">Loading…</span>
                                </button>
                            @endif
                        </div>
                    @endif

                </div>

            @endif

            @elseif($activeTab === 'reports')
            {{-- ===== REPORTS TAB ===== --}}

            @if($business->isPro())

                @php
                    $rCurr        = $business->currencySymbol();
                    $rSummary     = $reportData['periodSummary']     ?? [];
                    $rTrend       = $reportData['trendChart']        ?? [];
                    $rCategories  = $reportData['categoryBreakdown'] ?? [];
                    $rDays        = $rSummary['daySpan'] ?? 1;
                @endphp

                <div class="space-y-4">

                    {{-- ===== 1. PERIOD SUMMARY ===== --}}
                    <section aria-labelledby="report-summary-heading"
                             class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-5">
                        <div class="flex items-baseline justify-between gap-3 mb-4">
                            <h3 id="report-summary-heading" class="font-heading font-bold text-base dark:text-white text-gray-900">At a glance</h3>
                            <p class="text-xs dark:text-slate-400 text-gray-500 font-body">
                                {{ ($rSummary['inCount'] ?? 0) + ($rSummary['outCount'] ?? 0) }} {{ Str::plural('entry', ($rSummary['inCount'] ?? 0) + ($rSummary['outCount'] ?? 0)) }}
                                over {{ $rDays }} {{ Str::plural('day', $rDays) }}
                            </p>
                        </div>
                        <dl class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <dt class="text-xs font-body dark:text-slate-400 text-gray-500">Money in</dt>
                                <dd><x-amount :value="$rSummary['totalIn'] ?? 0" :symbol="$rCurr" tone="in" class="text-xl font-bold" /></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-body dark:text-slate-400 text-gray-500">Money out</dt>
                                <dd><x-amount :value="$rSummary['totalOut'] ?? 0" :symbol="$rCurr" tone="out" class="text-xl font-bold" /></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-body dark:text-slate-400 text-gray-500">Left over (in − out)</dt>
                                <dd><x-amount :value="$rSummary['netBalance'] ?? 0" :symbol="$rCurr" tone="net" class="text-xl font-bold" /></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-body dark:text-slate-400 text-gray-500">Average per day</dt>
                                <dd><x-amount :value="$rSummary['dailyAverage'] ?? 0" :symbol="$rCurr" tone="neutral" :sign="true" class="text-xl font-bold" /></dd>
                            </div>
                        </dl>
                    </section>

                    {{-- ===== 2. CASH IN VS OUT OVER TIME ===== --}}
                    <section aria-labelledby="report-trend-heading"
                             class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-5">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 id="report-trend-heading" class="font-heading font-bold text-base dark:text-white text-gray-900">Money in vs money out</h3>
                                <p class="text-xs dark:text-slate-400 text-gray-500 font-body mt-0.5">How much came in and went out over time</p>
                            </div>
                            <div class="flex items-center gap-3 text-xs font-body flex-shrink-0">
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span><span class="dark:text-slate-300 text-gray-600">In</span></span>
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-red-500"></span><span class="dark:text-slate-300 text-gray-600">Out</span></span>
                            </div>
                        </div>
                        @if(count($rTrend) >= 2)
                            @php $maxTrend = max(1, max(collect($rTrend)->max('in'), collect($rTrend)->max('out'))); @endphp
                            <div class="flex items-end gap-0.5 sm:gap-1 h-36 sm:h-52" role="img"
                                 aria-label="Bar chart of money in and money out from {{ $rTrend[0]['label'] }} to {{ $rTrend[count($rTrend) - 1]['label'] }}">
                                @foreach($rTrend as $period)
                                    @php
                                        $inPct  = ($period['in']  / $maxTrend) * 100;
                                        $outPct = ($period['out'] / $maxTrend) * 100;
                                        $pNet   = $period['in'] - $period['out'];
                                    @endphp
                                    <div class="flex-1 flex items-end gap-px h-full group relative">
                                        <div class="flex-1 rounded-t bg-emerald-500/60 group-hover:bg-emerald-500 transition-colors duration-150"
                                             style="height: {{ max(1, $inPct) }}%"></div>
                                        <div class="flex-1 rounded-t bg-red-500/60 group-hover:bg-red-500 transition-colors duration-150"
                                             style="height: {{ max(1, $outPct) }}%"></div>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-2 rounded-lg
                                                    bg-gray-900 text-white text-[11px] font-body whitespace-nowrap
                                                    opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-10 shadow-xl">
                                            <p class="font-semibold text-slate-300 mb-1">{{ $period['label'] }}</p>
                                            <p class="font-mono text-emerald-400">In {{ $rCurr }}{{ number_format($period['in'], 2) }}</p>
                                            <p class="font-mono text-red-400">Out {{ $rCurr }}{{ number_format($period['out'], 2) }}</p>
                                            <p class="font-mono border-t border-slate-700 mt-1 pt-1 {{ $pNet >= 0 ? 'text-blue-300' : 'text-red-600 dark:text-red-400' }}">
                                                Left {{ $pNet >= 0 ? '+' : '−' }}{{ $rCurr }}{{ number_format(abs($pNet), 2) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex justify-between mt-2 text-[10px] dark:text-slate-400 text-gray-500 font-body">
                                <span>{{ $rTrend[0]['label'] }}</span>
                                @if(count($rTrend) > 2)
                                    <span class="hidden sm:inline">{{ $rTrend[(int)(count($rTrend) / 2)]['label'] }}</span>
                                @endif
                                <span>{{ $rTrend[count($rTrend) - 1]['label'] }}</span>
                            </div>
                        @else
                            <p class="text-sm dark:text-slate-400 text-gray-500 font-body py-8 text-center">Add a few more entries to see this chart.</p>
                        @endif
                    </section>

                    {{-- ===== 3. SPENDING BY CATEGORY ===== --}}
                    <section aria-labelledby="report-category-heading"
                             class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-5"
                             x-data="{ catView: 'out' }">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 id="report-category-heading" class="font-heading font-bold text-base dark:text-white text-gray-900"
                                    x-text="catView === 'out' ? 'Where your money went' : 'Where your money came from'">Where your money went</h3>
                                <p class="text-xs dark:text-slate-400 text-gray-500 font-body mt-0.5">Totals by category</p>
                            </div>
                            <div class="flex items-center gap-0.5 dark:bg-slate-800 bg-gray-100 rounded-lg p-0.5" role="group" aria-label="Show money in or money out">
                                <button type="button" @click="catView = 'out'" :aria-pressed="(catView === 'out').toString()"
                                        :class="catView === 'out' ? 'dark:bg-slate-700 bg-white shadow-sm dark:text-white text-gray-900' : 'dark:text-slate-400 text-gray-500'"
                                        class="px-2.5 py-1 rounded-md text-xs font-semibold font-body transition-all duration-150">Out</button>
                                <button type="button" @click="catView = 'in'" :aria-pressed="(catView === 'in').toString()"
                                        :class="catView === 'in' ? 'dark:bg-slate-700 bg-white shadow-sm dark:text-white text-gray-900' : 'dark:text-slate-400 text-gray-500'"
                                        class="px-2.5 py-1 rounded-md text-xs font-semibold font-body transition-all duration-150">In</button>
                            </div>
                        </div>

                        @foreach(['out' => 'bg-red-500', 'in' => 'bg-emerald-500'] as $catType => $barColour)
                            <div x-show="catView === '{{ $catType }}'" @if($catType === 'in') x-cloak @endif>
                                @if(!empty($rCategories[$catType]))
                                    <ul class="space-y-3">
                                        @foreach($rCategories[$catType] as $cat)
                                            <li>
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-sm font-body dark:text-slate-300 text-gray-700 truncate mr-3">{{ $cat['name'] }}</span>
                                                    <span class="flex items-center gap-2 flex-shrink-0">
                                                        <span class="text-xs font-body dark:text-slate-400 text-gray-500">{{ $cat['pct'] }}%</span>
                                                        <x-amount :value="$cat['total']" :symbol="$rCurr" tone="plain" class="text-sm font-medium dark:text-slate-200 text-gray-800" />
                                                    </span>
                                                </div>
                                                <div class="h-1.5 dark:bg-slate-800 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full {{ $barColour }}" style="width: {{ max(2, $cat['barPct']) }}%"></div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm dark:text-slate-400 text-gray-500 font-body py-6 text-center">
                                        No {{ $catType === 'out' ? 'money out' : 'money in' }} in this period yet.
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </section>

                    {{-- ===== 4. AI INSIGHTS (plain English) ===== --}}
                    <section aria-labelledby="report-ai-heading" class="space-y-2">
                        <h3 id="report-ai-heading" class="flex items-center gap-2 font-heading font-bold text-base dark:text-white text-gray-900">
                            <x-ai-sparkle class="w-4 h-4 text-primary dark:text-blue-light" />
                            What stands out
                        </h3>
                    {{-- ===== AI INSIGHTS CARD ===== --}}
                    @if($aiInsightsLoading)
                        {{-- First-time loading — x-init fires generateInsights() when Alpine mounts the element --}}
                        <div x-data x-init="$wire.generateInsights()"
                             class="dark:bg-slate-800 bg-white rounded-xl border dark:border-slate-700 border-gray-200 overflow-hidden">
                            {{-- Animated header --}}
                            <div class="px-5 pt-8 pb-6 flex flex-col items-center text-center border-b dark:border-slate-700 border-gray-100">
                                {{-- Pulsing AI icon with orbiting ping --}}
                                <div class="relative mb-4">
                                    <div class="w-14 h-14 rounded-xl dark:bg-slate-700 bg-blue-50 flex items-center justify-center">
                                        <svg class="w-7 h-7 dark:text-blue-light text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                                        </svg>
                                    </div>
                                    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-primary animate-ping"></span>
                                    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-primary"></span>
                                </div>
                                <p class="text-sm font-semibold dark:text-slate-200 text-gray-800 font-body">Analyzing your cash flow…</p>
                                <p class="text-xs dark:text-slate-500 text-gray-400 font-body mt-1.5">AI is reviewing your entries. This takes a few seconds.</p>
                                {{-- Animated dots --}}
                                <div class="flex items-center gap-1.5 mt-4">
                                    <span class="w-1.5 h-1.5 rounded-full dark:bg-blue-light bg-primary animate-bounce" style="animation-delay:0ms; animation-duration:1s"></span>
                                    <span class="w-1.5 h-1.5 rounded-full dark:bg-blue-light bg-primary animate-bounce" style="animation-delay:200ms; animation-duration:1s"></span>
                                    <span class="w-1.5 h-1.5 rounded-full dark:bg-blue-light bg-primary animate-bounce" style="animation-delay:400ms; animation-duration:1s"></span>
                                </div>
                            </div>
                            {{-- Shimmer skeleton below to show card structure --}}
                            <div class="px-5 py-4 space-y-3">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-1.5 h-1.5 rounded-full dark:bg-slate-700 bg-gray-200 mt-2 flex-shrink-0"></div>
                                    <div class="h-4 dark:bg-slate-700 bg-gray-200 rounded animate-pulse w-full"></div>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <div class="w-1.5 h-1.5 rounded-full dark:bg-slate-700 bg-gray-200 mt-2 flex-shrink-0"></div>
                                    <div class="h-4 dark:bg-slate-700 bg-gray-200 rounded animate-pulse w-5/6"></div>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <div class="w-1.5 h-1.5 rounded-full dark:bg-slate-700 bg-gray-200 mt-2 flex-shrink-0"></div>
                                    <div class="h-4 dark:bg-slate-700 bg-gray-200 rounded animate-pulse w-4/6"></div>
                                </div>
                            </div>
                            <div class="mx-5 mb-5 h-10 dark:bg-slate-700 bg-gray-100 rounded-xl animate-pulse"></div>
                        </div>

                    @elseif($aiInsightsError === 'not_enough_data')
                        {{-- Not enough entries --}}
                        <div class="dark:bg-slate-800 bg-white rounded-xl border dark:border-slate-700 border-gray-200 px-5 py-8 text-center">
                            <div class="w-10 h-10 rounded-xl dark:bg-slate-700 bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold font-body dark:text-slate-300 text-gray-700 mb-1">Not enough data yet</p>
                            <p class="text-xs dark:text-slate-500 text-gray-400 font-body">Add at least 3 entries to generate AI insights.</p>
                        </div>

                    @elseif($aiInsightsError === 'failed')
                        {{-- API failed --}}
                        <div class="dark:bg-red-500/8 bg-red-50 rounded-xl border dark:border-red-500/15 border-red-200 px-5 py-4 flex items-start gap-3">
                            <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold dark:text-red-300 text-red-700 font-body">Couldn't generate insights</p>
                                <p class="text-xs dark:text-red-400/70 text-red-500 font-body mt-0.5">Something went wrong. Try again in a moment.</p>
                            </div>
                            <button wire:click="generateInsights"
                                    class="flex-shrink-0 text-xs font-semibold font-body px-3 py-1.5 rounded-lg
                                           dark:bg-red-500/15 bg-red-100 dark:text-red-300 text-red-700
                                           dark:hover:bg-red-500/25 hover:bg-red-200 transition-colors duration-150">
                                Retry
                            </button>
                        </div>

                    @elseif($aiInsightsLimitReached && empty($aiInsightsData))
                        {{-- Daily limit hit, no cache to show --}}
                        <div class="dark:bg-amber-500/10 bg-amber-50 rounded-xl border dark:border-amber-500/20 border-amber-200 px-5 py-4 flex items-start gap-3">
                            <svg class="w-4 h-4 text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold dark:text-amber-300 text-amber-700 font-body">Daily limit reached</p>
                                <p class="text-xs dark:text-amber-400/70 text-amber-600 font-body mt-0.5">You've used all 10 insight generations for today. Resets at midnight.</p>
                            </div>
                        </div>

                    @elseif(!empty($aiInsightsData))
                        {{-- ✅ Insights loaded --}}
                        @php $sentiment = $aiInsightsData['sentiment'] ?? 'watch'; @endphp

                        <div class="relative">

                            {{-- Regenerating overlay — wire:loading.flex forces display:flex so centering works --}}
                            <div wire:loading.flex wire:target="generateInsights"
                                 style="display:none"
                                 class="absolute inset-0 z-10 flex-col items-center justify-center gap-4 rounded-xl
                                        dark:bg-slate-900 bg-white backdrop-blur-sm">
                                {{-- Spinning ring --}}
                                <div class="relative w-12 h-12">
                                    <svg class="w-12 h-12 animate-spin dark:text-slate-700 text-gray-200" fill="none" viewBox="0 0 48 48">
                                        <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="4"/>
                                    </svg>
                                    <svg class="absolute inset-0 w-12 h-12 animate-spin dark:text-blue-light text-primary" style="animation-duration:1.1s" fill="none" viewBox="0 0 48 48">
                                        <path d="M44 24a20 20 0 0 0-20-20" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                                    </svg>
                                    {{-- AI sparkle in center --}}
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <svg class="w-5 h-5 dark:text-blue-light text-primary" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-semibold dark:text-slate-200 text-gray-700 font-body">AI insights are regenerating…</p>
                                    <p class="text-xs dark:text-slate-500 text-gray-400 font-body mt-1">Hang tight, this takes a few seconds.</p>
                                </div>
                                {{-- Animated dots --}}
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full dark:bg-blue-light bg-primary animate-bounce" style="animation-delay:0ms; animation-duration:1s"></span>
                                    <span class="w-1.5 h-1.5 rounded-full dark:bg-blue-light bg-primary animate-bounce" style="animation-delay:200ms; animation-duration:1s"></span>
                                    <span class="w-1.5 h-1.5 rounded-full dark:bg-blue-light bg-primary animate-bounce" style="animation-delay:400ms; animation-duration:1s"></span>
                                </div>
                            </div>

                            {{-- Card — blurred while regenerating --}}
                            <div wire:loading.class="opacity-30 pointer-events-none" wire:target="generateInsights"
                                 class="dark:bg-slate-800 bg-white rounded-xl border dark:border-slate-700 border-gray-200 overflow-hidden transition-opacity duration-200">

                            {{-- Header --}}
                            <div class="flex items-center justify-between px-5 pt-5 pb-4
                                        border-b dark:border-slate-700 border-gray-100">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    {{-- Sentiment badge — inline styles via Alpine so dark mode works regardless of Tailwind compilation --}}
                                    @if($sentiment === 'healthy')
                                        <span x-data="{ d: document.documentElement.classList.contains('dark') }"
                                              :style="d ? 'background:#064e3b;color:#6ee7b7;border-color:#15803d' : 'background:#ecfdf5;color:#047857;border-color:#a7f3d0'"
                                              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-body border flex-shrink-0">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 flex-shrink-0"></span>
                                            Healthy
                                        </span>
                                    @elseif($sentiment === 'concern')
                                        <span x-data="{ d: document.documentElement.classList.contains('dark') }"
                                              :style="d ? 'background:#7f1d1d;color:#fca5a5;border-color:#b91c1c' : 'background:#fef2f2;color:#b91c1c;border-color:#fecaca'"
                                              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-body border flex-shrink-0">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span>
                                            Concern
                                        </span>
                                    @else
                                        <span x-data="{ d: document.documentElement.classList.contains('dark') }"
                                              :style="d ? 'background:#78350f;color:#fcd34d;border-color:#b45309' : 'background:#fffbeb;color:#b45309;border-color:#fde68a'"
                                              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold font-body border flex-shrink-0">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span>
                                            Watch
                                        </span>
                                    @endif
                                    @if(!empty($aiInsightsData['sentiment_reason']))
                                        <span class="text-sm dark:text-slate-400 text-gray-500 font-body truncate">
                                            {{ $aiInsightsData['sentiment_reason'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 flex-shrink-0 ml-3">
                                    @if($aiInsightsGeneratedAt)
                                        <span class="text-[11px] dark:text-slate-600 text-gray-400 font-body hidden sm:block">
                                            {{ $aiInsightsGeneratedAt }}
                                        </span>
                                    @endif
                                    @if(!$aiInsightsLimitReached)
                                        <button aria-label="Regenerate insights" wire:click="generateInsights"
                                                wire:loading.attr="disabled"
                                                wire:target="generateInsights"
                                                title="Regenerate insights"
                                                class="p-1.5 rounded-lg dark:text-slate-500 text-gray-400
                                                       dark:hover:bg-slate-700 hover:bg-gray-100
                                                       dark:hover:text-slate-300 hover:text-gray-600
                                                       transition-colors duration-150 disabled:opacity-40">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Bullets --}}
                            <div class="px-5 py-4 space-y-3">
                                @foreach($aiInsightsData['bullets'] ?? [] as $bullet)
                                    <div class="flex items-start gap-3">
                                        @if($sentiment === 'healthy')
                                            <span class="ai-bullet-healthy w-1.5 h-1.5 rounded-full bg-emerald-500 mt-2 flex-shrink-0"></span>
                                        @elseif($sentiment === 'concern')
                                            <span class="ai-bullet-concern w-1.5 h-1.5 rounded-full bg-red-500 mt-2 flex-shrink-0"></span>
                                        @else
                                            <span class="ai-bullet-watch w-1.5 h-1.5 rounded-full bg-amber-500 mt-2 flex-shrink-0"></span>
                                        @endif
                                        <p class="text-sm dark:text-slate-300 text-gray-700 font-body leading-relaxed">{{ $bullet }}</p>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Tip --}}
                            @if(!empty($aiInsightsData['tip']))
                                <div class="mx-5 mb-5 flex items-start gap-3 px-4 py-3 rounded-xl
                                            dark:bg-slate-700 bg-blue-50
                                            dark:border dark:border-slate-600 border border-blue-100">
                                    <svg class="w-4 h-4 dark:text-blue-light text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>
                                    </svg>
                                    <p class="text-sm dark:text-blue-light text-primary font-body leading-relaxed">
                                        <strong class="font-semibold">Tip:</strong> {{ $aiInsightsData['tip'] }}
                                    </p>
                                </div>
                            @endif

                            {{-- Limit warning inline --}}
                            @if($aiInsightsLimitReached)
                                <div class="px-5 pb-4">
                                    <p class="text-[11px] dark:text-amber-500/70 text-amber-600 font-body text-center">
                                        Daily limit reached — insights will refresh tomorrow.
                                    </p>
                                </div>
                            @endif

                            </div>{{-- end inner card --}}
                        </div>{{-- end relative wrapper --}}

                    @endif
                    </section>

                </div>

            @else
                {{-- Free user — blurred preview + upgrade CTA --}}
                <div class="relative">
                    {{-- Blurred placeholder --}}
                    <div class="filter blur-sm pointer-events-none select-none" aria-hidden="true">
                        <div class="space-y-5">
                            {{-- Fake summary cards --}}
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                @foreach(['Cash In' => ['emerald-400', '125,000'], 'Cash Out' => ['red-400', '87,500'], 'Net Balance' => ['blue-light', '37,500'], 'Daily Average' => ['slate-300', '1,250']] as $label => $info)
                                    <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-4">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">{{ $label }}</p>
                                        <p class="font-mono font-bold text-lg text-{{ $info[0] }} leading-tight mt-1">{{ $info[1] }}</p>
                                        <p class="text-[10px] dark:text-slate-600 text-gray-400 font-body mt-1">12 entries</p>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Fake trend chart --}}
                            <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-5">
                                <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-4">Cash Flow Trend</h3>
                                <div class="flex items-end gap-1 h-32">
                                    @for($i = 0; $i < 14; $i++)
                                        <div class="flex-1 flex items-end gap-px h-full">
                                            <div class="flex-1 rounded-t-sm bg-emerald-500/50" style="height: {{ rand(15, 90) }}%"></div>
                                            <div class="flex-1 rounded-t-sm bg-red-500/50" style="height: {{ rand(10, 70) }}%"></div>
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            {{-- Fake category breakdown --}}
                            <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-5">
                                <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-4">By Category</h3>
                                <div class="space-y-3">
                                    @foreach(['Salaries' => 80, 'Rent' => 55, 'Supplies' => 35, 'Transport' => 20] as $name => $pct)
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-sm font-body dark:text-slate-300">{{ $name }}</span>
                                                <span class="font-mono text-sm dark:text-slate-400">--</span>
                                            </div>
                                            <div class="h-2 dark:bg-slate-800 bg-gray-100 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full bg-red-500/60" style="width: {{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Upgrade overlay --}}
                    <div class="absolute inset-0 flex items-center justify-center z-10">
                        <div class="max-w-sm mx-auto px-6 py-8 text-center dark:bg-dark/95 bg-white/95 backdrop-blur-md rounded-2xl border dark:border-slate-700 border-gray-200 shadow-2xl">
                            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                                </svg>
                            </div>
                            <h3 class="font-display font-extrabold text-lg dark:text-white text-gray-900 mb-2">Unlock Reports</h3>
                            <p class="text-sm dark:text-slate-400 text-gray-500 font-body mb-5">See money in vs out over time, where your money goes, and plain-English insights with Pro.</p>
                            <a href="{{ route('billing') }}" wire:navigate
                               class="inline-flex items-center justify-center w-full px-5 py-2.5 bg-primary text-white text-sm font-semibold font-body rounded-lg hover:brightness-110 hover:shadow-lg transition-all duration-150">
                                See Pro plans
                            </a>
                            <button wire:click="$set('activeTab', 'entries')"
                                    class="mt-3 text-sm dark:text-slate-500 text-gray-400 font-body hover:dark:text-white hover:text-gray-900 transition-colors">
                                Not Now
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            @elseif($activeTab === 'recurring')

                {{-- ===== RECURRING TAB ===== --}}
                @if($business->isPro())

                    @if($recurringEntries->isEmpty())
                        <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-dashed border-gray-200 rounded-xl px-8 py-20 text-center">
                            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/>
                                </svg>
                            </div>
                            <h3 class="font-display font-extrabold text-lg dark:text-white text-gray-900 mb-2">No recurring entries yet</h3>
                            <p class="text-sm dark:text-slate-500 text-gray-400 font-body max-w-sm mx-auto">
                                Add an entry and enable "Repeat this entry" to set up a daily, weekly, or bi-weekly schedule.
                            </p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($recurringEntries as $rec)
                                @php $isActive = $rec->isActive(); $isCompleted = $rec->isCompleted(); @endphp
                                <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-4
                                            {{ !$isActive ? 'opacity-60' : '' }} transition-opacity duration-150">
                                    <div class="flex items-start gap-3">
                                        {{-- Type dot --}}
                                        <div class="mt-1.5 flex-shrink-0">
                                            <span class="block w-2.5 h-2.5 rounded-full {{ $rec->type === 'in' ? 'bg-emerald-400' : 'bg-red-400' }}
                                                         {{ $isActive ? 'animate-pulse' : '' }}"></span>
                                        </div>

                                        {{-- Info --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-sm font-body font-medium dark:text-white text-gray-900 truncate">{{ $rec->displayLabel() }}</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                                             dark:bg-slate-800 bg-gray-100 dark:text-slate-400 text-gray-500">
                                                    {{ $rec->frequency === 'biweekly' ? 'Bi-weekly' : ucfirst($rec->frequency) }}
                                                </span>
                                                @if($isCompleted)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-400">Completed</span>
                                                @elseif(!$isActive)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400">Paused</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-3 mt-1 flex-wrap">
                                                <x-amount :value="$rec->amount" :symbol="$business->currencySymbol()" :type="$rec->type" class="text-sm" />
                                                @if(!$isCompleted)
                                                    <span class="text-[11px] dark:text-slate-500 text-gray-400 font-body">
                                                        Next: <span class="font-mono dark:text-slate-400 text-gray-500">{{ $rec->next_run_at->format('d M Y') }}</span>
                                                    </span>
                                                @endif
                                                @if($rec->ends_at)
                                                    <span class="text-[11px] dark:text-slate-500 text-gray-400 font-body">
                                                        Until: <span class="font-mono dark:text-slate-400 text-gray-500">{{ $rec->ends_at->format('d M Y') }}</span>
                                                    </span>
                                                @else
                                                    <span class="text-[11px] dark:text-slate-500 text-gray-400 font-body">No end date</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions --}}
                                        @if($userRole !== 'viewer')
                                            <div class="flex items-center gap-1 flex-shrink-0">
                                                @if(!$isCompleted)
                                                    <button wire:click="toggleRecurringStatus('{{ $rec->id }}')"
                                                            title="{{ $isActive ? 'Pause' : 'Resume' }}"
                                                            class="p-2 rounded-lg transition-colors duration-150
                                                                   {{ $isActive ? 'dark:text-slate-400 text-gray-500 dark:hover:bg-slate-700 hover:bg-gray-100' : 'text-emerald-500 dark:hover:bg-emerald-500/10 hover:bg-emerald-50' }}">
                                                        @if($isActive)
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5"/></svg>
                                                        @else
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                                                        @endif
                                                    </button>
                                                @endif
                                                <button wire:click="deleteRecurring('{{ $rec->id }}')"
                                                        wire:confirm="Delete this recurring entry? It will stop repeating."
                                                        title="Delete"
                                                        class="p-2 rounded-lg text-red-600 dark:text-red-400 dark:hover:bg-red-500/10 hover:bg-red-50 transition-colors duration-150">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                @else
                    {{-- Free user blurred preview --}}
                    <div class="relative">
                        <div class="filter blur-sm pointer-events-none select-none space-y-3">
                            @foreach([['Monthly Rent', 'out', '50,000.00', 'weekly'], ['Daily Sales', 'in', '5,000.00', 'daily'], ['Staff Salary', 'out', '25,000.00', 'biweekly']] as [$desc, $type, $amt, $freq])
                                <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-1.5"><span class="block w-2.5 h-2.5 rounded-full {{ $type === 'in' ? 'bg-emerald-400' : 'bg-red-400' }}"></span></div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-body dark:text-white text-gray-900">{{ $desc }}</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase dark:bg-slate-800 bg-gray-100 dark:text-slate-400 text-gray-500">{{ ucfirst($freq) }}</span>
                                            </div>
                                            <span class="font-mono text-sm {{ $type === 'in' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} mt-1 block">{{ $type === 'in' ? '+' : '-' }}${{ $amt }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-6">
                            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/></svg>
                            </div>
                            <h3 class="font-display font-extrabold text-lg dark:text-white text-gray-900 mb-2">Automate Recurring Entries</h3>
                            <p class="text-sm dark:text-slate-400 text-gray-500 font-body mb-5">Set daily or weekly entries to repeat automatically. Upgrade to Pro.</p>
                            <a href="{{ route('billing') }}" wire:navigate
                               class="inline-flex items-center justify-center w-full max-w-xs px-5 py-2.5 bg-primary hover:bg-accent text-white text-sm font-semibold font-body rounded-xl transition-colors duration-150 shadow-lg shadow-primary/25">
                                Upgrade to Pro
                            </a>
                        </div>
                    </div>
                @endif

            @elseif($activeTab === 'activity')

                {{-- ===== ACTIVITY TAB ===== --}}
                <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl overflow-hidden">

                    {{-- Header + Filters --}}
                    <div class="px-5 py-4 border-b dark:border-slate-700 border-gray-100">
                        {{-- Title row --}}
                        <div class="flex items-center justify-between gap-4 mb-3">
                            <div>
                                <h3 class="font-heading font-bold text-base dark:text-white text-gray-900">Activity Log</h3>
                                <p class="text-xs font-body dark:text-slate-500 text-gray-400 mt-0.5">
                                    {{ number_format($activityTotal) }} {{ $activityTotal === 1 ? 'action' : 'actions' }} recorded
                                </p>
                            </div>
                            {{-- Colour legend --}}
                            <div class="hidden sm:flex items-center gap-3 flex-shrink-0">
                                <span class="flex items-center gap-1.5 text-[11px] font-body dark:text-slate-500 text-gray-400">
                                    <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>Added
                                </span>
                                <span class="flex items-center gap-1.5 text-[11px] font-body dark:text-slate-500 text-gray-400">
                                    <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>Modified
                                </span>
                                <span class="flex items-center gap-1.5 text-[11px] font-body dark:text-slate-500 text-gray-400">
                                    <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>Deleted
                                </span>
                            </div>
                        </div>

                        {{-- Filter bar --}}
                        <div class="flex flex-wrap items-center gap-2">

                            {{-- Member filter --}}
                            @if($activityMembers->count() > 1)
                            @php
                                $selectedMemberName = $activityFilterUserId
                                    ? ($activityMembers->firstWhere('id', $activityFilterUserId)?->name ?? 'Unknown')
                                    : 'All members';
                                if ($activityFilterUserId === auth()->id()) $selectedMemberName = 'You';
                            @endphp
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-body
                                               dark:bg-slate-800 bg-white border dark:border-slate-700 border-gray-200
                                               dark:text-slate-300 text-gray-700 transition-colors duration-150
                                               hover:dark:border-slate-600 hover:border-gray-300 cursor-pointer">
                                    <span>{{ $selectedMemberName }}</span>
                                    <svg class="w-3 h-3 dark:text-slate-500 text-gray-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute top-full left-0 mt-1 z-30 min-w-[160px]
                                            dark:bg-slate-800 bg-white border dark:border-slate-700 border-gray-200
                                            rounded-xl shadow-lg overflow-hidden">
                                    <button type="button"
                                            wire:click="$set('activityFilterUserId', '')"
                                            @click="open = false"
                                            class="w-full text-left px-3 py-2 text-xs font-body dark:text-slate-300 text-gray-700
                                                   hover:dark:bg-slate-700 hover:bg-gray-50 transition-colors duration-100
                                                   {{ $activityFilterUserId === '' ? 'font-semibold text-primary dark:text-primary' : '' }}">
                                        All members
                                    </button>
                                    @foreach($activityMembers as $member)
                                    <button type="button"
                                            wire:click="$set('activityFilterUserId', '{{ $member->id }}')"
                                            @click="open = false"
                                            class="w-full text-left px-3 py-2 text-xs font-body dark:text-slate-300 text-gray-700
                                                   hover:dark:bg-slate-700 hover:bg-gray-50 transition-colors duration-100
                                                   {{ $activityFilterUserId === $member->id ? 'font-semibold text-primary dark:text-primary' : '' }}">
                                        {{ $member->id === auth()->id() ? 'You ('.$member->name.')' : $member->name }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            {{-- Action type filter --}}
                            @php
                                $actionLabels = ['' => 'All actions', 'entry_created' => 'Added entries', 'entry_updated' => 'Edited entries', 'entry_deleted' => 'Deleted entries', 'bulk' => 'Bulk actions', 'comment' => 'Comments', 'attachment' => 'Attachments', 'recurring' => 'Recurring'];
                                $selectedActionLabel = $actionLabels[$activityFilterAction] ?? 'All actions';
                            @endphp
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" @click="open = !open"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-body
                                               dark:bg-slate-800 bg-white border dark:border-slate-700 border-gray-200
                                               dark:text-slate-300 text-gray-700 transition-colors duration-150
                                               hover:dark:border-slate-600 hover:border-gray-300 cursor-pointer">
                                    <span>{{ $selectedActionLabel }}</span>
                                    <svg class="w-3 h-3 dark:text-slate-500 text-gray-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute top-full left-0 mt-1 z-30 min-w-[160px]
                                            dark:bg-slate-800 bg-white border dark:border-slate-700 border-gray-200
                                            rounded-xl shadow-lg overflow-hidden">
                                    @foreach($actionLabels as $val => $label)
                                    <button type="button"
                                            wire:click="$set('activityFilterAction', '{{ $val }}')"
                                            @click="open = false"
                                            class="w-full text-left px-3 py-2 text-xs font-body dark:text-slate-300 text-gray-700
                                                   hover:dark:bg-slate-700 hover:bg-gray-50 transition-colors duration-100
                                                   {{ $activityFilterAction === $val ? 'font-semibold text-primary dark:text-primary' : '' }}">
                                        {{ $label }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Clear filters --}}
                            @if($activityFilterUserId !== '' || $activityFilterAction !== '')
                                <button wire:click="$set('activityFilterUserId', ''); $set('activityFilterAction', '')"
                                        class="text-[11px] font-body text-primary hover:text-accent transition-colors duration-150 px-1">
                                    Clear filters
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($activityLog->isEmpty())
                        {{-- Empty state --}}
                        <div class="px-8 py-16 text-center">
                            <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                            </div>
                            <p class="font-body text-sm font-semibold dark:text-slate-300 text-gray-700 mb-1">No activity yet</p>
                            <p class="font-body text-xs dark:text-slate-500 text-gray-400">Actions on entries will appear here as they happen.</p>
                        </div>

                    @else
                        <div class="divide-y dark:divide-slate-800 divide-gray-100">
                            @foreach($activityLog as $log)
                                @php
                                    $meta        = $log->meta ?? [];
                                    $iconType    = $log->iconType();
                                    $initials    = strtoupper(substr($log->user->name ?? '?', 0, 1));
                                    $isCurrentUser = $log->user_id === auth()->id();
                                    $entryType   = $meta['type'] ?? null;
                                    $amount      = isset($meta['amount']) ? number_format((float) $meta['amount'], 2) : null;
                                    $description = $meta['description'] ?? null;
                                @endphp
                                <div x-data="{ shown: false }"
                                     x-init="setTimeout(() => shown = true, {{ $loop->index * 30 }})"
                                     :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-1'"
                                     class="flex items-start gap-3 px-5 py-3.5 dark:hover:bg-slate-800 hover:bg-gray-50 transition-all duration-300">

                                    {{-- Avatar --}}
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center
                                                    {{ $isCurrentUser
                                                        ? 'bg-primary/10 dark:bg-primary/20'
                                                        : 'bg-gray-100 dark:bg-slate-700' }}">
                                            <span class="text-[11px] font-bold
                                                         {{ $isCurrentUser
                                                             ? 'text-primary dark:text-blue-light'
                                                             : 'text-gray-600 dark:text-slate-300' }}">
                                                {{ $initials }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Content --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-sm font-body leading-snug dark:text-slate-300 text-gray-700">
                                                {{-- Action dot --}}
                                                @if($iconType === 'created')
                                                    <span class="inline-block w-1.5 h-1.5 rounded-full mr-1 relative top-[-1px] bg-green-500"></span>
                                                @elseif($iconType === 'deleted')
                                                    <span class="inline-block w-1.5 h-1.5 rounded-full mr-1 relative top-[-1px] bg-red-500"></span>
                                                @else
                                                    <span class="inline-block w-1.5 h-1.5 rounded-full mr-1 relative top-[-1px] bg-blue-500"></span>
                                                @endif
                                                {{-- User name --}}
                                                <span class="font-semibold dark:text-white text-gray-900">
                                                    {{ $isCurrentUser ? 'You' : ($log->user->name ?? 'Unknown') }}
                                                </span>
                                                {{ $log->describe() }}
                                                {{-- Amount (for single entry actions) --}}
                                                @if($amount !== null)
                                                    <span class="font-mono font-semibold
                                                                 {{ $entryType === 'in' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                        · {{ $business->currency ?? 'USD' }} {{ $amount }}
                                                    </span>
                                                @endif
                                            </p>
                                            {{-- Timestamp --}}
                                            <span class="font-mono text-[11px] dark:text-slate-500 text-gray-400 flex-shrink-0 whitespace-nowrap">
                                                {{ $log->created_at->diffForHumans(short: true) }}
                                            </span>
                                        </div>
                                        {{-- Sub-detail: entry description --}}
                                        @if($description)
                                            <p class="font-body text-xs dark:text-slate-500 text-gray-400 mt-0.5 truncate">{{ $description }}</p>
                                        @endif
                                    </div>

                                </div>
                            @endforeach
                        </div>

                        {{-- Load more / progress footer --}}
                        <div class="px-5 py-3 border-t dark:border-slate-800 border-gray-100 flex items-center justify-between gap-4">
                            <p class="text-[11px] font-body dark:text-slate-500 text-gray-400">
                                Showing {{ $activityLog->count() }} of {{ number_format($activityTotal) }}
                            </p>
                            @if($activityLog->count() < $activityTotal)
                                <button wire:click="loadMoreActivity"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold font-body text-primary hover:text-accent transition-colors duration-150 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="loadMoreActivity">Load more</span>
                                    <span wire:loading wire:target="loadMoreActivity">Loading…</span>
                                    <svg wire:loading.remove wire:target="loadMoreActivity" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endif

                </div>

            @endif {{-- end activeTab --}}

        </div>
    </div>

    {{-- ===== COMMENTS PANEL ===== --}}
    @if($showCommentPanel)
        {{-- Backdrop --}}
        <div class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm lg:bg-transparent lg:backdrop-blur-none"
             wire:click="closeComments"></div>

        {{-- Slide-over panel --}}
        <div x-data="{ show: false }"
             x-init="$nextTick(() => show = true)"
             :class="show ? 'translate-x-0' : 'translate-x-full'"
             class="fixed inset-y-0 right-0 z-50 w-full max-w-md flex flex-col
                    dark:bg-slate-900 bg-white
                    dark:border-l dark:border-slate-800 border-l border-gray-200
                    shadow-2xl shadow-black/30 transition-transform duration-300 ease-out">

            {{-- Header --}}
            <div class="flex items-start justify-between px-5 py-4 border-b dark:border-slate-800 border-gray-100 flex-shrink-0">
                <div class="min-w-0 flex-1 pr-3">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $commentingEntryType === 'in' ? 'bg-emerald-400' : 'bg-red-400' }}"></div>
                        <p class="text-xs font-semibold uppercase tracking-wider font-body
                                  {{ $commentingEntryType === 'in' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $commentingEntryType === 'in' ? 'Cash In' : 'Cash Out' }}
                        </p>
                    </div>
                    <h3 class="text-base font-heading font-semibold dark:text-white text-gray-900 truncate">
                        {{ $commentingEntryDesc }}
                    </h3>
                    <p class="text-sm font-mono {{ $commentingEntryType === 'in' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} mt-0.5">
                        {{ $commentingEntryType === 'in' ? '+' : '-' }}{{ number_format((float)$commentingEntryAmount, 2) }}
                    </p>
                </div>
                <button aria-label="Close comments" wire:click="closeComments"
                        class="p-2 rounded-lg dark:text-slate-400 text-gray-500 dark:hover:bg-slate-800 hover:bg-gray-100 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Comments list --}}
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                @if($commentThread->isEmpty())
                    <div class="flex flex-col items-center justify-center h-40 text-center">
                        <div class="w-12 h-12 rounded-full dark:bg-slate-800 bg-gray-100 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold font-heading dark:text-slate-300 text-gray-700">No comments yet</p>
                        <p class="text-xs font-body dark:text-slate-500 text-gray-400 mt-1">
                            Add a note or @mention a team member.
                        </p>
                    </div>
                @else
                    @foreach($commentThread as $comment)
                        <div class="flex gap-3 group">
                            {{-- Avatar --}}
                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white
                                        {{ $comment->user_id === auth()->id() ? 'bg-gradient-to-br from-primary to-accent' : 'dark:bg-slate-700 bg-gray-200 dark:text-slate-300 text-gray-600' }}">
                                {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                            </div>
                            {{-- Body --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline gap-2 mb-1">
                                    <span class="text-xs font-semibold font-body dark:text-slate-200 text-gray-800">
                                        {{ $comment->user_id === auth()->id() ? 'You' : ($comment->user?->name ?? 'Deleted user') }}
                                    </span>
                                    <span class="text-[10px] font-mono dark:text-slate-600 text-gray-400">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="text-sm font-body dark:text-slate-300 text-gray-700 leading-relaxed break-words">
                                    {!! $comment->renderedBody() !!}
                                </div>
                            </div>
                            {{-- Delete --}}
                            @if($comment->user_id === auth()->id() || $userRole === 'owner')
                                <button type="button" aria-label="Delete comment" wire:click="confirmDeleteComment('{{ $comment->id }}')"
                                        class="flex-shrink-0 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 focus:opacity-100 p-1 rounded dark:text-slate-400 text-gray-400 dark:hover:text-red-400 hover:text-red-500 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Free plan: threads are readable, posting is Pro --}}
            @if($userRole !== 'viewer' && !$business->isPro())
                <div class="flex-shrink-0 px-5 py-4 border-t dark:border-slate-800 border-gray-100 flex items-center justify-between gap-3">
                    <p class="text-xs font-body dark:text-slate-400 text-gray-500">Adding comments is available on the Pro plan.</p>
                    <button type="button" wire:click="$set('upgradeModalFeature', 'comments')"
                            class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold font-body bg-primary text-white hover:brightness-110 transition-all">Upgrade</button>
                </div>
            @endif

            {{-- Add comment input (viewers can read but not comment) --}}
            @if($userRole !== 'viewer' && $business->isPro())
                <div class="flex-shrink-0 px-5 py-4 border-t dark:border-slate-800 border-gray-100"
                     x-data="{
                         body: @entangle('commentBody').live,
                         showMentions: false,
                         mentionStart: -1,
                         members: {{ $commentMembers->toJson() }},
                         filtered: [],
                         onInput(e) {
                             const val = e.target.value;
                             const cur = e.target.selectionStart;
                             // Find last @ before cursor
                             const before = val.slice(0, cur);
                             const atIdx = before.lastIndexOf('@');
                             if (atIdx !== -1 && !before.slice(atIdx + 1).includes(' ')) {
                                 const query = before.slice(atIdx + 1).toLowerCase();
                                 this.filtered = this.members.filter(m => m.name.toLowerCase().includes(query));
                                 this.showMentions = this.filtered.length > 0;
                                 this.mentionStart = atIdx;
                             } else {
                                 this.showMentions = false;
                                 this.mentionStart = -1;
                             }
                         },
                         selectMember(member) {
                             const ta = this.$refs.commentInput;
                             const before = ta.value.slice(0, this.mentionStart);
                             const after = ta.value.slice(ta.selectionStart);
                             const token = '@[' + member.name + ']{' + member.id + '}';
                             this.body = before + token + ' ' + after;
                             this.showMentions = false;
                             this.mentionStart = -1;
                             this.$nextTick(() => ta.focus());
                         }
                     }">
                    <div class="relative">
                        {{-- @mention dropdown --}}
                        <div x-show="showMentions"
                             x-cloak
                             class="absolute bottom-full left-0 right-0 mb-2 dark:bg-slate-800 bg-white
                                    dark:border dark:border-slate-700 border border-gray-200
                                    rounded-xl shadow-xl overflow-hidden z-10">
                            <template x-for="member in filtered" :key="member.id">
                                <button type="button"
                                        @click="selectMember(member)"
                                        @mousedown.prevent
                                        class="flex items-center gap-2.5 w-full px-3 py-2.5 text-sm font-body
                                               dark:hover:bg-slate-700 hover:bg-gray-50
                                               dark:text-slate-200 text-gray-800 transition-colors">
                                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center text-[10px] font-bold text-white flex-shrink-0"
                                         x-text="member.name.charAt(0).toUpperCase()"></div>
                                    <span x-text="member.name"></span>
                                </button>
                            </template>
                        </div>

                        <div class="flex gap-2 items-end">
                            {{-- Current user avatar --}}
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center text-xs font-bold text-white self-end">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            {{-- Textarea --}}
                            <div class="flex-1 relative">
                                <textarea x-ref="commentInput"
                                          x-model="body"
                                          @input="onInput($event)"
                                          @keydown.escape="showMentions = false"
                                          @keydown.enter.prevent="if(!showMentions) { $wire.addComment() }"
                                          placeholder="Add a comment… type @ to mention"
                                          rows="1"
                                          class="w-full px-3 py-2.5 text-sm font-body resize-none rounded-lg
                                                 dark:bg-slate-800 bg-gray-50
                                                 dark:border dark:border-slate-700 border border-gray-200
                                                 dark:text-white text-gray-900
                                                 dark:placeholder-slate-600 placeholder-gray-400
                                                 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                                 transition-all duration-150"
                                          style="field-sizing: content; max-height: 120px;"></textarea>
                            </div>
                            {{-- Send button --}}
                            <button aria-label="Send comment" wire:click="addComment"
                                    :disabled="!body.trim()"
                                    class="flex-shrink-0 p-2.5 rounded-lg transition-all duration-150
                                           disabled:opacity-30 disabled:cursor-not-allowed
                                           bg-primary hover:bg-accent text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-[10px] font-body dark:text-slate-600 text-gray-400 mt-1.5 ml-10">
                            Enter to send · type @ to mention a team member
                        </p>
                    </div>
                </div>
            @else
                <div class="flex-shrink-0 px-5 py-3 border-t dark:border-slate-800 border-gray-100 text-center">
                    <p class="text-xs font-body dark:text-slate-500 text-gray-400">Viewers can read but not add comments.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- ===== DELETE ENTRY CONFIRM MODAL ===== --}}
    @if($showDeleteEntryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showDeleteEntryModal', false)"></div>
            <div class="relative w-full max-w-md dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 overflow-hidden"
                 x-data x-init="$el.querySelector('[data-autofocus]')?.focus()">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 pt-6 pb-4
                            border-b dark:border-slate-700/60 border-gray-100">
                    <h3 class="font-heading font-bold text-lg dark:text-white text-gray-900">Delete Entry</h3>
                    <button aria-label="Close" wire:click="$set('showDeleteEntryModal', false)"
                            class="w-8 h-8 flex items-center justify-center rounded-lg
                                   dark:text-slate-400 text-gray-400
                                   dark:hover:bg-slate-700 hover:bg-gray-100
                                   transition-colors duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5">

                    {{-- Warning banner --}}
                    <div class="flex items-start gap-3 px-4 py-3.5 rounded-xl
                                bg-red-50 dark:bg-red-500/10
                                border border-red-200 dark:border-red-500/20">
                        <svg class="w-5 h-5 text-red-500 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        </svg>
                        <p class="text-sm dark:text-red-300 text-red-700 font-body leading-relaxed">
                            Once deleted, this entry <strong>cannot be restored</strong>. Are you sure you want to delete it?
                        </p>
                    </div>

                    {{-- Entry details --}}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-3">Review Details</p>
                        <div class="rounded-xl dark:bg-slate-800 bg-gray-50 border dark:border-slate-700 border-gray-200 px-4 py-4">
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <p class="text-xs dark:text-slate-500 text-gray-400 font-body mb-1">Type</p>
                                    <p class="text-sm font-semibold font-body
                                              {{ $pendingDeleteType === 'Cash In' ? 'text-emerald-500' : 'text-red-500' }}">
                                        {{ $pendingDeleteType }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs dark:text-slate-500 text-gray-400 font-body mb-1">Amount</p>
                                    <p class="text-sm font-semibold font-mono dark:text-white text-gray-900">
                                        {{ $currSymbol }}{{ $pendingDeleteAmount }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs dark:text-slate-500 text-gray-400 font-body mb-1">Date</p>
                                    <p class="text-sm font-semibold font-body dark:text-white text-gray-900">{{ $pendingDeleteDate }}</p>
                                </div>
                            </div>
                            @if($pendingDeleteDesc)
                                <div class="mt-3 pt-3 border-t dark:border-slate-700 border-gray-200">
                                    <p class="text-xs dark:text-slate-500 text-gray-400 font-body mb-1">Description</p>
                                    <p class="text-sm dark:text-slate-300 text-gray-700 font-body truncate">{{ $pendingDeleteDesc }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 px-6 py-4
                            border-t dark:border-slate-700/60 border-gray-100">
                    <button wire:click="deleteEntry"
                            wire:loading.attr="disabled"
                            data-autofocus
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-3
                                   border-2 border-red-500 text-red-500
                                   dark:hover:bg-red-500/10 hover:bg-red-50
                                   rounded-lg text-sm font-bold font-body transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                        </svg>
                        <span wire:loading.remove wire:target="deleteEntry">Yes, Delete</span>
                        <span wire:loading wire:target="deleteEntry">Deleting…</span>
                    </button>
                    <button wire:click="$set('showDeleteEntryModal', false)"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-3
                                   bg-primary hover:bg-accent text-white
                                   rounded-lg text-sm font-bold font-body transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ===== DELETE COMMENT CONFIRM MODAL ===== --}}
    @if($showDeleteCommentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showDeleteCommentModal', false)"></div>
            <div class="relative w-full max-w-sm dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 overflow-hidden"
                 x-data x-init="$el.querySelector('[data-autofocus]')?.focus()">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 pt-6 pb-4
                            border-b dark:border-slate-700/60 border-gray-100">
                    <h3 class="font-heading font-bold text-lg dark:text-white text-gray-900">Delete Comment</h3>
                    <button aria-label="Close" wire:click="$set('showDeleteCommentModal', false)"
                            class="w-8 h-8 flex items-center justify-center rounded-lg
                                   dark:text-slate-400 text-gray-400
                                   dark:hover:bg-slate-700 hover:bg-gray-100
                                   transition-colors duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">

                    {{-- Warning --}}
                    <div class="flex items-start gap-3 px-4 py-3.5 rounded-xl
                                bg-red-50 dark:bg-red-500/10
                                border border-red-200 dark:border-red-500/20">
                        <svg class="w-5 h-5 text-red-500 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        </svg>
                        <p class="text-sm dark:text-red-300 text-red-700 font-body leading-relaxed">
                            This comment will be <strong>permanently deleted</strong> and cannot be restored.
                        </p>
                    </div>

                    {{-- Comment excerpt --}}
                    @if($pendingDeleteCommentExcerpt)
                        <div class="rounded-xl dark:bg-slate-800 bg-gray-50 border dark:border-slate-700 border-gray-200 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-400 font-body mb-1.5">Comment</p>
                            <p class="text-sm dark:text-slate-300 text-gray-700 font-body italic">"{{ $pendingDeleteCommentExcerpt }}"</p>
                        </div>
                    @endif

                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 px-6 py-4
                            border-t dark:border-slate-700/60 border-gray-100">
                    <button wire:click="deleteComment"
                            wire:loading.attr="disabled"
                            data-autofocus
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-3
                                   border-2 border-red-500 text-red-500
                                   dark:hover:bg-red-500/10 hover:bg-red-50
                                   rounded-lg text-sm font-bold font-body transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                        </svg>
                        <span wire:loading.remove wire:target="deleteComment">Yes, Delete</span>
                        <span wire:loading wire:target="deleteComment">Deleting…</span>
                    </button>
                    <button wire:click="$set('showDeleteCommentModal', false)"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-3
                                   bg-primary hover:bg-accent text-white
                                   rounded-lg text-sm font-bold font-body transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ===== BULK DELETE CONFIRM MODAL ===== --}}
    @if($showBulkDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="$set('showBulkDeleteConfirm', false)"></div>
            <div class="relative w-full max-w-sm dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 p-6">
                <div class="w-12 h-12 rounded-xl bg-red-500/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                </div>
                <h3 class="font-heading font-bold text-base dark:text-white text-gray-900 text-center mb-1.5">
                    Delete <span x-text="selectedIds.length"></span> <span x-text="selectedIds.length === 1 ? 'entry' : 'entries'"></span>?
                </h3>
                <p class="text-sm dark:text-slate-400 text-gray-500 font-body text-center mb-6">
                    This action cannot be undone. The entries will be permanently removed.
                </p>
                <div class="flex items-center gap-3">
                    <button wire:click="$set('showBulkDeleteConfirm', false)"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button @click="$wire.bulkDelete(selectedIds)"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-red-500 text-white hover:bg-red-600 rounded-lg transition-colors
                                   disabled:opacity-50">
                        <span wire:loading.remove wire:target="bulkDelete">Delete</span>
                        <span wire:loading wire:target="bulkDelete">Deleting…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== BULK BOOK PICKER MODAL (Move / Copy) ===== --}}
    @if($showBulkBookPicker)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="$set('showBulkBookPicker', false)"></div>
            <div class="relative w-full max-w-sm dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 overflow-hidden">
                <div class="px-6 pt-6 pb-4">
                    <h3 class="font-heading font-bold text-base dark:text-white text-gray-900 mb-1">
                        @if($bulkAction === 'move') Move entries to…
                        @elseif($bulkAction === 'copy') Copy entries to…
                        @else Copy opposite entries to…
                        @endif
                    </h3>
                    <p class="text-xs dark:text-slate-500 text-gray-400 font-body">
                        Select a book in {{ $business->name }}
                    </p>
                </div>

                @php
                    $otherBooks = $business->books()
                        ->where('id', '!=', $book->id)
                        ->orderBy('name')
                        ->get(['id', 'name']);
                @endphp

                <div class="max-h-64 overflow-y-auto">
                    @forelse($otherBooks as $otherBook)
                        <button wire:click="$set('bulkTargetBookId', '{{ $otherBook->id }}')"
                                class="flex items-center gap-3 w-full px-6 py-3 text-left transition-colors
                                       {{ $bulkTargetBookId === $otherBook->id
                                           ? 'dark:bg-primary/10 bg-blue-50'
                                           : 'dark:hover:bg-slate-800/50 hover:bg-gray-50' }}">
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                        {{ $bulkTargetBookId === $otherBook->id
                                            ? 'border-primary'
                                            : 'dark:border-slate-600 border-gray-300' }}">
                                @if($bulkTargetBookId === $otherBook->id)
                                    <div class="w-2.5 h-2.5 rounded-full bg-primary"></div>
                                @endif
                            </div>
                            <span class="text-sm font-body dark:text-white text-gray-900 truncate">{{ $otherBook->name }}</span>
                        </button>
                    @empty
                        <div class="px-6 py-8 text-center">
                            <p class="text-sm dark:text-slate-500 text-gray-500 font-body">No other books in this business.</p>
                            <p class="text-xs dark:text-slate-600 text-gray-400 font-body mt-1">Create a new book first.</p>
                        </div>
                    @endforelse
                </div>

                <div class="px-6 py-4 dark:border-t dark:border-slate-700 border-t border-gray-200 flex items-center gap-3">
                    <button wire:click="$set('showBulkBookPicker', false)"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button @click="$wire.executeBulkBookAction(selectedIds)"
                            wire:loading.attr="disabled"
                            {{ $bulkTargetBookId === '' || $otherBooks->isEmpty() ? 'disabled' : '' }}
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-primary text-white hover:bg-accent rounded-xl transition-colors
                                   disabled:opacity-40 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="executeBulkBookAction">
                            @if($bulkAction === 'move') Move
                            @elseif($bulkAction === 'copy') Copy
                            @else Copy Opposite
                            @endif
                            <span x-text="selectedIds.length"></span> <span x-text="selectedIds.length === 1 ? 'Entry' : 'Entries'"></span>
                        </span>
                        <span wire:loading wire:target="executeBulkBookAction">Processing…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== BULK CHANGE CATEGORY MODAL ===== --}}
    @if($showBulkChangeCategory)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="$set('showBulkChangeCategory', false)"></div>
            <div class="relative w-full max-w-sm dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 overflow-hidden">
                <div class="px-6 pt-6 pb-4">
                    <h3 class="font-heading font-bold text-base dark:text-white text-gray-900 mb-1">Change Category</h3>
                    <p class="text-xs dark:text-slate-500 text-gray-400 font-body">
                        Update category on <span x-text="selectedIds.length"></span> <span x-text="selectedIds.length === 1 ? 'entry' : 'entries'"></span>
                    </p>
                </div>

                <div class="max-h-64 overflow-y-auto">
                    {{-- None option --}}
                    <button wire:click="$set('bulkNewCategory', '')"
                            class="flex items-center gap-3 w-full px-6 py-3 text-left transition-colors
                                   {{ $bulkNewCategory === '' ? 'dark:bg-primary/10 bg-blue-50' : 'dark:hover:bg-slate-800/50 hover:bg-gray-50' }}">
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                    {{ $bulkNewCategory === '' ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                            @if($bulkNewCategory === '')
                                <div class="w-2.5 h-2.5 rounded-full bg-primary"></div>
                            @endif
                        </div>
                        <span class="text-sm font-body dark:text-slate-400 text-gray-500 italic">None (clear category)</span>
                    </button>
                    @foreach($categories as $cat)
                        <button wire:click="$set('bulkNewCategory', '{{ $cat->name }}')"
                                class="flex items-center gap-3 w-full px-6 py-3 text-left transition-colors
                                       {{ $bulkNewCategory === $cat->name ? 'dark:bg-primary/10 bg-blue-50' : 'dark:hover:bg-slate-800/50 hover:bg-gray-50' }}">
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                        {{ $bulkNewCategory === $cat->name ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                @if($bulkNewCategory === $cat->name)
                                    <div class="w-2.5 h-2.5 rounded-full bg-primary"></div>
                                @endif
                            </div>
                            <span class="text-sm font-body dark:text-white text-gray-900 truncate">{{ $cat->name }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="px-6 py-4 dark:border-t dark:border-slate-700 border-t border-gray-200 flex items-center gap-3">
                    <button wire:click="$set('showBulkChangeCategory', false)"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button @click="$wire.bulkChangeCategory(selectedIds)"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-primary text-white hover:bg-accent rounded-lg transition-colors
                                   disabled:opacity-50">
                        <span wire:loading.remove wire:target="bulkChangeCategory">Apply</span>
                        <span wire:loading wire:target="bulkChangeCategory">Applying…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== BULK CHANGE PAYMENT MODE MODAL ===== --}}
    @if($showBulkChangePaymentMode)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="$set('showBulkChangePaymentMode', false)"></div>
            <div class="relative w-full max-w-sm dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 overflow-hidden">
                <div class="px-6 pt-6 pb-4">
                    <h3 class="font-heading font-bold text-base dark:text-white text-gray-900 mb-1">Change Payment Method</h3>
                    <p class="text-xs dark:text-slate-500 text-gray-400 font-body">
                        Update payment method on <span x-text="selectedIds.length"></span> <span x-text="selectedIds.length === 1 ? 'entry' : 'entries'"></span>
                    </p>
                </div>

                <div class="max-h-64 overflow-y-auto">
                    {{-- None option --}}
                    <button wire:click="$set('bulkNewPaymentMode', '')"
                            class="flex items-center gap-3 w-full px-6 py-3 text-left transition-colors
                                   {{ $bulkNewPaymentMode === '' ? 'dark:bg-primary/10 bg-blue-50' : 'dark:hover:bg-slate-800/50 hover:bg-gray-50' }}">
                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                    {{ $bulkNewPaymentMode === '' ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                            @if($bulkNewPaymentMode === '')
                                <div class="w-2.5 h-2.5 rounded-full bg-primary"></div>
                            @endif
                        </div>
                        <span class="text-sm font-body dark:text-slate-400 text-gray-500 italic">None (clear payment method)</span>
                    </button>
                    @foreach($paymentModes as $mode)
                        <button wire:click="$set('bulkNewPaymentMode', '{{ $mode->name }}')"
                                class="flex items-center gap-3 w-full px-6 py-3 text-left transition-colors
                                       {{ $bulkNewPaymentMode === $mode->name ? 'dark:bg-primary/10 bg-blue-50' : 'dark:hover:bg-slate-800/50 hover:bg-gray-50' }}">
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                        {{ $bulkNewPaymentMode === $mode->name ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                @if($bulkNewPaymentMode === $mode->name)
                                    <div class="w-2.5 h-2.5 rounded-full bg-primary"></div>
                                @endif
                            </div>
                            <span class="text-sm font-body dark:text-white text-gray-900 truncate">{{ $mode->name }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="px-6 py-4 dark:border-t dark:border-slate-700 border-t border-gray-200 flex items-center gap-3">
                    <button wire:click="$set('showBulkChangePaymentMode', false)"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button @click="$wire.bulkChangePaymentMode(selectedIds)"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-primary text-white hover:bg-accent rounded-lg transition-colors
                                   disabled:opacity-50">
                        <span wire:loading.remove wire:target="bulkChangePaymentMode">Apply</span>
                        <span wire:loading wire:target="bulkChangePaymentMode">Applying…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Save & Add New toast — wire:ignore prevents Livewire morphdom from resetting Alpine's display state --}}
    <div wire:ignore
         x-data="{ show: false, message: '' }"
         x-on:entry-saved.window="message = ($event.detail.message ?? $event.detail[0] ?? 'Entry saved.'); show = true; setTimeout(() => show = false, 2500)"
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-4 sm:bottom-6 left-4 right-4 sm:left-1/2 sm:right-auto sm:w-auto sm:-translate-x-1/2 flex items-center gap-3 px-5 py-3.5
                bg-slate-900 rounded-2xl shadow-2xl border border-white/10 sm:whitespace-nowrap"
         style="z-index: 9998; display: none;">
        <span class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0">
            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10.5 18.75 19.5 6.75"/>
            </svg>
        </span>
        <span class="text-sm font-body font-medium text-white" x-text="message"></span>
    </div>

    {{-- ===== ENTRY SLIDE-OVER ===== --}}
    <div x-data="{ show: $wire.entangle('showEntryPanel').live }"
         x-on:open-entry-panel.window="show = true"
         x-init="$watch('show', v => { if (v) setTimeout(() => document.getElementById('entry-amount')?.focus(), 320) })"
         x-on:entry-saved.window="if (show) setTimeout(() => document.getElementById('entry-amount')?.focus(), 50)"
         @keydown.escape.window="if (show) show = false">

    {{-- Backdrop --}}
    <div x-cloak
         x-show="show"
         @click="show = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-navy/70 backdrop-blur-sm z-40"></div>

    {{-- Panel: header + scrolling form + pinned save bar --}}
    <div x-cloak
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         role="dialog" aria-modal="true" aria-labelledby="entry-panel-title"
         class="fixed inset-y-0 right-0 w-full max-w-lg z-50 flex flex-col
                dark:bg-dark bg-white
                dark:border-l dark:border-slate-700 border-l border-gray-200
                shadow-2xl dark:shadow-black/60 shadow-black/20">

        {{-- Panel header --}}
        <div class="flex-shrink-0 px-6 pt-5 pb-4 border-b border-gray-100 dark:border-slate-700">
            <div class="flex items-center justify-between mb-4">
                <div class="min-w-0">
                    <h2 id="entry-panel-title" class="font-heading font-bold text-base dark:text-white text-gray-900">
                        {{ $editingEntryId ? 'Edit entry' : 'New entry' }}
                    </h2>
                    <p class="text-xs dark:text-slate-400 text-gray-500 font-body truncate">{{ $editingEntryId ? 'In' : 'Adding to' }} {{ $business->name }} › {{ $book->name }} · {{ $business->currency }}</p>
                </div>
                <button type="button" @click="show = false" aria-label="Close"
                        class="p-2 rounded-lg dark:text-slate-400 text-gray-500
                               dark:hover:bg-slate-800 hover:bg-gray-100 dark:hover:text-white hover:text-gray-700
                               transition-all duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Type toggle --}}
            <div class="grid grid-cols-2 gap-1.5 p-1.5 dark:bg-slate-900 bg-gray-100 rounded-xl" role="radiogroup" aria-label="Entry type">
                <button type="button" wire:click="$set('entryType', 'in')" role="radio" aria-checked="{{ $entryType === 'in' ? 'true' : 'false' }}"
                        class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold font-body transition-all duration-200
                               {{ $entryType === 'in'
                                   ? 'bg-emerald-600 text-white shadow-sm'
                                   : 'dark:text-slate-300 text-gray-600 dark:hover:text-white hover:text-gray-900' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Cash In
                </button>
                <button type="button" wire:click="$set('entryType', 'out')" role="radio" aria-checked="{{ $entryType === 'out' ? 'true' : 'false' }}"
                        class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold font-body transition-all duration-200
                               {{ $entryType === 'out'
                                   ? 'bg-red-600 text-white shadow-sm'
                                   : 'dark:text-slate-300 text-gray-600 dark:hover:text-white hover:text-gray-900' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                    </svg>
                    Cash Out
                </button>
            </div>
        </div>

        {{-- Scrolling form --}}
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">

            {{-- ── AI helpers: one compact row of chips (new entries only, non-viewer) ── --}}
            @if(!$editingEntryId && $userRole !== 'viewer')
                @php
                    // AI entries: Free = 10/month (scans + typed, shared); Pro = 200 scans/month.
                    // When a Free allowance is used up the chips route to prepareScan /
                    // parseEntryText, which open the upgrade modal server-side.
                    $aiLocked   = $aiQuota && ! $aiQuota['isPro'] && $aiQuota['exhausted'];
                    $aiResetsOn = $aiQuota ? \Carbon\Carbon::parse($aiQuota['resetsAt'])->format('M j') : null;
                @endphp
                <div x-data="nlpVoice()"
                     x-on:livewire:navigated.window="teardown()"
                     @open-ocr-picker.window="$refs.ocrInput && $refs.ocrInput.click()"
                     class="space-y-2">

                    {{-- OCR file input — visually hidden but click-able so mobile Safari/Chrome can open
                         the camera + gallery picker (display:none blocks .click() on some browsers).
                         Pro businesses only (the server also rejects Free uploads). --}}
                    @if(!$aiLocked)
                        <input type="file" id="entry-ocr-file"
                               wire:model="ocrFile"
                               accept="image/png,image/jpeg,image/jpg"
                               class="sr-only" tabindex="-1" aria-hidden="true"
                               x-ref="ocrInput">
                    @endif

                    <div class="flex items-center gap-1.5 flex-wrap" role="group" aria-label="Fill with AI">
                        <x-ai-sparkle class="w-4 h-4 text-primary dark:text-blue-light flex-shrink-0" />
                        @php
                            $chip = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold font-body transition-all duration-150
                                     border border-blue-200 dark:border-slate-700 bg-blue-50 dark:bg-slate-800 text-primary dark:text-blue-light
                                     hover:brightness-105 hover:shadow-sm disabled:opacity-50';
                        @endphp
                        {{-- Scan receipt: Pro users click the hidden input directly in the user gesture
                             (mobile browsers refuse a deferred .click()); Free users hit prepareScan() → upgrade modal. --}}
                        <button type="button" class="{{ $chip }}"
                                @if(!$aiLocked) @click="$refs.ocrInput.click()" @else wire:click="prepareScan" @endif
                                wire:loading.attr="disabled" wire:target="ocrFile">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/>
                            </svg>
                            Scan receipt
                        </button>
                        <button type="button" class="{{ $chip }}"
                                @if(!$aiLocked) @click="open = !open; $nextTick(() => open && $refs.nlpField?.focus())" @else wire:click="parseEntryText" @endif
                                :aria-expanded="open.toString()">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                            </svg>
                            Type it
                        </button>
                        <button type="button" class="{{ $chip }}" x-show="supported" x-cloak
                                @if(!$aiLocked) @click="open = true; $nextTick(() => toggle())" @else wire:click="parseEntryText" @endif
                                :aria-label="listening ? 'Stop listening' : 'Speak your entry'">
                            <svg x-show="!listening" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z"/>
                            </svg>
                            <span x-show="listening" x-cloak class="w-2.5 h-2.5 rounded-sm bg-red-500 animate-pulse" aria-hidden="true"></span>
                            <span x-text="listening ? 'Stop' : 'Speak'">Speak</span>
                        </button>
                        @if($aiLocked)
                            <x-pro-badge class="ml-auto" />
                        @endif
                    </div>

                    {{-- AI entry allowance — what's left and when it resets --}}
                    @if($aiQuota)
                        @php
                            $q      = $aiQuota;
                            $warnAt = (int) floor($q['limit'] * 0.2);
                            $tone   = $q['remaining'] === 0 ? 'empty' : ($q['remaining'] <= $warnAt ? 'warn' : 'ok');
                            $typedNear = $q['isPro'] && $q['typed']['remaining'] <= (int) floor($q['typed']['limit'] * 0.2);
                        @endphp
                        <p class="flex items-center gap-1.5 flex-wrap text-[11px] font-body
                                  {{ $tone === 'empty' ? 'text-red-600 dark:text-red-400' : ($tone === 'warn' ? 'text-amber-700 dark:text-amber-300' : 'dark:text-slate-400 text-gray-500') }}"
                           aria-live="polite">
                            @if(! $q['isPro'])
                                <span><span class="font-mono font-semibold">{{ $q['remaining'] }}</span> of <span class="font-mono">{{ $q['limit'] }}</span> free AI entries left this month</span>
                            @else
                                <span><span class="font-mono font-semibold">{{ $q['scans']['remaining'] }}</span> scans left this month</span>
                                @if($typedNear)
                                    <span aria-hidden="true">·</span>
                                    <span><span class="font-mono font-semibold">{{ $q['typed']['remaining'] }}</span> typed {{ $q['typed']['remaining'] === 1 ? 'entry' : 'entries' }} left today</span>
                                @endif
                            @endif
                            <span aria-hidden="true">·</span>
                            <span>resets {{ $aiResetsOn }}</span>
                            @if($aiLocked)
                                @if(auth()->id() === $business->owner_id)
                                    <a href="{{ route('billing') }}" class="ml-1 font-semibold underline underline-offset-2">Upgrade for 200 scans/month</a>
                                @else
                                    <span class="ml-1">— ask the owner to upgrade</span>
                                @endif
                            @endif
                        </p>
                    @endif

                    {{-- Type it / Speak panel --}}
                    @if(!$aiLocked)
                    <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="space-y-1.5">
                        <label for="entry-nlp" class="sr-only">Describe a transaction</label>
                        <div class="relative">
                            <input type="text" id="entry-nlp"
                                   x-ref="nlpField"
                                   wire:model="nlpInput"
                                   wire:keydown.enter.prevent="parseEntryText"
                                   :placeholder="listening ? 'Listening…' : 'e.g. Paid 5000 for office rent yesterday from the bank'"
                                   maxlength="500"
                                   class="w-full pl-3 pr-24 py-2.5 text-sm rounded-lg font-body
                                          dark:bg-slate-900 bg-white
                                          dark:border-slate-700 border border-gray-300
                                          dark:text-white text-gray-900
                                          dark:placeholder-slate-500 placeholder-gray-400
                                          focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                          transition-all"
                                   wire:loading.attr="disabled" wire:target="parseEntryText">
                            <button type="button"
                                    wire:click="parseEntryText"
                                    wire:loading.attr="disabled" wire:target="parseEntryText"
                                    class="absolute right-1 top-1/2 -translate-y-1/2 px-3 py-1.5 rounded-md text-xs font-semibold font-body
                                           bg-primary text-white hover:brightness-110 transition-all disabled:opacity-50 disabled:cursor-not-allowed
                                           inline-flex items-center gap-1.5">
                                <svg wire:loading wire:target="parseEntryText" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-30"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                <span wire:loading.remove wire:target="parseEntryText">Fill in</span>
                                <span wire:loading wire:target="parseEntryText">…</span>
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Voice status --}}
                    <div x-show="voiceStatus" x-cloak class="text-[11px] font-body dark:text-slate-300 text-gray-600 flex items-center gap-1.5" aria-live="polite">
                        <svg x-show="listening" class="w-3 h-3 text-red-500 flex-shrink-0" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true"><circle cx="6" cy="6" r="3"/></svg>
                        <span x-text="voiceStatus" class="flex-1"></span>
                        <button type="button" x-show="!listening" @click="dismissVoiceStatus()" class="flex-shrink-0 p-1 rounded hover:bg-gray-100 dark:hover:bg-slate-800" aria-label="Dismiss">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Working states --}}
                    <div wire:loading.flex wire:target="parseEntryText" class="items-center gap-2 text-[11px] font-body text-primary dark:text-blue-light animate-pulse">
                        Understanding your transaction…
                    </div>
                    <div wire:loading.flex wire:target="ocrFile"
                         class="relative overflow-hidden items-center gap-3 rounded-lg border border-blue-200 dark:border-slate-700 bg-blue-50 dark:bg-slate-800 px-3 py-2.5">
                        <x-ai-sparkle class="w-4 h-4 text-primary dark:text-blue-light animate-pulse flex-shrink-0" />
                        <p class="text-xs font-semibold font-body text-primary dark:text-blue-light">Reading your receipt…</p>
                    </div>

                    {{-- Results --}}
                    <div wire:loading.remove wire:target="ocrFile,parseEntryText" aria-live="polite">
                        @if($scanError)
                            <div class="flex items-start gap-2 rounded-lg border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-500/10 px-3 py-2.5">
                                <p class="flex-1 text-xs font-body text-red-700 dark:text-red-300"><span class="font-semibold">Scan failed.</span> {{ $scanError }}</p>
                                <button wire:click="clearOcrScan" type="button" aria-label="Dismiss scan error"
                                        class="p-1 rounded text-red-600 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-500/20 flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @elseif(!empty($aiFilledFields))
                            <div class="flex items-center gap-2 rounded-lg border border-blue-200 dark:border-slate-700 bg-blue-50 dark:bg-slate-800 px-3 py-2.5">
                                <x-ai-sparkle class="w-4 h-4 text-primary dark:text-blue-light flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold font-body text-primary dark:text-blue-light">
                                        Filled {{ count($aiFilledFields) }} {{ count($aiFilledFields) === 1 ? 'field' : 'fields' }} from your receipt — check before saving
                                    </p>
                                    @if($ocrOriginalAmount && $ocrConvertedAt)
                                        <p class="text-[11px] font-mono dark:text-slate-300 text-gray-600 mt-0.5">
                                            {{ $ocrOriginalAmount }} → {{ $business->currency }} {{ number_format((float)$entryAmount, 2) }} · {{ $ocrConvertedAt }}
                                        </p>
                                    @endif
                                </div>
                                <button wire:click="clearOcrScan" type="button"
                                        class="text-xs font-semibold font-body text-primary dark:text-blue-light underline underline-offset-2 flex-shrink-0">
                                    Scan another
                                </button>
                            </div>
                        @endif

                        @if(!empty($nlpFilledFields))
                            <p class="text-[11px] font-body text-primary dark:text-blue-light flex items-center gap-1.5"
                               x-data="{s:true}" x-init="setTimeout(()=>s=false, 4500)" x-show="s" x-transition>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                Filled {{ count($nlpFilledFields) }} field{{ count($nlpFilledFields) === 1 ? '' : 's' }}. Review and save.
                            </p>
                        @endif

                        @if($nlpError)
                            <div class="flex items-start gap-1.5 text-[11px] font-body text-amber-700 dark:text-amber-300">
                                <span class="flex-1">{{ $nlpError }}</span>
                                <button type="button" wire:click="clearNlpError" class="text-[11px] underline">Dismiss</button>
                            </div>
                        @endif

                        @error('ocrFile')
                            <p class="text-xs text-red-600 dark:text-red-400 font-body">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endif

            {{-- Amount (first, large) --}}
            <div>
                <label for="entry-amount" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-600 font-body mb-1.5">
                    Amount <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                    @if(in_array('amount', $aiFilledFields))@include('livewire.book.partials.ai-filled-badge')@endif
                </label>
                <div class="flex">
                    <div class="flex items-center px-3.5 dark:bg-slate-800 bg-gray-100 border border-gray-300 dark:border-slate-700 border-r-0 rounded-l-lg">
                        <span class="text-sm font-mono dark:text-slate-300 text-gray-600 font-semibold">{{ $business->currency }}</span>
                    </div>
                    <input type="number" id="entry-amount"
                           wire:model="entryAmount"
                           wire:keydown.enter.prevent="saveEntry"
                           placeholder="0.00"
                           step="0.01" min="0.01" inputmode="decimal" required
                           aria-required="true"
                           @error('entryAmount') aria-invalid="true" aria-describedby="entry-amount-error" @enderror
                           class="flex-1 min-w-0 px-4 py-3 font-mono text-2xl font-semibold tabular-nums
                                  dark:bg-slate-800 bg-white
                                  border border-gray-300 dark:border-slate-700
                                  dark:text-white text-gray-900 rounded-r-lg
                                  placeholder:dark:text-slate-600 placeholder:text-gray-300 placeholder:font-normal
                                  focus:outline-none focus:ring-2
                                  {{ $entryType === 'in' ? 'focus:ring-emerald-500/30 focus:border-emerald-500' : 'focus:ring-red-500/30 focus:border-red-500' }}
                                  transition-all duration-150">
                </div>
                @error('entryAmount')<p id="entry-amount-error" class="text-xs text-red-600 dark:text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

            {{-- Description (optional) --}}
            <div>
                <label for="entry-description" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-600 font-body mb-1.5">
                    Description <span class="normal-case tracking-normal font-normal dark:text-slate-400 text-gray-500">(optional)</span>
                    @if(in_array('description', $aiFilledFields))@include('livewire.book.partials.ai-filled-badge')@endif
                </label>
                <input type="text" id="entry-description"
                       wire:model="entryDescription"
                       wire:blur="suggestCategory"
                       wire:keydown.enter.prevent="saveEntry"
                       placeholder="e.g. Client payment, Office rent…"
                       maxlength="255"
                       @error('entryDescription') aria-invalid="true" aria-describedby="entry-description-error" @enderror
                       class="w-full px-4 py-2.5 text-sm font-body
                              dark:bg-slate-800 bg-white
                              border border-gray-300 dark:border-slate-700
                              dark:text-white text-gray-900 rounded-lg
                              placeholder:dark:text-slate-500 placeholder:text-gray-400
                              focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150">
                @error('entryDescription')<p id="entry-description-error" class="text-xs text-red-600 dark:text-red-400 mt-1 font-body">{{ $message }}</p>@enderror

                {{-- AI category suggestion chip --}}
                @if($showCategoryChip && $aiCategorySuggestion)
                    <div class="mt-2 flex items-center gap-2" aria-live="polite">
                        <span class="text-[11px] dark:text-slate-400 text-gray-500 font-body flex-shrink-0">Suggested category:</span>
                        <button type="button"
                                wire:click="applyAiCategory"
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold font-body
                                       bg-blue-50 dark:bg-slate-800 text-primary dark:text-blue-light
                                       border border-blue-200 dark:border-slate-700 hover:brightness-105 transition-all duration-150">
                            <x-ai-sparkle class="w-3 h-3" />
                            {{ $aiCategorySuggestion }}
                        </button>
                        <button type="button"
                                wire:click="dismissCategoryChip"
                                aria-label="Dismiss suggestion"
                                class="p-1 rounded dark:text-slate-400 text-gray-500 dark:hover:text-white hover:text-gray-700 transition-colors ml-auto">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>

            {{-- Date --}}
            <div>
                <label for="entry-date" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-600 font-body mb-1.5">
                    Date
                    @if(in_array('date', $aiFilledFields))@include('livewire.book.partials.ai-filled-badge')@endif
                </label>
                <div wire:ignore
                     x-data="{
                         fp: null,
                         init() {
                             this.fp = flatpickr(this.$refs.picker, {
                                 dateFormat: 'Y-m-d',
                                 defaultDate: $wire.entryDate || null,
                                 disableMobile: true,
                                 onChange: (dates, str) => { $wire.set('entryDate', str) }
                             })
                         }
                     }"
                     x-on:entry-date-updated.window="fp && fp.setDate($event.detail.date, false)">
                    <input x-ref="picker" id="entry-date" type="text" placeholder="Select date" readonly
                           class="w-full px-4 py-2.5 text-sm font-body cursor-pointer
                                  dark:bg-slate-800 bg-white
                                  border border-gray-300 dark:border-slate-700
                                  dark:text-slate-200 text-gray-700 dark:placeholder-slate-500 placeholder-gray-400 rounded-lg
                                  focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                  transition-all duration-150">
                </div>
                @error('entryDate')<p class="text-xs text-red-600 dark:text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

            {{-- ── More details (category, payment method, reference, attachment, repeat) ── --}}
            @php
                $moreSummary = array_values(array_filter([
                    $entryCategory ?: null,
                    $entryPaymentMode ?: null,
                    $entryReference ? '#' . $entryReference : null,
                    ($entryAttachment || ($existingAttachmentPath && !$removeAttachment)) ? 'Attachment' : null,
                    $entryRecurring ? 'Repeats' : null,
                ]));
            @endphp
            <div x-data="{ more: $wire.entangle('showMoreDetails') }"
                 class="rounded-xl border border-gray-200 dark:border-slate-700">
                @if($errors->hasAny(['entryCategory', 'entryPaymentMode', 'entryReference', 'entryAttachment', 'entryEndsAt', 'entryFrequency']))
                    <span x-init="more = true" class="hidden"></span>
                @endif
                <button type="button" @click="more = !more" :aria-expanded="more.toString()" aria-controls="entry-more-details"
                        class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-lg">
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-semibold font-body dark:text-slate-200 text-gray-800">More details</span>
                        <span class="block text-xs font-body dark:text-slate-400 text-gray-500 truncate">
                            {{ $moreSummary ? implode(' · ', $moreSummary) : 'Category, payment method, reference, attachment, repeat' }}
                        </span>
                    </span>
                    <svg class="w-4 h-4 dark:text-slate-400 text-gray-500 flex-shrink-0 transition-transform duration-200" :class="more ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div id="entry-more-details" x-show="more" x-cloak x-collapse.duration.200ms class="px-4 pb-4 space-y-4">
            {{-- Category --}}
            <div>
                <label for="entry-category" class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-600 font-body mb-1.5 flex items-center gap-2">
                    Category
                    @if(in_array('category', $aiFilledFields))@include('livewire.book.partials.ai-filled-badge')@endif
                </label>
                @if($showAddCategory)
                    <div class="dark:bg-slate-800 bg-gray-50 rounded-xl p-3 space-y-2 dark:border dark:border-slate-700 border border-gray-200">
                        <p class="text-xs font-semibold dark:text-slate-300 text-gray-700 font-body">Add New Category</p>
                        <input type="text"
                               wire:model="newCategoryName"
                               wire:keydown.enter="addCategory"
                               placeholder="e.g. Expenses, Staff Salary, Utilities"
                               autofocus
                               class="w-full px-3 py-2 text-sm font-body
                                      dark:bg-slate-900 bg-white
                                      dark:border dark:border-slate-600 border border-gray-300
                                      dark:text-white text-gray-900 rounded-lg
                                      placeholder:dark:text-slate-600 placeholder:text-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <div class="flex gap-2">
                            <button wire:click="$set('showAddCategory', false)"
                                    class="flex-1 py-1.5 text-xs font-semibold font-body dark:bg-slate-700 bg-gray-200 dark:text-slate-300 text-gray-600 rounded-lg hover:dark:bg-slate-600 transition-colors">
                                Cancel
                            </button>
                            <button wire:click="addCategory"
                                    class="flex-1 py-1.5 text-xs font-semibold font-body bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                                Save
                            </button>
                        </div>
                    </div>
                @else
                    <div x-data="{ open: false, search: '' }" class="relative">
                        <button type="button" id="entry-category" aria-haspopup="listbox" :aria-expanded="open.toString()"
                                @click="open = !open"
                                @click.outside="open = false; search = ''"
                                class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-body
                                       dark:bg-slate-800 bg-white
                                       dark:border dark:border-slate-700 border border-gray-300
                                       dark:text-white text-gray-900 rounded-lg
                                       focus:outline-none transition-all duration-150
                                       {{ $entryCategory ? '' : 'dark:text-slate-500 text-gray-400' }}">
                            <span>{{ $entryCategory ?: 'Select category' }}</span>
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute bottom-full left-0 right-0 mb-1 z-20
                                    dark:bg-slate-800 bg-white
                                    dark:border dark:border-slate-700 border border-gray-200
                                    rounded-xl shadow-2xl shadow-black/30 overflow-hidden"
                             style="display:none;">
                            <div class="p-2 dark:border-b dark:border-slate-700 border-b border-gray-100">
                                <input x-model="search" @click.stop type="text" placeholder="Search categories…"
                                       class="w-full px-3 py-1.5 text-sm font-body
                                              dark:bg-slate-800 bg-gray-50
                                              dark:border dark:border-slate-600 border border-gray-200
                                              dark:text-white text-gray-900 rounded-lg
                                              placeholder:dark:text-slate-600 placeholder:text-gray-400
                                              focus:outline-none focus:ring-1 focus:ring-primary/40">
                            </div>
                            <div class="max-h-44 overflow-y-auto py-1">
                                @if($categories->isEmpty())
                                    <p class="text-xs dark:text-slate-400 text-gray-500 text-center py-4 font-body">No categories yet</p>
                                @else
                                    @foreach($categories as $cat)
                                        <button type="button"
                                                x-show="search === '' || '{{ strtolower(addslashes($cat->name)) }}'.includes(search.toLowerCase())"
                                                @click.stop="$wire.set('entryCategory', '{{ addslashes($cat->name) }}'); $wire.set('showCategoryChip', false); $wire.set('aiCategorySuggestion', ''); open = false; search = ''"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors text-left
                                                       {{ $entryCategory === $cat->name ? 'dark:text-white text-gray-900 font-semibold' : 'dark:text-slate-300 text-gray-700' }}">
                                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                                        {{ $entryCategory === $cat->name ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                                @if($entryCategory === $cat->name)
                                                    <div class="w-2 h-2 rounded-full bg-primary"></div>
                                                @endif
                                            </div>
                                            {{ $cat->name }}
                                        </button>
                                    @endforeach
                                @endif
                            </div>
                            <div class="dark:border-t dark:border-slate-700 border-t border-gray-100">
                                <button type="button"
                                        @click.stop="$wire.set('showAddCategory', true); open = false"
                                        class="w-full flex items-center gap-2 px-4 py-3 text-sm font-semibold font-body text-primary
                                               dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                    Add New Category
                                </button>
                            </div>
                        </div>
                    </div>
                    @if($entryCategory)
                        <button wire:click="$set('entryCategory', '')" class="mt-1 text-[11px] dark:text-slate-400 text-gray-500 hover:text-red-400 font-body transition-colors">
                            Clear selection
                        </button>
                    @endif
                @endif
            </div>

            {{-- Payment method --}}
            <div>
                <label for="entry-payment-method" class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-600 font-body mb-1.5 flex items-center gap-2">
                    Payment method
                    @if(in_array('payment_mode', $aiFilledFields))@include('livewire.book.partials.ai-filled-badge')@endif
                </label>
                @if($showAddPaymentMode)
                    <div class="dark:bg-slate-800 bg-gray-50 rounded-xl p-3 space-y-2 dark:border dark:border-slate-700 border border-gray-200">
                        <p class="text-xs font-semibold dark:text-slate-300 text-gray-700 font-body">Add New Payment Method</p>
                        <input type="text"
                               wire:model="newPaymentModeName"
                               wire:keydown.enter="addPaymentMode"
                               placeholder="e.g. Cash, Online, Bank Transfer"
                               autofocus
                               class="w-full px-3 py-2 text-sm font-body
                                      dark:bg-slate-900 bg-white
                                      dark:border dark:border-slate-600 border border-gray-300
                                      dark:text-white text-gray-900 rounded-lg
                                      placeholder:dark:text-slate-600 placeholder:text-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <div class="flex gap-2">
                            <button wire:click="$set('showAddPaymentMode', false)"
                                    class="flex-1 py-1.5 text-xs font-semibold font-body dark:bg-slate-700 bg-gray-200 dark:text-slate-300 text-gray-600 rounded-lg hover:dark:bg-slate-600 transition-colors">
                                Cancel
                            </button>
                            <button wire:click="addPaymentMode"
                                    class="flex-1 py-1.5 text-xs font-semibold font-body bg-primary text-white rounded-lg hover:bg-accent transition-colors">
                                Save
                            </button>
                        </div>
                    </div>
                @else
                    <div x-data="{ open: false, search: '' }" class="relative">
                        <button type="button" id="entry-payment-method" aria-haspopup="listbox" :aria-expanded="open.toString()"
                                @click="open = !open"
                                @click.outside="open = false; search = ''"
                                class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-body
                                       dark:bg-slate-800 bg-white
                                       dark:border dark:border-slate-700 border border-gray-300
                                       dark:text-white text-gray-900 rounded-lg
                                       focus:outline-none transition-all duration-150
                                       {{ $entryPaymentMode ? '' : 'dark:text-slate-500 text-gray-400' }}">
                            <span>{{ $entryPaymentMode ?: 'Select payment method' }}</span>
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute bottom-full left-0 right-0 mb-1 z-20
                                    dark:bg-slate-800 bg-white
                                    dark:border dark:border-slate-700 border border-gray-200
                                    rounded-xl shadow-2xl shadow-black/30 overflow-hidden"
                             style="display:none;">
                            <div class="p-2 dark:border-b dark:border-slate-700 border-b border-gray-100">
                                <input x-model="search" @click.stop type="text" placeholder="Search payment methods…"
                                       class="w-full px-3 py-1.5 text-sm font-body
                                              dark:bg-slate-800 bg-gray-50
                                              dark:border dark:border-slate-600 border border-gray-200
                                              dark:text-white text-gray-900 rounded-lg
                                              placeholder:dark:text-slate-600 placeholder:text-gray-400
                                              focus:outline-none focus:ring-1 focus:ring-primary/40">
                            </div>
                            <div class="max-h-44 overflow-y-auto py-1">
                                @if($paymentModes->isEmpty())
                                    <p class="text-xs dark:text-slate-400 text-gray-500 text-center py-4 font-body">No payment methods yet</p>
                                @else
                                    @foreach($paymentModes as $mode)
                                        <button type="button"
                                                x-show="search === '' || '{{ strtolower(addslashes($mode->name)) }}'.includes(search.toLowerCase())"
                                                @click.stop="$wire.set('entryPaymentMode', '{{ addslashes($mode->name) }}'); open = false; search = ''"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors text-left
                                                       {{ $entryPaymentMode === $mode->name ? 'dark:text-white text-gray-900 font-semibold' : 'dark:text-slate-300 text-gray-700' }}">
                                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                                        {{ $entryPaymentMode === $mode->name ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                                @if($entryPaymentMode === $mode->name)
                                                    <div class="w-2 h-2 rounded-full bg-primary"></div>
                                                @endif
                                            </div>
                                            {{ $mode->name }}
                                        </button>
                                    @endforeach
                                @endif
                            </div>
                            <div class="dark:border-t dark:border-slate-700 border-t border-gray-100">
                                <button type="button"
                                        @click.stop="$wire.set('showAddPaymentMode', true); open = false"
                                        class="w-full flex items-center gap-2 px-4 py-3 text-sm font-semibold font-body text-primary
                                               dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                    Add New Payment Method
                                </button>
                            </div>
                        </div>
                    </div>
                    @if($entryPaymentMode)
                        <button wire:click="$set('entryPaymentMode', '')" class="mt-1 text-[11px] dark:text-slate-400 text-gray-500 hover:text-red-400 font-body transition-colors">
                            Clear selection
                        </button>
                    @endif
                @endif
            </div>

            {{-- Reference (optional) --}}
            <div>
                <label for="entry-reference" class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-600 font-body mb-1.5">
                    Reference
                    <span class="normal-case tracking-normal font-normal dark:text-slate-400 text-gray-500 ml-1">· optional</span>
                </label>
                <input type="text" id="entry-reference"
                       wire:model="entryReference"
                       placeholder="Invoice, receipt, or PO number"
                       maxlength="100"
                       class="w-full px-4 py-2.5 text-sm font-mono
                              dark:bg-slate-800 bg-white
                              dark:border dark:border-slate-700 border border-gray-300
                              dark:text-white text-gray-900 rounded-lg
                              placeholder:dark:text-slate-600 placeholder:text-gray-400 placeholder:font-body
                              focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150">
                @error('entryReference')<p class="text-xs text-red-600 dark:text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

            {{-- Attachment (receipt / invoice) --}}
            @if($userRole !== 'viewer')
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-600 font-body mb-1.5">
                        Attachment
                        <span class="normal-case tracking-normal font-normal dark:text-slate-400 text-gray-500 ml-1">· optional · max 2MB</span>
                    </label>

                    {{-- Existing attachment (when editing) --}}
                    @if($existingAttachmentPath && !$removeAttachment)
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                                    dark:bg-slate-800 bg-gray-50
                                    dark:border dark:border-slate-700 border border-gray-200">
                            @php $ext = strtolower(pathinfo($existingAttachmentPath, PATHINFO_EXTENSION)); @endphp
                            @if($ext === 'pdf')
                                <svg class="w-5 h-5 flex-shrink-0 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 flex-shrink-0 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/>
                                </svg>
                            @endif
                            <span class="text-sm dark:text-slate-300 text-gray-700 font-body truncate flex-1">
                                {{ basename($existingAttachmentPath) }}
                            </span>
                            <button type="button"
                                    wire:click="removeExistingAttachment"
                                    class="p-1 rounded-lg dark:text-slate-500 text-gray-400
                                           dark:hover:bg-red-500/10 hover:bg-red-50
                                           dark:hover:text-red-400 hover:text-red-500 transition-colors flex-shrink-0"
                                    title="Remove attachment" aria-label="Remove attachment">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @else
                        {{-- New upload / replace --}}
                        @if($entryAttachment)
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg
                                        dark:bg-slate-800 bg-emerald-50
                                        dark:border dark:border-emerald-500/30 border border-emerald-200">
                                <svg class="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                </svg>
                                <span class="text-sm dark:text-slate-200 text-emerald-700 font-body truncate flex-1">
                                    {{ $entryAttachment->getClientOriginalName() }}
                                </span>
                                <button type="button"
                                        wire:click="clearNewAttachment"
                                        class="p-1 rounded-lg dark:text-slate-500 text-gray-400
                                               dark:hover:bg-red-500/10 hover:bg-red-50
                                               dark:hover:text-red-400 hover:text-red-500 transition-colors flex-shrink-0"
                                        title="Remove" aria-label="Remove new attachment">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @else
                            <label class="group flex items-center gap-3 px-4 py-3 rounded-lg cursor-pointer focus-within:ring-2 focus-within:ring-primary
                                          dark:bg-slate-800 bg-white
                                          dark:border dark:border-slate-700 border border-gray-300 border-dashed
                                          dark:hover:border-primary/50 hover:border-primary/40
                                          transition-all duration-150">
                                <svg class="w-5 h-5 dark:text-slate-400 text-gray-500 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                                </svg>
                                <span class="text-sm dark:text-slate-500 text-gray-400 font-body group-hover:dark:text-slate-400 group-hover:text-gray-500 transition-colors">
                                    Attach receipt or invoice
                                </span>
                                <input type="file" id="entry-attachment"
                                       wire:model="entryAttachment"
                                       accept=".png,.jpg,.jpeg,.pdf"
                                       class="sr-only">
                            </label>
                        @endif
                    @endif

                    {{-- Upload progress --}}
                    <div wire:loading wire:target="entryAttachment" class="mt-2">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                            <span class="text-xs dark:text-slate-500 text-gray-400 font-body">Uploading...</span>
                        </div>
                    </div>

                    @error('entryAttachment')
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1 font-body">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- Recurring toggle (new entries only, non-viewer) --}}
            @if(!$editingEntryId && $userRole !== 'viewer')
                <div class="border-t dark:border-slate-700 border-gray-200 pt-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span id="entry-repeat-label" class="text-sm font-body font-medium dark:text-slate-200 text-gray-700">Repeat this entry</span>
                                @if(!$business->isPro())
                                    <x-pro-badge />
                                @endif
                            </div>
                            <p class="text-[11px] dark:text-slate-400 text-gray-500 font-body mt-0.5">Repeats within this book only</p>
                        </div>
                        <button type="button"
                                wire:click="toggleRecurring"
                                wire:loading.attr="disabled"
                                wire:target="toggleRecurring"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none
                                       {{ $entryRecurring ? 'bg-primary' : 'dark:bg-slate-700 bg-gray-200' }}"
                                role="switch" aria-labelledby="entry-repeat-label"
                                aria-checked="{{ $entryRecurring ? 'true' : 'false' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                         {{ $entryRecurring ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </div>

                    @if($entryRecurring)
                        <div class="mt-4 space-y-4">
                            {{-- Frequency selector --}}
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-600 font-body mb-2">Frequency</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'biweekly' => 'Bi-weekly'] as $freqVal => $freqLabel)
                                        <button type="button"
                                                wire:click="$set('entryFrequency', '{{ $freqVal }}')"
                                                class="px-3.5 py-2 rounded-lg text-sm font-body font-medium transition-all duration-150
                                                       {{ $entryFrequency === $freqVal
                                                           ? 'bg-primary/10 border-primary/50 text-primary ring-2 ring-primary/30 border'
                                                           : 'dark:border-slate-700 border-gray-300 dark:text-slate-400 text-gray-500 border dark:hover:border-slate-600 hover:border-gray-400' }}">
                                            {{ $freqLabel }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- End date --}}
                            <div x-data="{ forever: $wire.entangle('entryRunForever').live }"
                                 x-on:entry-date-updated.window="
                                     if ($refs.endPicker && $refs.endPicker._flatpickr) {
                                         // keep end picker in sync when slide-over opens
                                     }
                                 ">
                                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-600 font-body mb-1.5">End Date</label>
                                <div x-show="!forever" wire:ignore
                                     x-data="{
                                         fp: null,
                                         init() {
                                             this.fp = flatpickr(this.$refs.endPicker, {
                                                 dateFormat: 'Y-m-d',
                                                 defaultDate: $wire.entryEndsAt || null,
                                                 disableMobile: true,
                                                 onChange: (dates, str) => { $wire.set('entryEndsAt', str) }
                                             })
                                         }
                                     }">
                                    <input x-ref="endPicker" type="text" placeholder="Select end date" readonly
                                           class="w-full max-w-[200px] px-4 py-2.5 text-sm font-body cursor-pointer
                                                  dark:bg-slate-800 bg-white
                                                  dark:border dark:border-slate-700 border border-gray-300
                                                  dark:text-slate-300 text-gray-700 dark:placeholder-slate-600 placeholder-gray-400 rounded-lg
                                                  focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                                  transition-all duration-150">
                                </div>
                                {{-- Run forever checkbox --}}
                                <label class="flex items-center gap-2 mt-2 cursor-pointer select-none group">
                                    <input type="checkbox"
                                           wire:model.live="entryRunForever"
                                           class="w-4 h-4 rounded border-gray-300 dark:border-slate-600 text-primary focus:ring-primary/30 dark:bg-slate-800 cursor-pointer">
                                    <span class="text-xs font-body dark:text-slate-400 text-gray-500 group-hover:dark:text-slate-300 group-hover:text-gray-700 transition-colors">Run until I stop it</span>
                                </label>
                            </div>
                        </div>
                    @endif

                </div>
            @endif

                </div>
            </div>

        </div>

        {{-- Save bar — pinned to the bottom of the panel --}}
        <div class="flex-shrink-0 px-6 py-4 flex gap-2 pb-[calc(1rem+env(safe-area-inset-bottom))]
                    dark:bg-dark bg-white border-t border-gray-100 dark:border-slate-700">

            @if(!$editingEntryId)
                <button type="button"
                        wire:click="saveAndAddNew"
                        wire:loading.attr="disabled"
                        class="flex-1 py-2.5 text-sm font-semibold font-body
                               dark:bg-slate-800 bg-gray-100 dark:text-slate-200 text-gray-700
                               dark:hover:bg-slate-700 hover:bg-gray-200
                               rounded-lg transition-all duration-200 disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveAndAddNew">Save &amp; add another</span>
                    <span wire:loading wire:target="saveAndAddNew">Saving…</span>
                </button>
            @else
                <button type="button"
                        @click="show = false"
                        class="flex-1 py-2.5 text-sm font-semibold font-body
                               dark:bg-slate-800 bg-gray-100 dark:text-slate-200 text-gray-700
                               dark:hover:bg-slate-700 hover:bg-gray-200
                               rounded-lg transition-all duration-200">
                    Cancel
                </button>
            @endif

            <button type="button"
                    wire:click="saveEntry"
                    wire:loading.attr="disabled"
                    class="flex-1 py-2.5 text-sm font-semibold font-body
                           text-white rounded-lg transition-all duration-200 hover:brightness-110 hover:shadow-lg
                           {{ $entryType === 'in' ? 'bg-emerald-600 shadow-emerald-600/20' : 'bg-red-600 shadow-red-600/20' }}
                           disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="saveEntry">
                    {{ $editingEntryId ? 'Save changes' : ($entryType === 'in' ? 'Save cash in' : 'Save cash out') }}
                </span>
                <span wire:loading wire:target="saveEntry" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Saving…
                </span>
            </button>

        </div>

    </div>

    </div>{{-- end entangle wrapper --}}

</div>
