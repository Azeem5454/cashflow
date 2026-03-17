<div class="min-h-full"
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
    <div class="sticky top-0 z-30 px-6 lg:px-8 py-4
                dark:bg-navy/95 bg-white/95 backdrop-blur-md
                dark:border-b dark:border-slate-800 border-b border-gray-200/80">
        <div class="max-w-5xl mx-auto flex items-center gap-4">

            <a href="{{ route('businesses.show', $business) }}" wire:navigate
               class="p-2 rounded-xl dark:text-slate-500 text-gray-400
                      dark:hover:bg-slate-800 hover:bg-gray-100
                      dark:hover:text-white hover:text-gray-700
                      transition-all duration-150 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>

            <div class="flex-1 min-w-0">
                <h1 class="font-display font-extrabold text-xl dark:text-white text-gray-900 tracking-tight leading-none truncate">
                    {{ $book->name }}
                </h1>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    <span class="text-xs dark:text-slate-500 text-gray-400 font-body">{{ $business->name }}</span>
                    <span class="w-1 h-1 rounded-full dark:bg-slate-700 bg-gray-300 flex-shrink-0"></span>
                    <span class="text-xs font-mono dark:text-slate-500 text-gray-400 uppercase tracking-wider">{{ $business->currency }}</span>
                    @if($book->period_starts_at || $book->period_ends_at)
                        <span class="w-1 h-1 rounded-full dark:bg-slate-700 bg-gray-300 flex-shrink-0"></span>
                        <span class="text-xs dark:text-slate-500 text-gray-400 font-body">
                            @if($book->period_starts_at && $book->period_ends_at)
                                {{ $book->period_starts_at->format('d M') }} – {{ $book->period_ends_at->format('d M Y') }}
                            @elseif($book->period_starts_at)
                                from {{ $book->period_starts_at->format('d M Y') }}
                            @else
                                until {{ $book->period_ends_at->format('d M Y') }}
                            @endif
                        </span>
                    @endif
                    @if($userRole !== 'owner')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider
                                     {{ $userRole === 'editor'
                                         ? 'dark:bg-blue-500/10 dark:text-blue-400 bg-blue-50 text-blue-600'
                                         : 'dark:bg-slate-800 dark:text-slate-500 bg-gray-100 text-gray-500' }}">
                            {{ $userRole }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Export dropdown --}}
            <div x-data="{ exportOpen: false }" class="relative flex-shrink-0">
                <button @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                        title="Export"
                        class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-semibold font-body
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

                    {{-- Pro badge --}}
                    <div class="px-4 py-2 border-b dark:border-slate-700 border-gray-100">
                        <span class="text-[10px] font-semibold uppercase tracking-wider
                                     dark:text-slate-500 text-gray-400 font-body">Export Book</span>
                        <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide
                                     bg-primary/10 text-primary">Pro</span>
                    </div>

                    <button @click="$wire.exportPdf(); exportOpen = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                   dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        Export as PDF
                    </button>

                    <button @click="$wire.exportCsv(); exportOpen = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                   dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
                            class="p-2 rounded-xl dark:text-slate-500 text-gray-400
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

                        <button @click="$wire.openRenameBook(); open = false"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                            </svg>
                            Rename Book
                        </button>

                        <button @click="$wire.duplicateBook(); open = false"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                       dark:text-slate-300 text-gray-700
                                       dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
                            </svg>
                            Duplicate Book
                        </button>

                        @if($userRole === 'owner')
                            <div class="my-1 dark:border-t dark:border-slate-700 border-t border-gray-100"></div>
                            <button @click="$wire.openDeleteBook(); open = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                           text-red-400
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

    {{-- ===== RENAME BOOK MODAL ===== --}}
    @if($showRenameBook)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="$set('showRenameBook', false)"></div>
            <div class="relative w-full max-w-md dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 p-6">
                <h3 class="font-heading font-bold text-lg dark:text-white text-gray-900 mb-4">Rename Book</h3>
                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                        Book Name
                    </label>
                    <input type="text"
                           wire:model="renameBookName"
                           wire:keydown.enter="renameBook"
                           autofocus
                           class="w-full px-4 py-2.5 text-sm font-body
                                  dark:bg-slate-800 bg-white
                                  dark:border dark:border-slate-700 border border-gray-300
                                  dark:text-white text-gray-900 rounded-xl
                                  focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                  transition-all duration-150">
                    @error('renameBookName')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-2">
                    <button wire:click="$set('showRenameBook', false)"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200
                                   rounded-xl transition-all duration-200">
                        Cancel
                    </button>
                    <button wire:click="renameBook"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-primary text-white hover:bg-accent
                                   rounded-xl transition-all duration-200">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== DELETE BOOK MODAL ===== --}}
    @if($showDeleteBook)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="$set('showDeleteBook', false)"></div>
            <div class="relative w-full max-w-md dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 p-6">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
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
                    @error('deleteConfirmName')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-2">
                    <button wire:click="$set('showDeleteBook', false)"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200
                                   rounded-xl transition-all duration-200">
                        Cancel
                    </button>
                    <button wire:click="deleteBook"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-red-500 text-white hover:bg-red-400
                                   rounded-xl transition-all duration-200 shadow-lg shadow-red-500/20">
                        Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== UPGRADE MODAL ===== --}}
    <x-upgrade-modal :show="$showUpgradeModal" feature="export"
        :is-owner="auth()->user()->id === $business->owner_id"
        :business-name="$business->name" />

    {{-- ===== RECURRING UPDATE CONFIRMATION ===== --}}
    @if($showRecurringUpdateConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="skipRecurringUpdate"></div>
            <div class="relative w-full max-w-sm dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 p-6"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/>
                    </svg>
                </div>

                <h3 class="font-display font-extrabold text-lg dark:text-white text-gray-900 text-center mb-2">
                    Update Recurring Entry?
                </h3>
                <p class="text-sm dark:text-slate-400 text-gray-500 font-body text-center mb-6">
                    This entry is linked to a recurring rule. Apply your changes to all future auto-generated entries too?
                </p>

                <div class="space-y-2">
                    <button wire:click="applyToRecurring"
                            class="w-full py-2.5 text-sm font-semibold font-body text-white bg-primary hover:bg-accent rounded-xl transition-colors duration-150">
                        Yes, update future entries
                    </button>
                    <button wire:click="skipRecurringUpdate"
                            class="w-full py-2.5 text-sm font-semibold font-body dark:text-slate-300 text-gray-700 dark:bg-slate-800 bg-gray-100 dark:hover:bg-slate-700 hover:bg-gray-200 rounded-xl transition-colors duration-150">
                        No, this entry only
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== EDIT RECURRING MODAL ===== --}}
    @if($showEditRecurring)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="closeEditRecurring"></div>
            <div class="relative w-full max-w-md dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 p-6"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                <h3 class="font-display font-extrabold text-lg dark:text-white text-gray-900 mb-5">Edit Recurring Entry</h3>

                <div class="space-y-4">
                    {{-- Amount --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">Amount</label>
                        <input type="number" step="0.01" min="0.01"
                               wire:model="editRecAmount"
                               class="w-full px-4 py-2.5 text-sm font-mono
                                      dark:bg-slate-800 bg-white dark:border dark:border-slate-700 border border-gray-300
                                      dark:text-white text-gray-900 rounded-xl
                                      focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all duration-150">
                        @error('editRecAmount')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">Description</label>
                        <input type="text" maxlength="255"
                               wire:model="editRecDescription"
                               class="w-full px-4 py-2.5 text-sm font-body
                                      dark:bg-slate-800 bg-white dark:border dark:border-slate-700 border border-gray-300
                                      dark:text-white text-gray-900 rounded-xl
                                      focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all duration-150">
                        @error('editRecDescription')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                            Category <span class="normal-case tracking-normal font-normal dark:text-slate-600 text-gray-400 ml-1">· optional</span>
                        </label>
                        <input type="text" maxlength="100"
                               wire:model="editRecCategory"
                               class="w-full px-4 py-2.5 text-sm font-body
                                      dark:bg-slate-800 bg-white dark:border dark:border-slate-700 border border-gray-300
                                      dark:text-white text-gray-900 rounded-xl
                                      focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all duration-150">
                    </div>

                    {{-- Payment Mode --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                            Payment Mode <span class="normal-case tracking-normal font-normal dark:text-slate-600 text-gray-400 ml-1">· optional</span>
                        </label>
                        <input type="text" maxlength="100"
                               wire:model="editRecPaymentMode"
                               class="w-full px-4 py-2.5 text-sm font-body
                                      dark:bg-slate-800 bg-white dark:border dark:border-slate-700 border border-gray-300
                                      dark:text-white text-gray-900 rounded-xl
                                      focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50 transition-all duration-150">
                    </div>

                    {{-- Frequency --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-2">Frequency</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $freqVal => $freqLabel)
                                <button type="button"
                                        wire:click="$set('editRecFrequency', '{{ $freqVal }}')"
                                        class="px-3.5 py-2 rounded-xl text-sm font-body font-medium transition-all duration-150
                                               {{ $editRecFrequency === $freqVal
                                                   ? 'bg-primary/10 border-primary/50 text-primary ring-2 ring-primary/30 border'
                                                   : 'dark:border-slate-700 border-gray-300 dark:text-slate-400 text-gray-500 border dark:hover:border-slate-600 hover:border-gray-400' }}">
                                    {{ $freqLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- End Date --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                            End Date <span class="normal-case tracking-normal font-normal dark:text-slate-600 text-gray-400 ml-1">· optional</span>
                        </label>
                        <input type="date"
                               wire:model="editRecEndsAt"
                               class="w-full max-w-[200px] px-4 py-2.5 text-sm font-body
                                      dark:bg-slate-800 bg-white dark:border dark:border-slate-700 border border-gray-300
                                      dark:text-white text-gray-900 rounded-xl
                                      focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      transition-all duration-150 dark:[color-scheme:dark]">
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 mt-6">
                    <button wire:click="closeEditRecurring"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200 rounded-xl transition-colors duration-150">
                        Cancel
                    </button>
                    <button wire:click="updateRecurring"
                            class="flex-1 py-2.5 text-sm font-semibold font-body text-white bg-primary hover:bg-accent rounded-xl transition-colors duration-150">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    @endif

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
                        <button wire:click="closeAttachmentPreview"
                                class="p-2 rounded-xl dark:bg-slate-800 bg-gray-100
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
                            <svg class="w-16 h-16 mx-auto dark:text-red-400/60 text-red-400 mb-4" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
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
                 x-data="{ mode: 'range' }"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-heading font-bold text-base dark:text-white text-gray-900">Custom Date</h3>
                    <button wire:click="cancelCustomDate"
                            class="p-1.5 rounded-xl dark:text-slate-500 text-gray-400
                                   dark:hover:bg-slate-800 hover:bg-gray-100 dark:hover:text-white hover:text-gray-700
                                   transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Mode tabs --}}
                <div class="flex gap-1 p-1 dark:bg-slate-900 bg-gray-100 rounded-xl mb-4">
                    <button @click="mode = 'range'"
                            :class="mode === 'range' ? 'dark:bg-slate-700 bg-white dark:text-white text-gray-900 shadow-sm' : 'dark:text-slate-500 text-gray-500'"
                            class="flex-1 py-1.5 text-xs font-semibold font-body rounded-lg transition-all duration-150">
                        Date Range
                    </button>
                    <button @click="mode = 'single'"
                            :class="mode === 'single' ? 'dark:bg-slate-700 bg-white dark:text-white text-gray-900 shadow-sm' : 'dark:text-slate-500 text-gray-500'"
                            class="flex-1 py-1.5 text-xs font-semibold font-body rounded-lg transition-all duration-150">
                        Single Day
                    </button>
                </div>

                {{-- Date range mode --}}
                <div x-show="mode === 'range'" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">From</label>
                        <input type="date"
                               wire:model="filterCustomFrom"
                               class="w-full px-3 py-2 text-sm font-body rounded-xl
                                      dark:bg-slate-800 bg-white
                                      dark:border dark:border-slate-700 border border-gray-300
                                      dark:text-white text-gray-900
                                      dark:[color-scheme:dark]
                                      focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      transition-all duration-150">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">To</label>
                        <input type="date"
                               wire:model="filterCustomTo"
                               class="w-full px-3 py-2 text-sm font-body rounded-xl
                                      dark:bg-slate-800 bg-white
                                      dark:border dark:border-slate-700 border border-gray-300
                                      dark:text-white text-gray-900
                                      dark:[color-scheme:dark]
                                      focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      transition-all duration-150">
                    </div>
                </div>

                {{-- Single day mode --}}
                <div x-show="mode === 'single'">
                    <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">Date</label>
                    <input type="date"
                           wire:model="filterCustomFrom"
                           @change="$wire.set('filterCustomTo', $event.target.value)"
                           class="w-full px-3 py-2 text-sm font-body rounded-xl
                                  dark:bg-slate-800 bg-white
                                  dark:border dark:border-slate-700 border border-gray-300
                                  dark:text-white text-gray-900
                                  dark:[color-scheme:dark]
                                  focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                  transition-all duration-150">
                </div>

                <div class="flex gap-2 mt-5">
                    <button wire:click="cancelCustomDate"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                                   dark:hover:bg-slate-700 hover:bg-gray-200
                                   rounded-xl transition-all duration-200">
                        Cancel
                    </button>
                    <button wire:click="applyCustomDate"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-primary text-white hover:bg-accent
                                   rounded-xl transition-all duration-200">
                        Apply
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== PAGE BODY ===== --}}
    <div class="px-6 lg:px-8 py-6">
        <div class="max-w-5xl mx-auto space-y-3">

            {{-- ===== FILTER BAR ===== --}}
            <div class="flex items-center gap-2 flex-wrap">

                {{-- Types filter --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" type="button"
                            class="flex items-center gap-1.5 px-3 py-1.5
                                   dark:bg-dark bg-white
                                   dark:border dark:border-slate-700 border border-gray-200
                                   dark:text-slate-300 text-gray-700
                                   text-xs font-semibold font-body rounded-lg
                                   hover:dark:border-slate-600 hover:border-gray-300
                                   transition-all duration-150">
                        <span>Types:
                            <span class="{{ $filterType !== 'all' ? 'text-primary' : '' }}">
                                {{ $filterType === 'all' ? 'All' : ($filterType === 'in' ? 'Cash In' : 'Cash Out') }}
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
                         class="absolute top-full left-0 mt-1 w-44 z-20
                                dark:bg-slate-800 bg-white
                                dark:border dark:border-slate-700 border border-gray-200
                                rounded-xl shadow-xl shadow-black/20 overflow-hidden"
                         style="display:none;">
                        @foreach(['all' => 'All', 'in' => 'Cash In', 'out' => 'Cash Out'] as $val => $label)
                            <button @click="$wire.set('filterType', '{{ $val }}'); open = false"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-body
                                           dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors
                                           {{ $filterType === $val ? 'dark:text-white text-gray-900 font-semibold' : 'dark:text-slate-400 text-gray-600' }}">
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0
                                            {{ $filterType === $val ? 'border-primary' : 'dark:border-slate-600 border-gray-300' }}">
                                    @if($filterType === $val)
                                        <div class="w-2 h-2 rounded-full bg-primary"></div>
                                    @endif
                                </div>
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
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
                        <span>Pay Mode:
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
                                No payment modes yet
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Clear filters (shown only when any filter is active) --}}
                @if($filterType !== 'all' || $filterDuration !== 'all_time' || count($filterCategories) > 0 || count($filterPaymentModes) > 0)
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

            {{-- ===== SUCCESS BANNER ===== --}}
            @if($bulkSuccessMessage)
                <div x-data="{ show: true }"
                     x-init="setTimeout(() => { show = false; $wire.set('bulkSuccessMessage', '') }, 3000)"
                     x-show="show"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                    <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    <span class="text-sm font-body text-emerald-400">{{ $bulkSuccessMessage }}</span>
                </div>
            @endif

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
                            dark:bg-dark bg-white rounded-2xl
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
                    </label>

                    <div class="w-px h-6 dark:bg-slate-700 bg-gray-200 mx-1"></div>

                    {{-- Delete --}}
                    <button @click="$wire.set('showBulkDeleteConfirm', true)"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold font-body
                                   text-red-400 hover:bg-red-500/10 rounded-xl transition-colors duration-150">
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
                                       dark:hover:bg-slate-800 hover:bg-gray-100 rounded-xl transition-colors duration-150">
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
                                       dark:hover:bg-slate-800 hover:bg-gray-100 rounded-xl transition-colors duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75M10.5 18a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 18H7.5m-3-6h9.75m0 0a1.5 1.5 0 0 1 3 0m-3 0a1.5 1.5 0 0 0 3 0m0 0h3.75"/>
                            </svg>
                            Change Fields
                            <svg class="w-3 h-3 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition
                             class="absolute left-0 top-full mt-1 w-52 py-1.5
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
                                Change Payment Mode
                            </button>
                        </div>
                    </div>

                    <div class="flex-1"></div>

                    {{-- Clear selection --}}
                    <button @click="clearSelection()"
                            class="p-1.5 rounded-lg dark:text-slate-500 text-gray-400
                                   dark:hover:bg-slate-800 hover:bg-gray-100
                                   dark:hover:text-white hover:text-gray-700 transition-colors duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

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
                                           text-red-400 bg-red-500/10 rounded-lg whitespace-nowrap">
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
                            <button @click="$wire.set('showBulkChangeCategory', true)"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold font-body
                                           dark:text-slate-300 text-gray-700 dark:bg-slate-800 bg-gray-100 rounded-lg whitespace-nowrap">
                                Category
                            </button>
                            <button @click="$wire.set('showBulkChangePaymentMode', true)"
                                    class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold font-body
                                           dark:text-slate-300 text-gray-700 dark:bg-slate-800 bg-gray-100 rounded-lg whitespace-nowrap">
                                Pay Mode
                            </button>
                        </div>
                        <button @click="clearSelection()"
                                class="p-1.5 dark:text-slate-500 text-gray-400 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            {{-- ===== BALANCE SUMMARY STRIP ===== --}}
            @php
                $isPositive  = bccomp((string)$balance, '0', 2) >= 0;
                $currSymbol  = $business->currencySymbol();
                $openingBal  = (float)$book->opening_balance;
            @endphp
            <div class="dark:bg-dark bg-white rounded-2xl
                        dark:border dark:border-slate-700 border border-gray-200 overflow-hidden">
                <div class="flex divide-x dark:divide-slate-700 divide-gray-200">

                    <div class="flex-1 px-3 py-3 sm:px-5 sm:py-4 flex items-center gap-2 sm:gap-3">
                        <div class="hidden sm:flex w-9 h-9 rounded-full bg-emerald-500/10 items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">Cash In</p>
                            <p class="font-mono font-bold text-base sm:text-xl text-emerald-400 leading-tight mt-0.5 truncate">
                                {{ $currSymbol }}{{ number_format((float)$totalIn, 0) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex-1 px-3 py-3 sm:px-5 sm:py-4 flex items-center gap-2 sm:gap-3">
                        <div class="hidden sm:flex w-9 h-9 rounded-full bg-red-500/10 items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">Cash Out</p>
                            <p class="font-mono font-bold text-base sm:text-xl text-red-400 leading-tight mt-0.5 truncate">
                                {{ $currSymbol }}{{ number_format((float)$totalOut, 0) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex-1 px-3 py-3 sm:px-5 sm:py-4 flex items-center gap-2 sm:gap-3">
                        <div class="hidden sm:flex w-9 h-9 rounded-full {{ $isPositive ? 'bg-primary/10' : 'bg-red-500/10' }} items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 {{ $isPositive ? 'text-blue-light' : 'text-red-400' }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.499 8.248h15m-15 7.501h15"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">Net Balance</p>
                            <p class="font-mono font-bold text-base sm:text-xl leading-tight mt-0.5 truncate
                                      {{ $isPositive ? 'dark:text-blue-light text-primary' : 'text-red-400' }}">
                                {{ $currSymbol }}@if(!$isPositive)−@endif{{ number_format(abs((float)$balance), 0) }}
                            </p>
                            @if($openingBal > 0)
                                <p class="text-[9px] dark:text-slate-600 text-gray-400 font-body mt-0.5 truncate">
                                    incl. {{ $currSymbol }}{{ number_format($openingBal, 0) }} opening
                                </p>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            {{-- ===== VIEW TABS ===== --}}
            <div class="flex items-center gap-1 dark:bg-slate-800/60 bg-gray-100 rounded-xl p-1 w-fit">
                <button wire:click="$set('activeTab', 'entries')"
                        class="px-4 py-2 rounded-lg text-sm font-semibold font-body transition-all duration-150
                               {{ $activeTab === 'entries'
                                   ? 'dark:bg-dark bg-white dark:text-white text-gray-900 shadow-sm'
                                   : 'dark:text-slate-400 text-gray-500 hover:dark:text-white hover:text-gray-900' }}">
                    Entries
                </button>
                <button wire:click="$set('activeTab', 'reports')"
                        class="px-4 py-2 rounded-lg text-sm font-semibold font-body transition-all duration-150 flex items-center gap-1.5
                               {{ $activeTab === 'reports'
                                   ? 'dark:bg-dark bg-white dark:text-white text-gray-900 shadow-sm'
                                   : 'dark:text-slate-400 text-gray-500 hover:dark:text-white hover:text-gray-900' }}">
                    Reports
                    <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-primary/10 text-primary leading-none">Pro</span>
                </button>
                <button wire:click="$set('activeTab', 'recurring')"
                        class="px-4 py-2 rounded-lg text-sm font-semibold font-body transition-all duration-150 flex items-center gap-1.5
                               {{ $activeTab === 'recurring'
                                   ? 'dark:bg-dark bg-white dark:text-white text-gray-900 shadow-sm'
                                   : 'dark:text-slate-400 text-gray-500 hover:dark:text-white hover:text-gray-900' }}">
                    Recurring
                    <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded bg-primary/10 text-primary leading-none">Pro</span>
                </button>
            </div>

            @if($activeTab === 'entries')

            {{-- ===== SEARCH + ADD BUTTONS ===== --}}
            <div class="flex items-center gap-2 sm:gap-3">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                        <svg class="w-4 h-4 dark:text-slate-600 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                    </div>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search by description, category…"
                           class="w-full pl-10 pr-4 py-2.5 text-sm font-body
                                  dark:bg-dark bg-white
                                  dark:border dark:border-slate-700 border border-gray-200
                                  dark:text-white text-gray-900 rounded-xl
                                  placeholder:dark:text-slate-600 placeholder:text-gray-400
                                  focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                  transition-all duration-150">
                </div>
                @if($userRole !== 'viewer')
                    <button wire:click="openAddEntry('in')"
                            class="flex items-center gap-1.5 px-4 py-2.5 flex-shrink-0
                                   bg-emerald-500/10 text-emerald-400
                                   hover:bg-emerald-500 hover:text-white
                                   border border-emerald-500/25 hover:border-emerald-500
                                   text-sm font-semibold font-body rounded-xl transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span class="hidden sm:block">Cash In</span>
                    </button>
                    <button wire:click="openAddEntry('out')"
                            class="flex items-center gap-1.5 px-4 py-2.5 flex-shrink-0
                                   bg-red-500/10 text-red-400
                                   hover:bg-red-500 hover:text-white
                                   border border-red-500/25 hover:border-red-500
                                   text-sm font-semibold font-body rounded-xl transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                        </svg>
                        <span class="hidden sm:block">Cash Out</span>
                    </button>
                @endif
            </div>

            {{-- ===== ENTRIES TABLE / EMPTY STATE ===== --}}
            @if($entries->isEmpty() && $search === '' && $filterType === 'all' && $filterDuration === 'all_time' && empty($filterCategories) && empty($filterPaymentModes))

                <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-dashed border-gray-200
                            rounded-2xl px-8 py-20 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </div>
                    <h2 class="font-heading font-bold text-base dark:text-white text-gray-900 mb-1.5">No entries yet</h2>
                    <p class="text-sm dark:text-slate-500 text-gray-500 font-body mb-6 max-w-xs mx-auto">
                        Record your first transaction to start tracking this book's balance.
                    </p>
                    @if($userRole !== 'viewer')
                        <div class="flex items-center justify-center gap-3">
                            <button wire:click="openAddEntry('in')"
                                    class="flex items-center gap-2 px-4 py-2.5
                                           bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500 hover:text-white
                                           border border-emerald-500/25 text-sm font-semibold font-body rounded-xl transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Cash In
                            </button>
                            <button wire:click="openAddEntry('out')"
                                    class="flex items-center gap-2 px-4 py-2.5
                                           bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white
                                           border border-red-500/25 text-sm font-semibold font-body rounded-xl transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                                Cash Out
                            </button>
                        </div>
                    @endif
                </div>

            @else

                @php $filteredIds = $entries->pluck('id')->values()->toJson(); @endphp
                <div class="dark:bg-dark bg-white rounded-2xl
                            dark:border dark:border-slate-700 border border-gray-200 overflow-hidden"
                     x-init="filteredIds = {{ $filteredIds }}; selectedIds = selectedIds.filter(id => filteredIds.includes(id)); syncSelectAll();">

                    {{-- Column headers --}}
                    <div class="hidden md:grid {{ $userRole !== 'viewer' ? 'md:grid-cols-[36px_120px_1fr_120px_110px_140px_130px_56px]' : 'md:grid-cols-[120px_1fr_120px_110px_140px_130px_56px]' }}
                                px-5 py-3
                                dark:border-b dark:border-slate-700 border-b border-gray-100
                                dark:bg-navy/50 bg-gray-50/70">
                        @if($userRole !== 'viewer')
                            <label class="flex items-center justify-center cursor-pointer">
                                <input type="checkbox"
                                       x-ref="headerSelectAll"
                                       :checked="selectAll"
                                       @change="toggleSelectAll()"
                                       x-effect="if ($refs.headerSelectAll) $refs.headerSelectAll.indeterminate = selectedIds.length > 0 && selectedIds.length < filteredIds.length"
                                       class="w-3.5 h-3.5 rounded border-slate-600 text-primary focus:ring-primary/40 dark:bg-slate-800 bg-white">
                            </label>
                        @endif
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body">Date</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body">Description</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body">Category</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body">Mode</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body text-right pr-4">Amount</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 font-body text-right pr-2">Balance</span>
                        <span></span>
                    </div>

                    <div class="divide-y dark:divide-slate-700/40 divide-gray-100">
                        @forelse($entries as $entry)
                            @php
                                $rb    = $entry->running_balance ?? '0.00';
                                $rbPos = bccomp((string)$rb, '0', 2) >= 0;
                            @endphp
                            <div wire:key="{{ $entry->id }}"
                                 x-data="{ hovered: false, confirming: false }"
                                 @mouseenter="hovered = true"
                                 @mouseleave="hovered = false; confirming = false"
                                 class="transition-colors duration-100 dark:hover:bg-slate-800/30 hover:bg-gray-50/80">

                                {{-- Desktop --}}
                                <div class="hidden md:grid {{ $userRole !== 'viewer' ? 'md:grid-cols-[36px_120px_1fr_120px_110px_140px_130px_56px]' : 'md:grid-cols-[120px_1fr_120px_110px_140px_130px_56px]' }} items-center px-5 py-3.5"
                                     :class="isSelected('{{ $entry->id }}') ? 'dark:!bg-primary/5 !bg-blue-50/50' : ''">
                                    @if($userRole !== 'viewer')
                                        <label class="flex items-center justify-center cursor-pointer" @click.stop>
                                            <input type="checkbox"
                                                   :checked="isSelected('{{ $entry->id }}')"
                                                   @change="toggleEntry('{{ $entry->id }}')"
                                                   class="w-3.5 h-3.5 rounded border-slate-600 text-primary focus:ring-primary/40 dark:bg-slate-800 bg-white">
                                        </label>
                                    @endif

                                    <span class="text-sm dark:text-slate-400 text-gray-600 font-body">
                                        {{ $entry->date->format('d M Y') }}
                                    </span>

                                    <div class="min-w-0 pr-3">
                                        <p class="text-sm font-medium dark:text-white text-gray-900 font-body truncate flex items-center gap-1.5">
                                            {{ $entry->description }}
                                            @if($entry->recurring_entry_id)
                                                <svg class="w-3.5 h-3.5 flex-shrink-0 dark:text-primary text-primary/70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" title="Recurring entry">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/>
                                                </svg>
                                            @endif
                                            @if($entry->attachment_path)
                                                <button wire:click.stop="openAttachmentPreview('{{ $entry->id }}')"
                                                        class="flex-shrink-0 dark:text-amber-400/70 text-amber-500/70 hover:dark:text-amber-400 hover:text-amber-500 transition-colors"
                                                        title="View attachment">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </p>
                                        @if($entry->reference)
                                            <p class="text-[11px] font-mono dark:text-slate-600 text-gray-400 mt-0.5 truncate">
                                                {{ $entry->reference }}
                                            </p>
                                        @endif
                                    </div>

                                    <span class="text-xs dark:text-slate-500 text-gray-500 font-body truncate pr-2">
                                        {{ $entry->category ?: '—' }}
                                    </span>

                                    <span class="text-xs dark:text-slate-500 text-gray-500 font-body truncate pr-2">
                                        {{ $entry->payment_mode ?: '—' }}
                                    </span>

                                    <div class="text-right pr-4">
                                        @if($entry->type === 'in')
                                            <span class="font-mono text-sm font-semibold text-emerald-400">
                                                +{{ $currSymbol }}{{ number_format((float)$entry->amount, 2) }}
                                            </span>
                                        @else
                                            <span class="font-mono text-sm font-semibold text-red-400">
                                                −{{ $currSymbol }}{{ number_format((float)$entry->amount, 2) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="text-right pr-2">
                                        <span class="font-mono text-sm font-semibold
                                                     {{ $rbPos ? 'dark:text-slate-300 text-gray-700' : 'text-red-400' }}">
                                            @if(!$rbPos)−@endif{{ $currSymbol }}{{ number_format(abs((float)$rb), 2) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center justify-end gap-0.5"
                                         :class="(hovered || confirming) ? 'opacity-100' : 'opacity-0'"
                                         style="transition: opacity 0.15s;">
                                        <template x-if="!confirming">
                                            <div class="flex gap-0.5">
                                                @if($userRole !== 'viewer')
                                                    <button @click.stop="$wire.openEditEntry('{{ $entry->id }}')"
                                                            class="p-1.5 rounded-lg dark:text-slate-500 text-gray-400
                                                                   dark:hover:bg-slate-700 hover:bg-gray-200
                                                                   dark:hover:text-white hover:text-gray-700 transition-colors duration-150">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                                        </svg>
                                                    </button>
                                                    <button @click.stop="confirming = true"
                                                            class="p-1.5 rounded-lg dark:text-slate-500 text-gray-400
                                                                   dark:hover:bg-red-500/10 hover:bg-red-50
                                                                   dark:hover:text-red-400 hover:text-red-500 transition-colors duration-150">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </template>
                                        <template x-if="confirming">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] dark:text-slate-400 text-gray-500 font-body whitespace-nowrap">Delete?</span>
                                                <button @click.stop="$wire.deleteEntry('{{ $entry->id }}'); confirming = false"
                                                        class="px-2 py-1 text-[11px] font-semibold font-body bg-red-500/10 text-red-400 hover:bg-red-500/20 rounded-lg transition-colors">Yes</button>
                                                <button @click.stop="confirming = false"
                                                        class="px-2 py-1 text-[11px] font-semibold font-body dark:bg-slate-700 bg-gray-200 dark:text-slate-300 text-gray-600 dark:hover:bg-slate-600 rounded-lg transition-colors">No</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Mobile --}}
                                <div class="md:hidden flex items-center gap-3 px-4 py-3.5"
                                     :class="isSelected('{{ $entry->id }}') ? 'dark:!bg-primary/5 !bg-blue-50/50' : ''">
                                    @if($userRole !== 'viewer')
                                        <label class="flex items-center cursor-pointer flex-shrink-0" @click.stop>
                                            <input type="checkbox"
                                                   :checked="isSelected('{{ $entry->id }}')"
                                                   @change="toggleEntry('{{ $entry->id }}')"
                                                   class="w-4 h-4 rounded border-slate-600 text-primary focus:ring-primary/40 dark:bg-slate-800 bg-white">
                                        </label>
                                    @endif
                                    <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $entry->type === 'in' ? 'bg-emerald-400' : 'bg-red-400' }}"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium dark:text-white text-gray-900 font-body truncate flex items-center gap-1.5">
                                            {{ $entry->description }}
                                            @if($entry->recurring_entry_id)
                                                <svg class="w-3.5 h-3.5 flex-shrink-0 dark:text-primary text-primary/70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" title="Recurring entry">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/>
                                                </svg>
                                            @endif
                                            @if($entry->attachment_path)
                                                <button wire:click.stop="openAttachmentPreview('{{ $entry->id }}')"
                                                        class="flex-shrink-0 dark:text-amber-400/70 text-amber-500/70 hover:dark:text-amber-400 hover:text-amber-500 transition-colors"
                                                        title="View attachment">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </p>
                                        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                            <span class="text-xs dark:text-slate-500 text-gray-400 font-body">{{ $entry->date->format('d M Y') }}</span>
                                            @if($entry->category)<span class="text-xs dark:text-slate-600 text-gray-400 font-body">· {{ $entry->category }}</span>@endif
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        @if($entry->type === 'in')
                                            <p class="font-mono text-sm font-semibold text-emerald-400">+{{ $currSymbol }}{{ number_format((float)$entry->amount, 2) }}</p>
                                        @else
                                            <p class="font-mono text-sm font-semibold text-red-400">−{{ $currSymbol }}{{ number_format((float)$entry->amount, 2) }}</p>
                                        @endif
                                        <p class="font-mono text-xs {{ $rbPos ? 'dark:text-slate-500 text-gray-400' : 'text-red-400/70' }} mt-0.5">
                                            @if(!$rbPos)−@endif{{ $currSymbol }}{{ number_format(abs((float)$rb), 2) }}
                                        </p>
                                    </div>
                                </div>

                            </div>
                        @empty
                            <div class="px-6 py-14 text-center">
                                <p class="text-sm dark:text-slate-500 text-gray-500 font-body">
                                    No entries match your current filters.
                                </p>
                            </div>
                        @endforelse
                    </div>

                    @if($entries->isNotEmpty())
                        <div class="px-5 py-3
                                    dark:border-t dark:border-slate-700/40 border-t border-gray-100
                                    dark:bg-navy/30 bg-gray-50/50
                                    flex items-center justify-between">
                            <span class="text-xs dark:text-slate-600 text-gray-400 font-body">
                                {{ $entries->count() }} {{ Str::plural('entry', $entries->count()) }}
                                @if($search !== '' || $filterType !== 'all' || $filterDuration !== 'all_time' || count($filterCategories) > 0 || count($filterPaymentModes) > 0) shown @else total @endif
                            </span>
                            <span class="text-xs font-mono dark:text-slate-600 text-gray-400">{{ $currSymbol }}{{ $business->currency }}</span>
                        </div>
                    @endif

                </div>

            @endif

            @elseif($activeTab === 'reports')
            {{-- ===== REPORTS TAB ===== --}}

            @if($business->isPro())

                @php
                    $rCurr = $business->currencySymbol();
                    $rSummary = $reportData['periodSummary'] ?? [];
                    $rTrend = $reportData['trendChart'] ?? [];
                    $rCategories = $reportData['categoryBreakdown'] ?? [];
                    $rPayModes = $reportData['paymentModeBreakdown'] ?? [];
                    $rNetPositive = bccomp($rSummary['netBalance'] ?? '0', '0', 2) >= 0;
                @endphp

                <div class="space-y-5">

                    {{-- Period Summary Cards --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        {{-- Cash In --}}
                        <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-4">
                            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">Cash In</p>
                            <p class="font-mono font-bold text-lg sm:text-xl text-emerald-400 leading-tight mt-1 truncate">
                                {{ $rCurr }}{{ number_format((float)($rSummary['totalIn'] ?? 0), 0) }}
                            </p>
                            <p class="text-[10px] dark:text-slate-600 text-gray-400 font-body mt-1">{{ $rSummary['inCount'] ?? 0 }} {{ Str::plural('entry', $rSummary['inCount'] ?? 0) }}</p>
                        </div>

                        {{-- Cash Out --}}
                        <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-4">
                            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">Cash Out</p>
                            <p class="font-mono font-bold text-lg sm:text-xl text-red-400 leading-tight mt-1 truncate">
                                {{ $rCurr }}{{ number_format((float)($rSummary['totalOut'] ?? 0), 0) }}
                            </p>
                            <p class="text-[10px] dark:text-slate-600 text-gray-400 font-body mt-1">{{ $rSummary['outCount'] ?? 0 }} {{ Str::plural('entry', $rSummary['outCount'] ?? 0) }}</p>
                        </div>

                        {{-- Net Balance --}}
                        <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-4">
                            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">Net Balance</p>
                            <p class="font-mono font-bold text-lg sm:text-xl leading-tight mt-1 truncate {{ $rNetPositive ? 'dark:text-blue-light text-primary' : 'text-red-400' }}">
                                @if(!$rNetPositive)−@endif{{ $rCurr }}{{ number_format(abs((float)($rSummary['netBalance'] ?? 0)), 0) }}
                            </p>
                            <p class="text-[10px] dark:text-slate-600 text-gray-400 font-body mt-1">{{ ($rSummary['inCount'] ?? 0) + ($rSummary['outCount'] ?? 0) }} total entries</p>
                        </div>

                        {{-- Daily Average --}}
                        <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-4">
                            <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body">Daily Average</p>
                            @php $avgPositive = bccomp($rSummary['dailyAverage'] ?? '0', '0', 2) >= 0; @endphp
                            <p class="font-mono font-bold text-lg sm:text-xl leading-tight mt-1 truncate {{ $avgPositive ? 'dark:text-slate-300 text-gray-700' : 'text-red-400' }}">
                                @if(!$avgPositive)−@endif{{ $rCurr }}{{ number_format(abs((float)($rSummary['dailyAverage'] ?? 0)), 0) }}
                            </p>
                            <p class="text-[10px] dark:text-slate-600 text-gray-400 font-body mt-1">over {{ $rSummary['daySpan'] ?? 1 }} {{ Str::plural('day', $rSummary['daySpan'] ?? 1) }}</p>
                        </div>
                    </div>

                    {{-- Cash Flow Trend Chart --}}
                    <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900">Cash Flow Trend</h3>
                            <div class="flex items-center gap-3 text-[10px] font-body">
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-400"></span> <span class="dark:text-slate-400 text-gray-500">Cash In</span></span>
                                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-red-400"></span> <span class="dark:text-slate-400 text-gray-500">Cash Out</span></span>
                            </div>
                        </div>

                        @if(count($rTrend) >= 2)
                            @php
                                $maxTrend = max(1, max(
                                    collect($rTrend)->max('in'),
                                    collect($rTrend)->max('out')
                                ));
                            @endphp

                            <div class="flex items-end gap-0.5 sm:gap-1 h-32 sm:h-48">
                                @foreach($rTrend as $i => $period)
                                    @php
                                        $inPct  = ($period['in'] / $maxTrend) * 100;
                                        $outPct = ($period['out'] / $maxTrend) * 100;
                                    @endphp
                                    <div class="flex-1 flex items-end gap-px h-full group relative">
                                        {{-- In bar --}}
                                        <div class="flex-1 rounded-t-sm bg-emerald-500/50 group-hover:bg-emerald-400 transition-colors duration-150"
                                             style="height: {{ max(2, $inPct) }}%"></div>
                                        {{-- Out bar --}}
                                        <div class="flex-1 rounded-t-sm bg-red-500/50 group-hover:bg-red-400 transition-colors duration-150"
                                             style="height: {{ max(2, $outPct) }}%"></div>

                                        {{-- Tooltip --}}
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1.5 rounded-lg
                                                    dark:bg-slate-800 bg-gray-800 text-white text-[10px] font-body whitespace-nowrap
                                                    opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-10 shadow-lg">
                                            <p class="font-semibold mb-0.5">{{ $period['label'] }}</p>
                                            <p class="text-emerald-300">In: {{ $rCurr }}{{ number_format($period['in'], 0) }}</p>
                                            <p class="text-red-300">Out: {{ $rCurr }}{{ number_format($period['out'], 0) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- X-axis labels --}}
                            <div class="flex justify-between mt-2 text-[9px] dark:text-slate-600 text-gray-400 font-body">
                                <span>{{ $rTrend[0]['label'] }}</span>
                                @if(count($rTrend) > 2)
                                    <span class="hidden sm:inline">{{ $rTrend[intval(count($rTrend) / 2)]['label'] }}</span>
                                @endif
                                <span>{{ $rTrend[count($rTrend) - 1]['label'] }}</span>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <svg class="w-8 h-8 dark:text-slate-700 text-gray-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                                </svg>
                                <p class="text-sm dark:text-slate-500 text-gray-400 font-body">Add more entries to see cash flow trends</p>
                            </div>
                        @endif
                    </div>

                    {{-- Category Breakdown --}}
                    <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-5"
                         x-data="{ catView: 'out' }">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900">By Category</h3>
                            <div class="flex items-center gap-1 dark:bg-slate-800 bg-gray-100 rounded-lg p-0.5">
                                <button @click="catView = 'in'"
                                        :class="catView === 'in' ? 'dark:bg-slate-700 bg-white shadow-sm dark:text-white text-gray-900' : 'dark:text-slate-500 text-gray-400'"
                                        class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider font-body transition-all duration-150">
                                    Cash In
                                </button>
                                <button @click="catView = 'out'"
                                        :class="catView === 'out' ? 'dark:bg-slate-700 bg-white shadow-sm dark:text-white text-gray-900' : 'dark:text-slate-500 text-gray-400'"
                                        class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider font-body transition-all duration-150">
                                    Cash Out
                                </button>
                            </div>
                        </div>

                        {{-- Cash In categories --}}
                        <div x-show="catView === 'in'" x-transition.opacity>
                            @if(!empty($rCategories['in']))
                                <div class="space-y-3">
                                    @foreach($rCategories['in'] as $cat)
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-sm font-body dark:text-slate-300 text-gray-700 truncate mr-3">{{ $cat['name'] }}</span>
                                                <span class="font-mono text-sm dark:text-slate-400 text-gray-500 flex-shrink-0">{{ $rCurr }}{{ number_format($cat['total'], 0) }}</span>
                                            </div>
                                            <div class="h-2 dark:bg-slate-800 bg-gray-100 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full bg-emerald-500/60" style="width: {{ max(2, $cat['percent']) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm dark:text-slate-600 text-gray-400 font-body py-6 text-center">No Cash In entries with categories</p>
                            @endif
                        </div>

                        {{-- Cash Out categories --}}
                        <div x-show="catView === 'out'" x-transition.opacity>
                            @if(!empty($rCategories['out']))
                                <div class="space-y-3">
                                    @foreach($rCategories['out'] as $cat)
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-sm font-body dark:text-slate-300 text-gray-700 truncate mr-3">{{ $cat['name'] }}</span>
                                                <span class="font-mono text-sm dark:text-slate-400 text-gray-500 flex-shrink-0">{{ $rCurr }}{{ number_format($cat['total'], 0) }}</span>
                                            </div>
                                            <div class="h-2 dark:bg-slate-800 bg-gray-100 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full bg-red-500/60" style="width: {{ max(2, $cat['percent']) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm dark:text-slate-600 text-gray-400 font-body py-6 text-center">No Cash Out entries with categories</p>
                            @endif
                        </div>
                    </div>

                    {{-- Payment Mode Breakdown --}}
                    <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-5">
                        <h3 class="font-heading font-bold text-sm dark:text-white text-gray-900 mb-4">By Payment Mode</h3>

                        @if(!empty($rPayModes))
                            <div class="space-y-3">
                                @foreach($rPayModes as $mode)
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-body dark:text-slate-300 text-gray-700 truncate mr-3">{{ $mode['name'] }}</span>
                                            <span class="font-mono text-sm dark:text-slate-400 text-gray-500 flex-shrink-0">{{ $rCurr }}{{ number_format($mode['total'], 0) }}</span>
                                        </div>
                                        <div class="h-2 dark:bg-slate-800 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full bg-accent/60" style="width: {{ max(2, $mode['percent']) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm dark:text-slate-600 text-gray-400 font-body py-6 text-center">No entries with payment modes</p>
                        @endif
                    </div>

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
                            <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                                </svg>
                            </div>
                            <h3 class="font-display font-extrabold text-lg dark:text-white text-gray-900 mb-2">Unlock Reports</h3>
                            <p class="text-sm dark:text-slate-400 text-gray-500 font-body mb-5">See cash flow trends, category breakdowns, and spending insights with Pro.</p>
                            <a href="{{ route('billing') }}" wire:navigate
                               class="inline-flex items-center justify-center w-full px-5 py-2.5 bg-primary hover:bg-accent text-white text-sm font-semibold font-body rounded-xl transition-colors duration-150 shadow-lg shadow-primary/25">
                                Upgrade to Pro — $3/mo
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

                {{-- ===== RECURRING ENTRIES TAB ===== --}}
                @if($business->isPro())

                    @if($recurringEntries->isEmpty())
                        {{-- Empty state --}}
                        <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-dashed border-gray-200
                                    rounded-2xl px-8 py-20 text-center">
                            <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/>
                                </svg>
                            </div>
                            <h3 class="font-display font-extrabold text-lg dark:text-white text-gray-900 mb-2">No recurring entries</h3>
                            <p class="text-sm dark:text-slate-500 text-gray-400 font-body max-w-sm mx-auto">
                                When you add an entry and enable "Repeat this entry", it will appear here. Recurring entries auto-create new entries on your schedule.
                            </p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($recurringEntries as $rec)
                                <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-4 transition-all duration-150
                                            {{ $rec->is_active ? '' : 'opacity-60' }}">
                                    <div class="flex items-start gap-3">
                                        {{-- Type indicator --}}
                                        <div class="mt-1.5 flex-shrink-0">
                                            <span class="block w-2.5 h-2.5 rounded-full {{ $rec->type === 'in' ? 'bg-emerald-400' : 'bg-red-400' }}"></span>
                                        </div>

                                        {{-- Content --}}
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-sm font-body font-medium dark:text-white text-gray-900 truncate">{{ $rec->description }}</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                                             dark:bg-slate-800 bg-gray-100 dark:text-slate-400 text-gray-500">
                                                    {{ ucfirst($rec->frequency) }}
                                                </span>
                                                @if(!$rec->is_active)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                                                 bg-amber-500/10 text-amber-400">
                                                        Paused
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                                                <span class="font-mono text-sm font-medium {{ $rec->type === 'in' ? 'text-emerald-400' : 'text-red-400' }}">
                                                    {{ $rec->type === 'in' ? '+' : '-' }}{{ $business->currency }} {{ number_format($rec->amount, 2) }}
                                                </span>
                                                <span class="text-[11px] dark:text-slate-600 text-gray-400 font-body">
                                                    Next: <span class="font-mono dark:text-slate-400 text-gray-500">{{ $rec->next_run_at->format('d M Y') }}</span>
                                                </span>
                                                @if($rec->ends_at)
                                                    <span class="text-[11px] dark:text-slate-600 text-gray-400 font-body">
                                                        Ends: <span class="font-mono dark:text-slate-400 text-gray-500">{{ $rec->ends_at->format('d M Y') }}</span>
                                                    </span>
                                                @endif
                                                @if($rec->category)
                                                    <span class="text-[11px] dark:text-slate-600 text-gray-400 font-body">{{ $rec->category }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            {{-- Edit --}}
                                            <button wire:click="openEditRecurring('{{ $rec->id }}')"
                                                    title="Edit"
                                                    class="p-2 rounded-lg dark:text-slate-500 text-gray-400 dark:hover:text-white hover:text-gray-700 dark:hover:bg-slate-800 hover:bg-gray-100 transition-colors duration-150">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                                </svg>
                                            </button>

                                            {{-- Toggle active/paused --}}
                                            <button wire:click="toggleRecurring('{{ $rec->id }}')"
                                                    title="{{ $rec->is_active ? 'Pause' : 'Resume' }}"
                                                    class="p-2 rounded-lg transition-colors duration-150
                                                           {{ $rec->is_active
                                                               ? 'dark:text-emerald-400 text-emerald-500 dark:hover:bg-emerald-500/10 hover:bg-emerald-50'
                                                               : 'dark:text-slate-500 text-gray-400 dark:hover:bg-slate-800 hover:bg-gray-100' }}">
                                                @if($rec->is_active)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/>
                                                    </svg>
                                                @endif
                                            </button>

                                            {{-- Delete --}}
                                            <button wire:click="deleteRecurring('{{ $rec->id }}')"
                                                    wire:confirm="Delete this recurring entry? Future auto-entries will stop."
                                                    title="Delete"
                                                    class="p-2 rounded-lg dark:text-slate-600 text-gray-400 hover:text-red-400 dark:hover:bg-red-500/10 hover:bg-red-50 transition-colors duration-150">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                @else
                    {{-- Free user: blurred preview + upgrade CTA --}}
                    <div class="relative">
                        <div class="filter blur-sm pointer-events-none select-none">
                            @for($i = 0; $i < 3; $i++)
                                <div class="dark:bg-dark bg-white dark:border dark:border-slate-700 border border-gray-200 rounded-xl p-4 {{ $i > 0 ? 'mt-3' : '' }}">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-1.5"><span class="block w-2.5 h-2.5 rounded-full {{ $i % 2 === 0 ? 'bg-emerald-400' : 'bg-red-400' }}"></span></div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-body dark:text-white text-gray-900">{{ $i === 0 ? 'Monthly Rent' : ($i === 1 ? 'Weekly Salary' : 'Server Hosting') }}</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase dark:bg-slate-800 bg-gray-100 dark:text-slate-400 text-gray-500">
                                                    {{ $i === 0 ? 'Monthly' : ($i === 1 ? 'Weekly' : 'Yearly') }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-3 mt-1.5">
                                                <span class="font-mono text-sm {{ $i % 2 === 0 ? 'text-emerald-400' : 'text-red-400' }}">
                                                    {{ $i % 2 === 0 ? '+' : '-' }}{{ $business->currency }} {{ $i === 0 ? '50,000.00' : ($i === 1 ? '25,000.00' : '5,000.00') }}
                                                </span>
                                                <span class="text-[11px] dark:text-slate-600 text-gray-400 font-body">Next: <span class="font-mono">01 Apr 2026</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>

                        {{-- Upgrade overlay --}}
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-6">
                            <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.678 48.678 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3-3 3"/>
                                </svg>
                            </div>
                            <h3 class="font-display font-extrabold text-lg dark:text-white text-gray-900 mb-2">Automate Recurring Entries</h3>
                            <p class="text-sm dark:text-slate-400 text-gray-500 font-body mb-5">Set up rent, salaries, and subscriptions to auto-repeat on schedule with Pro.</p>
                            <a href="{{ route('billing') }}" wire:navigate
                               class="inline-flex items-center justify-center w-full max-w-xs px-5 py-2.5 bg-primary hover:bg-accent text-white text-sm font-semibold font-body rounded-xl transition-colors duration-150 shadow-lg shadow-primary/25">
                                Upgrade to Pro — $3/mo
                            </a>
                            <button wire:click="$set('activeTab', 'entries')"
                                    class="mt-3 text-sm dark:text-slate-500 text-gray-400 font-body hover:dark:text-white hover:text-gray-900 transition-colors">
                                Not Now
                            </button>
                        </div>
                    </div>
                @endif

            @endif {{-- end activeTab --}}

        </div>
    </div>

    {{-- ===== BULK DELETE CONFIRM MODAL ===== --}}
    @if($showBulkDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-navy/70 backdrop-blur-sm" wire:click="$set('showBulkDeleteConfirm', false)"></div>
            <div class="relative w-full max-w-sm dark:bg-dark bg-white rounded-2xl shadow-2xl
                        dark:border dark:border-slate-700 border border-gray-200 p-6">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
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
                                   dark:hover:bg-slate-700 hover:bg-gray-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button @click="$wire.bulkDelete(selectedIds)"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-red-500 text-white hover:bg-red-600 rounded-xl transition-colors
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
                                   dark:hover:bg-slate-700 hover:bg-gray-200 rounded-xl transition-colors">
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
                                   dark:hover:bg-slate-700 hover:bg-gray-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button @click="$wire.bulkChangeCategory(selectedIds)"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-primary text-white hover:bg-accent rounded-xl transition-colors
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
                    <h3 class="font-heading font-bold text-base dark:text-white text-gray-900 mb-1">Change Payment Mode</h3>
                    <p class="text-xs dark:text-slate-500 text-gray-400 font-body">
                        Update payment mode on <span x-text="selectedIds.length"></span> <span x-text="selectedIds.length === 1 ? 'entry' : 'entries'"></span>
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
                        <span class="text-sm font-body dark:text-slate-400 text-gray-500 italic">None (clear payment mode)</span>
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
                                   dark:hover:bg-slate-700 hover:bg-gray-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button @click="$wire.bulkChangePaymentMode(selectedIds)"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2.5 text-sm font-semibold font-body
                                   bg-primary text-white hover:bg-accent rounded-xl transition-colors
                                   disabled:opacity-50">
                        <span wire:loading.remove wire:target="bulkChangePaymentMode">Apply</span>
                        <span wire:loading wire:target="bulkChangePaymentMode">Applying…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== ENTRY SLIDE-OVER ===== --}}
    <div x-data="{ show: $wire.entangle('showEntryPanel').live }">

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

    {{-- Panel --}}
    <div x-cloak
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 w-full max-w-lg z-50 overflow-y-auto
                dark:bg-dark bg-white
                dark:border-l dark:border-slate-700 border-l border-gray-200
                shadow-2xl dark:shadow-black/60 shadow-black/20">

        {{-- Panel header --}}
        <div class="px-6 py-5
                    dark:border-b dark:border-slate-700 border-b border-gray-100
                    sticky top-0 dark:bg-dark bg-white z-10">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs dark:text-slate-500 text-gray-400 font-body">{{ $book->name }} · {{ $business->currency }}</p>
                <button @click="show = false"
                        class="p-1.5 rounded-xl dark:text-slate-500 text-gray-400
                               dark:hover:bg-slate-800 hover:bg-gray-100 dark:hover:text-white hover:text-gray-700
                               transition-all duration-150">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Type toggle (always visible) --}}
            <div class="grid grid-cols-2 gap-1.5 p-1.5 dark:bg-slate-900 bg-gray-100 rounded-xl">
                <button type="button" wire:click="$set('entryType', 'in')"
                        class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold font-body transition-all duration-200
                               {{ $entryType === 'in'
                                   ? 'bg-emerald-500 text-white'
                                   : 'dark:text-slate-400 text-gray-600 dark:hover:text-white hover:text-gray-900' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Cash In
                </button>
                <button type="button" wire:click="$set('entryType', 'out')"
                        class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold font-body transition-all duration-200
                               {{ $entryType === 'out'
                                   ? 'bg-red-500 text-white'
                                   : 'dark:text-slate-400 text-gray-600 dark:hover:text-white hover:text-gray-900' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                    </svg>
                    Cash Out
                </button>
            </div>
        </div>

        {{-- Form fields --}}
        <div class="px-6 py-5 space-y-4">

            {{-- ── AI Scan Receipt (new entries only, non-viewer) ── --}}
            @if(!$editingEntryId && $userRole !== 'viewer')
            <div x-data @open-ocr-picker.window="$refs.ocrInput.click()">

                {{-- Hidden OCR file input --}}
                <input type="file"
                       wire:model="ocrFile"
                       accept=".png,.jpg,.jpeg"
                       class="hidden"
                       x-ref="ocrInput">

                {{-- Scanning state (shown while wire:loading on ocrFile) --}}
                <div wire:loading wire:target="ocrFile">
                    <div class="relative overflow-hidden rounded-2xl border dark:border-violet-500/20 border-violet-200 dark:bg-violet-500/5 bg-violet-50 p-4">
                        {{-- Shimmer sweep --}}
                        <div class="absolute inset-0 -translate-x-full animate-[shimmer_1.5s_infinite]"
                             style="background: linear-gradient(90deg, transparent, rgba(167,139,250,0.08), transparent)"></div>
                        <div class="flex items-center gap-3">
                            <div class="relative flex-shrink-0">
                                <div class="w-9 h-9 rounded-full dark:bg-violet-500/20 bg-violet-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-violet-400 animate-pulse" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                                    </svg>
                                </div>
                                <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 dark:border-dark border-white border-t-transparent bg-transparent animate-spin"
                                     style="border-top-color: rgb(167,139,250)"></div>
                            </div>
                            <div>
                                <p class="text-sm font-semibold font-body dark:text-violet-300 text-violet-700">Reading your receipt with AI…</p>
                                <p class="text-xs font-body dark:text-violet-400/50 text-violet-400 mt-0.5">Extracting amount, date and description</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Default / success / error states --}}
                <div wire:loading.remove wire:target="ocrFile">

                    @if($scanError)
                        {{-- Error state --}}
                        <div class="rounded-2xl border dark:border-red-500/20 border-red-200 dark:bg-red-500/5 bg-red-50 p-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full dark:bg-red-500/10 bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold font-body dark:text-red-400 text-red-600">Scan failed</p>
                                    <p class="text-xs font-body dark:text-red-400/60 text-red-500 mt-0.5">{{ $scanError }}</p>
                                </div>
                                <button wire:click="clearOcrScan" type="button"
                                        class="p-1 rounded-lg dark:text-red-400/40 text-red-400 dark:hover:text-red-400 hover:text-red-600 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                    @elseif(!empty($aiFilledFields))
                        {{-- Success state --}}
                        <div class="rounded-2xl border dark:border-emerald-500/20 border-emerald-200 dark:bg-emerald-500/5 bg-emerald-50 p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full dark:bg-emerald-500/15 bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold font-body dark:text-emerald-300 text-emerald-700">
                                        AI filled {{ count($aiFilledFields) }} {{ count($aiFilledFields) === 1 ? 'field' : 'fields' }}
                                    </p>
                                    @if($ocrOriginalAmount && $ocrConvertedAt)
                                        <p class="text-xs font-mono dark:text-emerald-400/70 text-emerald-600 mt-0.5">
                                            {{ $ocrOriginalAmount }} → {{ $business->currency }} {{ number_format((float)$entryAmount, 2) }}
                                            <span class="dark:text-emerald-500/40 text-emerald-400">· {{ $ocrConvertedAt }}</span>
                                        </p>
                                    @else
                                        <p class="text-xs font-body dark:text-emerald-400/50 text-emerald-500 mt-0.5">Review and edit anything before saving</p>
                                    @endif
                                </div>
                                <button wire:click="clearOcrScan" type="button"
                                        class="text-xs font-semibold font-body dark:text-emerald-400/60 text-emerald-500 dark:hover:text-emerald-400 hover:text-emerald-600 underline underline-offset-2 transition-colors flex-shrink-0">
                                    Scan another
                                </button>
                            </div>
                        </div>

                    @else
                        {{-- Default state — main Scan Receipt button --}}
                        <button wire:click="prepareScan" type="button"
                                class="group w-full rounded-2xl border transition-all duration-200
                                       dark:border-slate-700/80 border-gray-200
                                       dark:hover:border-violet-500/30 hover:border-violet-300
                                       dark:bg-slate-800/40 bg-white
                                       dark:hover:bg-violet-500/5 hover:bg-violet-50/60
                                       py-3.5 px-4">
                            <div class="flex items-center gap-3.5">
                                {{-- Icon --}}
                                <div class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center
                                            dark:bg-slate-700 bg-gray-100
                                            dark:group-hover:bg-violet-500/15 group-hover:bg-violet-100
                                            transition-colors duration-200">
                                    <svg class="w-4.5 h-4.5 dark:text-slate-400 text-gray-400 dark:group-hover:text-violet-400 group-hover:text-violet-500 transition-colors duration-200"
                                         style="width:18px;height:18px"
                                         fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                                    </svg>
                                </div>
                                {{-- Text --}}
                                <div class="text-left flex-1 min-w-0">
                                    <p class="text-sm font-semibold font-body dark:text-slate-300 text-gray-700
                                               dark:group-hover:text-white group-hover:text-gray-900 transition-colors duration-200">
                                        Scan Receipt with AI
                                    </p>
                                    <p class="text-xs font-body dark:text-slate-600 text-gray-400 mt-0.5">
                                        Auto-fill fields from a photo or image
                                    </p>
                                </div>
                                {{-- Pro badge or chevron --}}
                                @if($business->isPro())
                                    <svg class="w-4 h-4 dark:text-slate-600 text-gray-300 dark:group-hover:text-slate-400 group-hover:text-gray-400 flex-shrink-0 transition-colors duration-200"
                                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                                    </svg>
                                @else
                                    <span class="flex-shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold font-body tracking-wide bg-amber-500/15 text-amber-400 border border-amber-500/20">
                                        PRO
                                    </span>
                                @endif
                            </div>
                        </button>
                    @endif

                    @error('ocrFile')
                        <p class="text-xs text-red-400 mt-1.5 font-body">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Divider --}}
                @if(empty($aiFilledFields) && !$scanError)
                    <div class="flex items-center gap-3 mt-3 mb-1">
                        <div class="flex-1 h-px dark:bg-slate-800 bg-gray-100"></div>
                        <span class="text-[11px] dark:text-slate-700 text-gray-400 font-body">or fill in manually</span>
                        <div class="flex-1 h-px dark:bg-slate-800 bg-gray-100"></div>
                    </div>
                @endif

            </div>
            @endif
            {{-- ── End AI Scan ── --}}

            {{-- Date --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5 flex items-center gap-2">
                    Date <span class="text-red-400">*</span>
                    @if(in_array('date', $aiFilledFields))<span class="normal-case tracking-normal font-semibold px-1.5 py-0.5 rounded bg-violet-500/15 text-violet-400 text-[10px]">✦ AI</span>@endif
                </label>
                <input type="date"
                       wire:model="entryDate"
                       class="w-full px-4 py-2.5 text-sm font-body
                              dark:bg-slate-800 bg-white
                              dark:border dark:border-slate-700 border border-gray-300
                              dark:text-white text-gray-900 rounded-xl
                              focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150 dark:[color-scheme:dark]">
                @error('entryDate')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

            {{-- Amount --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5 flex items-center gap-2">
                    Amount <span class="text-red-400">*</span>
                    @if(in_array('amount', $aiFilledFields))<span class="normal-case tracking-normal font-semibold px-1.5 py-0.5 rounded bg-violet-500/15 text-violet-400 text-[10px]">✦ AI</span>@endif
                </label>
                <div class="flex">
                    <div class="flex items-center px-3.5
                                dark:bg-slate-800 bg-gray-100
                                dark:border dark:border-slate-700 border border-gray-300 border-r-0
                                rounded-l-xl">
                        <span class="text-xs font-mono dark:text-slate-400 text-gray-500 font-semibold">{{ $business->currency }}</span>
                    </div>
                    <input type="number"
                           wire:model="entryAmount"
                           placeholder="0.00"
                           step="0.01" min="0.01"
                           class="flex-1 px-4 py-2.5 font-mono text-lg font-semibold
                                  dark:bg-slate-800 bg-white
                                  dark:border dark:border-slate-700 border border-gray-300
                                  dark:text-white text-gray-900 rounded-r-xl
                                  placeholder:dark:text-slate-700 placeholder:text-gray-300 placeholder:font-normal
                                  focus:outline-none focus:ring-2
                                  {{ $entryType === 'in' ? 'focus:ring-emerald-500/30 focus:border-emerald-500/50' : 'focus:ring-red-500/30 focus:border-red-500/50' }}
                                  transition-all duration-150">
                </div>
                @error('entryAmount')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5 flex items-center gap-2">
                    Description <span class="text-red-400">*</span>
                    @if(in_array('description', $aiFilledFields))<span class="normal-case tracking-normal font-semibold px-1.5 py-0.5 rounded bg-violet-500/15 text-violet-400 text-[10px]">✦ AI</span>@endif
                </label>
                <input type="text"
                       wire:model="entryDescription"
                       placeholder="e.g. Client payment, Office rent…"
                       maxlength="255"
                       class="w-full px-4 py-2.5 text-sm font-body
                              dark:bg-slate-800 bg-white
                              dark:border dark:border-slate-700 border border-gray-300
                              dark:text-white text-gray-900 rounded-xl
                              placeholder:dark:text-slate-600 placeholder:text-gray-400
                              focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150">
                @error('entryDescription')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5 flex items-center gap-2">
                    Category
                    @if(in_array('category', $aiFilledFields))<span class="normal-case tracking-normal font-semibold px-1.5 py-0.5 rounded bg-violet-500/15 text-violet-400 text-[10px]">✦ AI</span>@endif
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
                        <button type="button"
                                @click="open = !open"
                                @click.outside="open = false; search = ''"
                                class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-body
                                       dark:bg-slate-800 bg-white
                                       dark:border dark:border-slate-700 border border-gray-300
                                       dark:text-white text-gray-900 rounded-xl
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
                                <input x-model="search" type="text" placeholder="Search categories…"
                                       class="w-full px-3 py-1.5 text-sm font-body
                                              dark:bg-slate-800 bg-gray-50
                                              dark:border dark:border-slate-600 border border-gray-200
                                              dark:text-white text-gray-900 rounded-lg
                                              placeholder:dark:text-slate-600 placeholder:text-gray-400
                                              focus:outline-none focus:ring-1 focus:ring-primary/40">
                            </div>
                            <div class="max-h-44 overflow-y-auto py-1">
                                @if($categories->isEmpty())
                                    <p class="text-xs dark:text-slate-600 text-gray-400 text-center py-4 font-body">No categories yet</p>
                                @else
                                    @foreach($categories as $cat)
                                        <button type="button"
                                                x-show="search === '' || '{{ strtolower(addslashes($cat->name)) }}'.includes(search.toLowerCase())"
                                                @click.stop="$wire.set('entryCategory', '{{ addslashes($cat->name) }}'); open = false; search = ''"
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
                        <button wire:click="$set('entryCategory', '')" class="mt-1 text-[11px] dark:text-slate-600 text-gray-400 hover:text-red-400 font-body transition-colors">
                            Clear selection
                        </button>
                    @endif
                @endif
            </div>

            {{-- Payment Mode --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5 flex items-center gap-2">
                    Payment Mode
                    @if(in_array('payment_mode', $aiFilledFields))<span class="normal-case tracking-normal font-semibold px-1.5 py-0.5 rounded bg-violet-500/15 text-violet-400 text-[10px]">✦ AI</span>@endif
                </label>
                @if($showAddPaymentMode)
                    <div class="dark:bg-slate-800 bg-gray-50 rounded-xl p-3 space-y-2 dark:border dark:border-slate-700 border border-gray-200">
                        <p class="text-xs font-semibold dark:text-slate-300 text-gray-700 font-body">Add New Payment Mode</p>
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
                        <button type="button"
                                @click="open = !open"
                                @click.outside="open = false; search = ''"
                                class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-body
                                       dark:bg-slate-800 bg-white
                                       dark:border dark:border-slate-700 border border-gray-300
                                       dark:text-white text-gray-900 rounded-xl
                                       focus:outline-none transition-all duration-150
                                       {{ $entryPaymentMode ? '' : 'dark:text-slate-500 text-gray-400' }}">
                            <span>{{ $entryPaymentMode ?: 'Select payment mode' }}</span>
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
                                <input x-model="search" type="text" placeholder="Search modes…"
                                       class="w-full px-3 py-1.5 text-sm font-body
                                              dark:bg-slate-800 bg-gray-50
                                              dark:border dark:border-slate-600 border border-gray-200
                                              dark:text-white text-gray-900 rounded-lg
                                              placeholder:dark:text-slate-600 placeholder:text-gray-400
                                              focus:outline-none focus:ring-1 focus:ring-primary/40">
                            </div>
                            <div class="max-h-44 overflow-y-auto py-1">
                                @if($paymentModes->isEmpty())
                                    <p class="text-xs dark:text-slate-600 text-gray-400 text-center py-4 font-body">No payment modes yet</p>
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
                                    Add New Payment Mode
                                </button>
                            </div>
                        </div>
                    </div>
                    @if($entryPaymentMode)
                        <button wire:click="$set('entryPaymentMode', '')" class="mt-1 text-[11px] dark:text-slate-600 text-gray-400 hover:text-red-400 font-body transition-colors">
                            Clear selection
                        </button>
                    @endif
                @endif
            </div>

            {{-- Reference (optional) --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                    Reference
                    <span class="normal-case tracking-normal font-normal dark:text-slate-600 text-gray-400 ml-1">· optional</span>
                </label>
                <input type="text"
                       wire:model="entryReference"
                       placeholder="Invoice, receipt, or PO number"
                       maxlength="100"
                       class="w-full px-4 py-2.5 text-sm font-mono
                              dark:bg-slate-800 bg-white
                              dark:border dark:border-slate-700 border border-gray-300
                              dark:text-white text-gray-900 rounded-xl
                              placeholder:dark:text-slate-600 placeholder:text-gray-400 placeholder:font-body
                              focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                              transition-all duration-150">
                @error('entryReference')<p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>@enderror
            </div>

            {{-- Attachment (receipt / invoice) --}}
            @if($userRole !== 'viewer')
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                        Attachment
                        <span class="normal-case tracking-normal font-normal dark:text-slate-600 text-gray-400 ml-1">· optional · max 2MB</span>
                    </label>

                    {{-- Existing attachment (when editing) --}}
                    @if($existingAttachmentPath && !$removeAttachment)
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl
                                    dark:bg-slate-800 bg-gray-50
                                    dark:border dark:border-slate-700 border border-gray-200">
                            @php $ext = strtolower(pathinfo($existingAttachmentPath, PATHINFO_EXTENSION)); @endphp
                            @if($ext === 'pdf')
                                <svg class="w-5 h-5 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
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
                                    title="Remove attachment">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @else
                        {{-- New upload / replace --}}
                        @if($entryAttachment)
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl
                                        dark:bg-slate-800 bg-emerald-50
                                        dark:border dark:border-emerald-500/30 border border-emerald-200">
                                <svg class="w-5 h-5 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
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
                                        title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @else
                            <label class="group flex items-center gap-3 px-4 py-3 rounded-xl cursor-pointer
                                          dark:bg-slate-800 bg-white
                                          dark:border dark:border-slate-700 border border-gray-300 border-dashed
                                          dark:hover:border-primary/50 hover:border-primary/40
                                          transition-all duration-150">
                                <svg class="w-5 h-5 dark:text-slate-600 text-gray-400 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                                </svg>
                                <span class="text-sm dark:text-slate-500 text-gray-400 font-body group-hover:dark:text-slate-400 group-hover:text-gray-500 transition-colors">
                                    Attach receipt or invoice
                                </span>
                                <input type="file"
                                       wire:model="entryAttachment"
                                       accept=".png,.jpg,.jpeg,.pdf"
                                       class="hidden">
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
                        <p class="text-xs text-red-400 mt-1 font-body">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- Recurring toggle (new entries only, non-viewer) --}}
            @if(!$editingEntryId && $userRole !== 'viewer')
                <div class="border-t dark:border-slate-700 border-gray-200 pt-4 mt-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-body font-medium dark:text-slate-300 text-gray-700">Repeat this entry</label>
                            <p class="text-[11px] dark:text-slate-600 text-gray-400 font-body mt-0.5">Auto-create on a schedule</p>
                        </div>
                        <button type="button"
                                wire:click="{{ $entryRecurring ? "\$toggle('entryRecurring')" : "enableRecurring" }}"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none
                                       {{ $entryRecurring ? 'bg-primary' : 'dark:bg-slate-700 bg-gray-200' }}"
                                role="switch"
                                aria-checked="{{ $entryRecurring ? 'true' : 'false' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                         {{ $entryRecurring ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </div>

                    @if($entryRecurring)
                        <div class="mt-4 space-y-4">
                            {{-- Frequency selector --}}
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-2">Frequency</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly'] as $freqVal => $freqLabel)
                                        <button type="button"
                                                wire:click="$set('entryFrequency', '{{ $freqVal }}')"
                                                class="px-3.5 py-2 rounded-xl text-sm font-body font-medium transition-all duration-150
                                                       {{ $entryFrequency === $freqVal
                                                           ? 'bg-primary/10 border-primary/50 text-primary ring-2 ring-primary/30 border'
                                                           : 'dark:border-slate-700 border-gray-300 dark:text-slate-400 text-gray-500 border dark:hover:border-slate-600 hover:border-gray-400' }}">
                                            {{ $freqLabel }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- End date (optional) --}}
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-500 text-gray-500 font-body mb-1.5">
                                    End Date
                                    <span class="normal-case tracking-normal font-normal dark:text-slate-600 text-gray-400 ml-1">· optional</span>
                                </label>
                                <input type="date"
                                       wire:model="entryEndsAt"
                                       class="w-full max-w-[200px] px-4 py-2.5 text-sm font-body
                                              dark:bg-slate-800 bg-white
                                              dark:border dark:border-slate-700 border border-gray-300
                                              dark:text-white text-gray-900 rounded-xl
                                              focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                              transition-all duration-150 dark:[color-scheme:dark]">
                                <p class="mt-1 text-[10px] dark:text-slate-600 text-gray-400 font-body">Leave empty to repeat forever.</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- Panel footer --}}
        <div class="sticky bottom-0 px-6 py-4 flex gap-2
                    dark:bg-dark bg-white
                    dark:border-t dark:border-slate-700 border-t border-gray-100">

            @if(!$editingEntryId)
                {{-- Save & Add New --}}
                <button type="button"
                        wire:click="saveAndAddNew"
                        wire:loading.attr="disabled"
                        class="flex-1 py-2.5 text-sm font-semibold font-body
                               dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                               dark:hover:bg-slate-700 hover:bg-gray-200
                               rounded-xl transition-all duration-200 disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveAndAddNew">Save &amp; Add New</span>
                    <span wire:loading wire:target="saveAndAddNew">Saving…</span>
                </button>
            @else
                <button type="button"
                        @click="show = false"
                        class="flex-1 py-2.5 text-sm font-semibold font-body
                               dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700
                               dark:hover:bg-slate-700 hover:bg-gray-200
                               rounded-xl transition-all duration-200">
                    Cancel
                </button>
            @endif

            {{-- Save --}}
            <button type="button"
                    wire:click="saveEntry"
                    wire:loading.attr="disabled"
                    class="flex-1 py-2.5 text-sm font-semibold font-body
                           text-white rounded-xl transition-all duration-200 shadow-lg
                           {{ $entryType === 'in'
                               ? 'bg-emerald-500 hover:bg-emerald-400 shadow-emerald-500/20'
                               : 'bg-red-500 hover:bg-red-400 shadow-red-500/20' }}
                           disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="saveEntry">
                    {{ $editingEntryId ? 'Save Changes' : 'Save' }}
                </span>
                <span wire:loading wire:target="saveEntry" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
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
