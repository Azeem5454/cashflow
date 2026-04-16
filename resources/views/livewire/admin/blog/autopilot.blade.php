<div class="p-4 sm:p-8" x-data="autopilotPage">

    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('admin.blog.index') }}" class="inline-flex items-center gap-1 text-xs dark:text-slate-400 text-gray-500 hover:text-primary dark:hover:text-blue-light mb-2">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            Back to Blog
        </a>
        <h1 class="font-heading font-bold text-2xl dark:text-white text-gray-900">Blog Autopilot</h1>
        <p class="text-sm dark:text-slate-400 text-gray-500 mt-1">Queue blog titles · AI writes the post + image · auto-publishes daily at 09:00 UTC.</p>
    </div>

    {{-- Image render failure banner --}}
    @if($lastImageError || $missingImagesCount > 0)
        <div class="rounded-xl mb-5 overflow-hidden border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10">
            <div class="px-5 py-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm text-amber-900 dark:text-amber-200">
                        @if($missingImagesCount > 0)
                            {{ $missingImagesCount }} post{{ $missingImagesCount === 1 ? '' : 's' }} published without a featured image
                        @else
                            Last image render failed
                        @endif
                    </p>
                    @if($lastImageError)
                        <p class="text-xs text-amber-800 dark:text-amber-300 mt-1 font-mono break-all">{{ $lastImageError }}</p>
                    @else
                        <p class="text-xs text-amber-800 dark:text-amber-300 mt-1">The post was published but the GD render step didn't complete — probably a missing font or the GD extension on the server.</p>
                    @endif
                    <div class="flex items-center gap-2 mt-3 flex-wrap">
                        @if($missingImagesCount > 0)
                            <button type="button" wire:click="retryMissingImages"
                                    wire:loading.attr="disabled" wire:target="retryMissingImages"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 hover:bg-amber-400 text-white transition-colors inline-flex items-center gap-1.5 disabled:opacity-60">
                                <svg wire:loading.remove wire:target="retryMissingImages" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                <svg wire:loading wire:target="retryMissingImages" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-30"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                <span wire:loading.remove wire:target="retryMissingImages">Retry all missing</span>
                                <span wire:loading wire:target="retryMissingImages">Rendering…</span>
                            </button>
                        @endif
                        @if($lastImageError)
                            <button type="button" wire:click="clearImageError"
                                    class="px-3 py-1.5 text-xs font-medium rounded-lg text-amber-800 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-500/20 transition-colors">
                                Dismiss
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Enable/disable + status --}}
    <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5 mb-5 flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3">
                <span class="font-semibold text-base dark:text-white text-gray-900">
                    Autopilot is
                    <span class="{{ $enabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ $enabled ? 'ON' : 'OFF' }}
                    </span>
                </span>
                @if($enabled)
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                @endif
            </div>
            <p class="text-xs dark:text-slate-400 text-gray-500 mt-1">{{ $nextRunHint }}</p>
            @if($lastRun)
                <p class="text-[11px] dark:text-slate-500 text-gray-400 mt-0.5">Last run: {{ $lastRun->diffForHumans() }} ({{ $lastRun->format('M j, Y H:i') }} UTC)</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <button type="button" wire:click="runNow" wire:loading.attr="disabled" wire:target="runNow"
                    class="px-3.5 py-2 text-xs font-medium rounded-lg dark:bg-slate-800 bg-gray-100 dark:text-slate-200 text-gray-700 dark:hover:bg-slate-700 hover:bg-gray-200 transition-colors inline-flex items-center gap-1.5 disabled:opacity-50">
                <svg wire:loading.remove wire:target="runNow" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                <svg wire:loading wire:target="runNow" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-30"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <span wire:loading.remove wire:target="runNow">Generate Now</span>
                <span wire:loading wire:target="runNow">Generating…</span>
            </button>

            {{-- Toggle pill --}}
            <button type="button" wire:click="toggle"
                    role="switch" :aria-checked="{{ $enabled ? 'true' : 'false' }}"
                    class="relative inline-flex h-7 w-12 flex-shrink-0 items-center rounded-full transition-colors focus:outline-none
                           {{ $enabled ? 'bg-primary' : 'dark:bg-slate-700 bg-gray-300' }}">
                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform
                             {{ $enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
            </button>
        </div>
    </div>

    {{-- Product brief --}}
    <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl mb-5 overflow-hidden">
        <button type="button" wire:click="$toggle('briefEditOpen')"
                class="w-full flex items-center gap-3 px-5 py-4 text-left hover:dark:bg-slate-800/50 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4 text-primary flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-sm dark:text-white text-gray-900">Product brief</h3>
                <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">
                    Injected into every AI post so Claude references real features + pricing instead of inventing them.
                    {{ \App\Helpers\Setting::get('blog_autopilot.product_brief') ? '· Custom brief active' : '· Using default' }}
                </p>
            </div>
            <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 transition-transform {{ $briefEditOpen ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>

        @if($briefEditOpen)
            <div class="px-5 pb-5 pt-1 border-t dark:border-slate-800 border-gray-100">
                <p class="text-[11px] dark:text-slate-500 text-gray-500 mb-2.5 leading-relaxed">
                    Keep it factual. Features, pricing, tiers, voice rules. Markdown-ish plain text is fine — it goes straight into the prompt as reference material.
                    Claude is instructed to reference these facts only when relevant (max 2–3 per post), never to invent features beyond what's listed here.
                </p>
                <textarea wire:model.defer="productBrief" rows="14"
                          class="w-full px-3 py-2.5 text-xs rounded-lg font-mono
                                 dark:bg-slate-800 bg-white
                                 dark:border-slate-700 border border-gray-200
                                 dark:text-slate-200 text-gray-800
                                 dark:placeholder-slate-500 placeholder-gray-400
                                 focus:outline-none focus:border-primary/60 dark:focus:border-primary/60 resize-y leading-relaxed"></textarea>
                @error('productBrief') <p class="mt-1.5 text-xs text-red-500 font-body">{{ $message }}</p> @enderror

                <div class="mt-3 flex items-center gap-2 flex-wrap">
                    <button type="button" wire:click="saveBrief"
                            class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-primary hover:bg-accent text-white transition-colors inline-flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Save brief
                    </button>
                    <button type="button" wire:click="resetBriefToDefault"
                            wire:confirm="Reset the product brief to the default? Your custom text will be replaced."
                            class="px-3.5 py-2 text-xs font-medium rounded-lg dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700 hover:bg-gray-200 transition-colors">
                        Reset to default
                    </button>
                    <button type="button" wire:click="$set('briefEditOpen', false)"
                            class="px-3.5 py-2 text-xs font-medium rounded-lg dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800 hover:bg-gray-100 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Add to queue --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">

        {{-- Bulk paste --}}
        <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5">
            <div class="flex items-center gap-2 mb-1.5">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0 3-3m0 0-3-3m3 3H15"/></svg>
                <h3 class="font-semibold text-sm dark:text-white text-gray-900">Paste many titles</h3>
            </div>
            <p class="text-xs dark:text-slate-400 text-gray-500 mb-3">One title per line. Duplicates and lines under 8 characters are skipped.</p>

            <textarea wire:model.defer="bulkTitles" rows="6"
                      placeholder="How to forecast cash flow in 10 minutes&#10;Freelancer tax deductions most people miss&#10;Cash runway calculation, explained simply"
                      class="w-full px-3 py-2.5 text-sm rounded-lg font-body
                             dark:bg-slate-800 bg-white
                             dark:border-slate-700 border border-gray-200
                             dark:text-white text-gray-900
                             dark:placeholder-slate-500 placeholder-gray-400
                             focus:outline-none focus:border-primary/60 dark:focus:border-primary/60 resize-y"></textarea>
            @error('bulkTitles') <p class="mt-1.5 text-xs text-red-500 font-body">{{ $message }}</p> @enderror

            <button type="button" wire:click="addBulk"
                    class="mt-3 px-3.5 py-2 text-xs font-semibold rounded-lg bg-primary hover:bg-accent text-white transition-colors inline-flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add to queue
            </button>
        </div>

        {{-- Add single --}}
        <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl p-5">
            <div class="flex items-center gap-2 mb-1.5">
                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>
                <h3 class="font-semibold text-sm dark:text-white text-gray-900">Add a single title</h3>
            </div>
            <p class="text-xs dark:text-slate-400 text-gray-500 mb-3">Optionally pick a category — leave empty to let the autopilot choose.</p>

            <div class="space-y-3">
                <div>
                    <input type="text" wire:model.defer="newTitle"
                           placeholder="e.g. The 20 expense categories every business should track"
                           class="w-full px-3 py-2.5 text-sm rounded-lg font-body
                                  dark:bg-slate-800 bg-white
                                  dark:border-slate-700 border border-gray-200
                                  dark:text-white text-gray-900
                                  dark:placeholder-slate-500 placeholder-gray-400
                                  focus:outline-none focus:border-primary/60 dark:focus:border-primary/60">
                    @error('newTitle') <p class="mt-1.5 text-xs text-red-500 font-body">{{ $message }}</p> @enderror
                </div>

                <select wire:model.defer="newCategoryId"
                        class="w-full px-3 py-2.5 text-sm rounded-lg font-body
                               dark:bg-slate-800 bg-white
                               dark:border-slate-700 border border-gray-200
                               dark:text-white text-gray-900
                               focus:outline-none focus:border-primary/60 dark:focus:border-primary/60">
                    <option value="">— Auto-pick category —</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>

                <button type="button" wire:click="addSingle"
                        class="px-3.5 py-2 text-xs font-semibold rounded-lg bg-primary hover:bg-accent text-white transition-colors inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add to queue
                </button>
            </div>
        </div>
    </div>

    {{-- Queue list --}}
    <div class="dark:bg-slate-900 bg-white dark:border-slate-800 border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b dark:border-slate-800 border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="font-heading font-bold text-base dark:text-white text-gray-900">Queue</h2>
                <p class="text-[11px] dark:text-slate-500 text-gray-400 mt-0.5">
                    The top row publishes next. Drag rows to reorder.
                </p>
            </div>
            <span class="px-2.5 py-1 text-[11px] font-bold rounded-full dark:bg-slate-800 bg-gray-100 dark:text-slate-300 text-gray-700">
                {{ $items->count() }} queued
            </span>
        </div>

        @if($items->isEmpty())
            <div class="p-10 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-full dark:bg-slate-800 bg-gray-100 flex items-center justify-center">
                    <svg class="w-5 h-5 dark:text-slate-500 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                </div>
                <p class="text-sm font-semibold dark:text-slate-300 text-gray-700">Queue is empty</p>
                <p class="text-xs dark:text-slate-500 text-gray-500 mt-1">Paste titles above to get started.</p>
            </div>
        @else
            <ul id="queue-list" wire:ignore.self
                x-ref="queue"
                x-init="mountSortable($refs.queue)"
                class="divide-y dark:divide-slate-800 divide-gray-100">
                @foreach($items as $idx => $it)
                    <li data-id="{{ $it->id }}" wire:key="q-{{ $it->id }}"
                        class="flex items-center gap-3 px-5 py-3 group hover:dark:bg-slate-800/50 hover:bg-gray-50 transition-colors">

                        {{-- Drag handle --}}
                        <button type="button" class="drag-handle cursor-grab active:cursor-grabbing p-1.5 dark:text-slate-500 text-gray-400 hover:dark:text-slate-300 hover:text-gray-700" aria-label="Drag to reorder">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm0 5a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm0 5a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm6-10a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm0 5a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm0 5a1 1 0 1 1 0 2 1 1 0 0 1 0-2Z"/></svg>
                        </button>

                        {{-- Position badge --}}
                        <span class="w-7 h-7 flex-shrink-0 inline-flex items-center justify-center rounded-lg text-[11px] font-bold
                                     {{ $idx === 0 ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/30' : 'dark:bg-slate-800 bg-gray-100 dark:text-slate-400 text-gray-500' }}">
                            {{ $idx + 1 }}
                        </span>

                        {{-- Title --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold dark:text-white text-gray-900 truncate">{{ $it->title }}</p>
                            @if($idx === 0)
                                <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mt-0.5">Next up</p>
                            @endif
                        </div>

                        {{-- Category select --}}
                        <select x-on:change="$wire.updateCategory('{{ $it->id }}', $event.target.value)"
                                class="text-xs px-2 py-1.5 rounded-lg font-body
                                       dark:bg-slate-800 bg-gray-100
                                       dark:border-slate-700 border border-gray-200
                                       dark:text-slate-300 text-gray-700
                                       focus:outline-none focus:border-primary/60 hidden sm:block">
                            <option value="" @if(!$it->category_id) selected @endif>— Auto-pick —</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" @if($it->category_id === $c->id) selected @endif>{{ $c->name }}</option>
                            @endforeach
                        </select>

                        {{-- Delete --}}
                        <button type="button"
                                wire:click="deleteItem('{{ $it->id }}')"
                                wire:confirm="Remove this title from the queue?"
                                class="p-1.5 rounded-lg dark:text-slate-500 text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:dark:bg-red-500/10 hover:bg-red-50 transition-colors"
                                aria-label="Remove from queue">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Toast --}}
    <div wire:ignore
         x-data="{ show: false, msg: '', error: false, timer: null }"
         x-on:autopilot-toast.window="
            msg = $event.detail.message;
            error = !!$event.detail.error;
            show = true;
            clearTimeout(timer);
            timer = setTimeout(() => show = false, 4500);
         "
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 z-50 max-w-md"
         x-cloak>
        <div class="px-4 py-3 rounded-xl shadow-2xl border text-sm font-body flex items-center gap-2.5"
             :class="error ? 'bg-red-500 border-red-400 text-white' : 'bg-gray-900 dark:bg-slate-800 border-slate-700 text-white'">
            <svg x-show="!error" class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            <svg x-show="error" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
            <span x-text="msg" class="flex-1"></span>
        </div>
    </div>

    {{-- SortableJS via CDN + Alpine glue --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        function autopilotPage() {
            return {
                _sortable: null,
                mountSortable(el) {
                    if (!el || !window.Sortable) return;
                    // Destroy any previous instance (Livewire morphdom can reuse DOM)
                    if (this._sortable) this._sortable.destroy();
                    this._sortable = new Sortable(el, {
                        handle: '.drag-handle',
                        animation: 160,
                        ghostClass: 'opacity-40',
                        onEnd: () => {
                            const ids = Array.from(el.querySelectorAll('[data-id]')).map(n => n.dataset.id);
                            this.$wire.reorder(ids);
                        },
                    });
                },
            };
        }

        // Re-mount after Livewire re-renders the list (e.g., after add/delete)
        document.addEventListener('livewire:navigated', () => {
            const el = document.getElementById('queue-list');
            if (el && el.__x) el.__x.$data.mountSortable(el);
        });
    </script>
</div>
