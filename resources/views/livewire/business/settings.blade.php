<div class="min-h-full"
     x-data="{
         tab: 'general',
         savedToast: false,
         showSavedToast() { this.savedToast = true; setTimeout(() => this.savedToast = false, 2500); }
     }"
     @general-saved.window="showSavedToast()">

    {{-- ===== STICKY HEADER ===== --}}
    <div class="px-6 lg:px-8 py-5
                dark:bg-[#080d1a] bg-white
                dark:border-b dark:border-slate-800 border-b border-gray-200
                sticky top-0 z-10 backdrop-blur-sm">
        <div class="max-w-3xl mx-auto">

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-1.5 text-sm mb-3">
                <a href="{{ route('businesses.show', $business) }}"
                   class="inline-flex items-center gap-1.5 dark:text-slate-500 text-gray-400
                          hover:text-primary dark:hover:text-primary transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                    {{ $business->name }}
                </a>
                <span class="dark:text-slate-700 text-gray-300">/</span>
                <span class="dark:text-slate-300 text-gray-600 font-medium">Settings</span>
            </div>

            <div class="flex items-center justify-between gap-4">
                <h1 class="font-display font-extrabold text-2xl dark:text-white text-gray-900 tracking-tight leading-none">
                    Settings
                </h1>

                {{-- Tab switcher --}}
                <div class="flex items-center gap-1 dark:bg-slate-800/60 bg-gray-100 rounded-xl p-1">
                    <button @click="tab = 'general'"
                            :class="tab === 'general'
                                ? 'dark:bg-[#1e293b] bg-white dark:text-white text-gray-900 shadow-sm'
                                : 'dark:text-slate-400 text-gray-500 dark:hover:text-slate-300 hover:text-gray-700'"
                            class="px-4 py-1.5 text-sm font-semibold rounded-lg transition-all duration-150">
                        General
                    </button>
                    <button @click="tab = 'team'"
                            :class="tab === 'team'
                                ? 'dark:bg-[#1e293b] bg-white dark:text-white text-gray-900 shadow-sm'
                                : 'dark:text-slate-400 text-gray-500 dark:hover:text-slate-300 hover:text-gray-700'"
                            class="relative px-4 py-1.5 text-sm font-semibold rounded-lg transition-all duration-150">
                        Team
                        @if($pending->isNotEmpty())
                            <span class="ml-1 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold
                                         dark:bg-primary/20 bg-primary/10 text-primary rounded-full">
                                {{ $pending->count() }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== SAVED TOAST ===== --}}
    <div x-show="savedToast"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         x-cloak
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50">
        <div class="flex items-center gap-2.5 px-4 py-3
                    dark:bg-[#1e293b] bg-white
                    dark:border-slate-700/60 border border-gray-200
                    rounded-2xl shadow-xl shadow-black/20">
            <div class="w-5 h-5 rounded-full bg-green-500/15 flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-green-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
            </div>
            <p class="text-sm font-semibold dark:text-white text-gray-900">Changes saved</p>
        </div>
    </div>

    {{-- ===== CONTENT ===== --}}
    <div class="px-6 lg:px-8 py-7 max-w-3xl mx-auto space-y-5">

        {{-- =========================================== --}}
        {{-- GENERAL TAB                                  --}}
        {{-- =========================================== --}}
        <div x-show="tab === 'general'" x-cloak class="space-y-5">

            {{-- Business Details Card --}}
            <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 dark:border-b dark:border-slate-700/40 border-b border-gray-100">
                    <h2 class="font-heading font-bold text-base dark:text-white text-gray-900">Business Details</h2>
                    <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">Update your business name and description.</p>
                </div>
                <div class="p-6 space-y-4">

                    {{-- Name --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-500 mb-2">
                            Business Name
                        </label>
                        <input wire:model="name"
                               type="text"
                               placeholder="e.g. Eveso IT Company"
                               class="w-full px-4 py-2.5 text-sm rounded-xl
                                      dark:bg-navy bg-gray-50
                                      dark:border-slate-700 border-gray-200 border
                                      dark:text-white text-gray-900
                                      dark:placeholder-slate-600 placeholder-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                      transition-all duration-150">
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider dark:text-slate-400 text-gray-500 mb-2">
                            Description <span class="normal-case font-normal dark:text-slate-500 text-gray-400">(optional)</span>
                        </label>
                        <textarea wire:model="description"
                                  rows="3"
                                  placeholder="What does this business do?"
                                  class="w-full px-4 py-2.5 text-sm rounded-xl resize-none
                                         dark:bg-navy bg-gray-50
                                         dark:border-slate-700 border-gray-200 border
                                         dark:text-white text-gray-900
                                         dark:placeholder-slate-600 placeholder-gray-400
                                         focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                         transition-all duration-150"></textarea>
                        @error('description')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end pt-1">
                        <button wire:click="saveGeneral"
                                wire:loading.attr="disabled"
                                wire:target="saveGeneral"
                                wire:loading.class="opacity-70 cursor-wait"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold
                                       bg-primary hover:bg-accent text-white rounded-xl
                                       transition-all duration-200 shadow-md shadow-primary/25
                                       disabled:opacity-70 disabled:cursor-wait">
                            <span wire:loading.remove wire:target="saveGeneral">Save Changes</span>
                            <span wire:loading wire:target="saveGeneral">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Danger Zone Card --}}
            <div class="dark:bg-red-950/20 bg-red-50/60 dark:border-red-900/40 border border-red-200 rounded-2xl overflow-hidden"
                 x-data="{ confirm: false }">
                <div class="px-6 py-4 dark:border-b dark:border-red-900/30 border-b border-red-100">
                    <h2 class="font-heading font-bold text-base text-red-600 dark:text-red-400">Danger Zone</h2>
                    <p class="text-xs text-red-400/70 dark:text-red-400/60 mt-0.5">These actions are permanent and cannot be undone.</p>
                </div>
                <div class="p-6">
                    {{-- Default state --}}
                    <div class="flex items-start justify-between gap-4" x-show="!confirm">
                        <div>
                            <p class="text-sm font-semibold dark:text-white text-gray-900">Delete this business</p>
                            <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5 leading-relaxed">
                                Permanently deletes all books, entries, and team access.
                            </p>
                        </div>
                        <button @click="confirm = true"
                                class="flex-shrink-0 px-4 py-2 text-sm font-semibold rounded-xl
                                       dark:bg-red-500/10 bg-red-100 text-red-600 dark:text-red-400
                                       hover:bg-red-500 hover:text-white dark:hover:bg-red-500 dark:hover:text-white
                                       transition-all duration-150">
                            Delete Business
                        </button>
                    </div>

                    {{-- Confirm state --}}
                    <div x-show="confirm" x-cloak class="space-y-3">
                        <p class="text-sm dark:text-slate-300 text-gray-700 leading-relaxed">
                            Type <strong class="dark:text-white text-gray-900 font-mono text-xs bg-gray-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $business->name }}</strong> to confirm:
                        </p>
                        <input wire:model="deleteConfirmInput"
                               type="text"
                               placeholder="{{ $business->name }}"
                               class="w-full px-4 py-2.5 text-sm rounded-xl
                                      dark:bg-navy bg-white
                                      dark:border-red-800/50 border-red-300 border
                                      dark:text-white text-gray-900
                                      dark:placeholder-slate-600 placeholder-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-red-500
                                      transition-all duration-150">
                        @error('deleteConfirmInput')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <div class="flex items-center gap-3">
                            <button wire:click="deleteBusiness"
                                    wire:loading.attr="disabled"
                                    wire:target="deleteBusiness"
                                    class="px-4 py-2 text-sm font-semibold
                                           bg-red-500 hover:bg-red-600 text-white
                                           rounded-xl transition-all duration-150
                                           disabled:opacity-70 disabled:cursor-wait">
                                <span wire:loading.remove wire:target="deleteBusiness">Permanently Delete</span>
                                <span wire:loading wire:target="deleteBusiness">Deleting…</span>
                            </button>
                            <button @click="confirm = false; $wire.set('deleteConfirmInput', '')"
                                    class="px-4 py-2 text-sm font-medium
                                           dark:text-slate-400 text-gray-500
                                           dark:hover:text-white hover:text-gray-900
                                           transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =========================================== --}}
        {{-- TEAM TAB                                     --}}
        {{-- =========================================== --}}
        <div x-show="tab === 'team'" x-cloak class="space-y-5">

            {{-- Invite Member Card --}}
            <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl">
                <div class="px-6 py-4 dark:border-b dark:border-slate-700/40 border-b border-gray-100 rounded-t-2xl">
                    <h2 class="font-heading font-bold text-base dark:text-white text-gray-900">Invite a Team Member</h2>
                    <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">They'll receive an email with a link to join. Invitation expires in 72 hours.</p>
                </div>
                <div class="p-6">

                    @if($inviteSent)
                        <div class="flex items-center gap-3 px-4 py-3
                                    dark:bg-green-500/10 bg-green-50
                                    dark:border-green-500/20 border border-green-200
                                    rounded-xl mb-4">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                            </svg>
                            <p class="text-sm font-medium dark:text-green-400 text-green-700">Invitation sent successfully.</p>
                        </div>
                    @endif

                    <div class="flex gap-3 flex-col sm:flex-row">
                        {{-- Email input --}}
                        <div class="flex-1">
                            <input wire:model="inviteEmail"
                                   type="email"
                                   placeholder="colleague@example.com"
                                   class="w-full px-4 py-2.5 text-sm rounded-xl
                                          dark:bg-navy bg-gray-50
                                          dark:border-slate-700 border-gray-200 border
                                          dark:text-white text-gray-900
                                          dark:placeholder-slate-600 placeholder-gray-400
                                          focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary
                                          transition-all duration-150">
                            @error('inviteEmail')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Role picker (Alpine custom dropdown) --}}
                        <div class="relative flex-shrink-0"
                             x-data="{ open: false, role: $wire.entangle('inviteRole') }">
                            <button type="button"
                                    @click="open = !open"
                                    class="flex items-center gap-2 w-full sm:w-auto px-4 py-2.5 text-sm font-semibold rounded-xl
                                           dark:bg-navy bg-gray-50
                                           dark:border-slate-700 border-gray-200 border
                                           dark:text-white text-gray-900
                                           focus:outline-none focus:ring-2 focus:ring-primary/50
                                           transition-all duration-150 whitespace-nowrap">
                                <span x-text="role === 'editor' ? 'Editor' : 'Viewer'"></span>
                                <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 transition-transform duration-150"
                                     :class="open ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                </svg>
                            </button>
                            <div x-show="open"
                                 @click.outside="open = false"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 top-full mt-1.5 z-20 w-52
                                        dark:bg-[#1e293b] bg-white
                                        dark:border-slate-700/60 border border-gray-200
                                        rounded-xl shadow-xl shadow-black/20 overflow-hidden">
                                <button type="button"
                                        @click="role = 'editor'; open = false"
                                        class="w-full px-4 py-3 text-left transition-colors
                                               hover:dark:bg-slate-700/40 hover:bg-gray-50">
                                    <p class="text-sm font-semibold dark:text-white text-gray-900">Editor</p>
                                    <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">Can add, edit & delete entries</p>
                                </button>
                                <div class="dark:border-slate-700/40 border-gray-100 border-t"></div>
                                <button type="button"
                                        @click="role = 'viewer'; open = false"
                                        class="w-full px-4 py-3 text-left transition-colors
                                               hover:dark:bg-slate-700/40 hover:bg-gray-50">
                                    <p class="text-sm font-semibold dark:text-white text-gray-900">Viewer</p>
                                    <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">Read-only access</p>
                                </button>
                            </div>
                        </div>

                        {{-- Send button --}}
                        <button wire:click="sendInvite"
                                wire:loading.attr="disabled"
                                wire:target="sendInvite"
                                wire:loading.class="opacity-70 cursor-wait"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold
                                       bg-primary hover:bg-accent text-white rounded-xl
                                       transition-all duration-200 shadow-md shadow-primary/25 whitespace-nowrap
                                       disabled:opacity-70 disabled:cursor-wait flex-shrink-0">
                            <span wire:loading.remove wire:target="sendInvite" class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                                </svg>
                                Send Invite
                            </span>
                            <span wire:loading wire:target="sendInvite" class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Sending…
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Team Members Card --}}
            <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 dark:border-b dark:border-slate-700/40 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-heading font-bold text-base dark:text-white text-gray-900">Team Members</h2>
                        <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">
                            {{ $members->count() }} {{ Str::plural('member', $members->count()) }}
                        </p>
                    </div>
                    @if(!auth()->user()->isPro())
                        <div class="text-right">
                            <p class="text-xs dark:text-slate-500 text-gray-400">
                                <span class="font-mono font-semibold dark:text-slate-300 text-gray-600">{{ $members->count() }}</span> / 2 members
                            </p>
                            @if($members->count() >= 2)
                                <a href="{{ route('billing') }}"
                                   class="text-xs text-primary hover:text-accent transition-colors">
                                    Upgrade for unlimited →
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="divide-y dark:divide-slate-700/40 divide-gray-100">
                    @foreach($members as $member)
                        @php
                            $memberRole = $member->pivot->role ?? 'viewer';
                            $isOwner = $member->id === $business->owner_id;
                        @endphp
                        <div class="px-6 py-4 flex items-center gap-4"
                             x-data="{ confirmRemove: false }">

                            {{-- Avatar --}}
                            <div class="w-9 h-9 rounded-full bg-primary/10 dark:bg-primary/15 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-primary">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold dark:text-white text-gray-900 truncate">{{ $member->name }}</p>
                                    @if($isOwner)
                                        <span class="flex-shrink-0 text-[10px] font-bold uppercase tracking-wider
                                                     dark:bg-primary/20 bg-primary/10 text-primary
                                                     px-2 py-0.5 rounded-full">
                                            Owner
                                        </span>
                                    @endif
                                    @if($member->id === auth()->id() && !$isOwner)
                                        <span class="flex-shrink-0 text-[10px] dark:text-slate-500 text-gray-400">(you)</span>
                                    @endif
                                </div>
                                <p class="text-xs dark:text-slate-500 text-gray-400 truncate">{{ $member->email }}</p>
                            </div>

                            {{-- Role & Actions --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if(!$isOwner)
                                    {{-- Role select --}}
                                    <select @change="$wire.updateMemberRole('{{ $member->id }}', $event.target.value)"
                                            class="text-xs font-semibold px-2.5 py-1.5 rounded-lg cursor-pointer
                                                   dark:bg-slate-800 bg-gray-100
                                                   dark:border-slate-700 border-gray-200 border
                                                   dark:text-slate-300 text-gray-700
                                                   focus:outline-none focus:ring-1 focus:ring-primary/50
                                                   transition-all duration-150">
                                        <option value="editor" {{ $memberRole === 'editor' ? 'selected' : '' }}>Editor</option>
                                        <option value="viewer" {{ $memberRole === 'viewer' ? 'selected' : '' }}>Viewer</option>
                                    </select>

                                    {{-- Remove button --}}
                                    <div x-show="!confirmRemove">
                                        <button @click="confirmRemove = true"
                                                class="p-1.5 rounded-lg transition-all duration-150
                                                       dark:text-slate-600 text-gray-400
                                                       dark:hover:text-red-400 hover:text-red-500
                                                       dark:hover:bg-red-500/10 hover:bg-red-50"
                                                title="Remove member">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Confirm remove --}}
                                    <div x-show="confirmRemove" x-cloak class="flex items-center gap-1.5">
                                        <span class="text-xs dark:text-slate-400 text-gray-500">Remove?</span>
                                        <button wire:click="removeMember('{{ $member->id }}')"
                                                class="px-2.5 py-1 text-xs font-semibold
                                                       bg-red-500 hover:bg-red-600 text-white
                                                       rounded-lg transition-colors">
                                            Yes
                                        </button>
                                        <button @click="confirmRemove = false"
                                                class="px-2.5 py-1 text-xs font-medium
                                                       dark:text-slate-400 text-gray-500
                                                       dark:hover:text-white hover:text-gray-900
                                                       transition-colors">
                                            No
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs dark:text-slate-600 text-gray-400 font-medium">Cannot remove</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Pending Invitations --}}
            @if($pending->isNotEmpty())
                <div class="dark:bg-[#1e293b] bg-white dark:border-slate-700/60 border border-gray-200 rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 dark:border-b dark:border-slate-700/40 border-b border-gray-100">
                        <h2 class="font-heading font-bold text-base dark:text-white text-gray-900">Pending Invitations</h2>
                        <p class="text-xs dark:text-slate-400 text-gray-500 mt-0.5">Awaiting acceptance from invitees.</p>
                    </div>
                    <div class="divide-y dark:divide-slate-700/40 divide-gray-100">
                        @foreach($pending as $inv)
                            <div class="px-6 py-4 flex items-center gap-4">
                                {{-- Email icon --}}
                                <div class="w-9 h-9 rounded-full dark:bg-slate-700/60 bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 dark:text-slate-400 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                                    </svg>
                                </div>

                                {{-- Details --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold dark:text-white text-gray-900 truncate">{{ $inv->email }}</p>
                                    <p class="text-xs dark:text-slate-500 text-gray-400 mt-0.5">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold
                                                     dark:bg-primary/15 bg-primary/10 text-primary">
                                            {{ ucfirst($inv->role) }}
                                        </span>
                                        <span class="ml-1.5">Expires {{ $inv->expires_at->diffForHumans() }}</span>
                                    </p>
                                </div>

                                {{-- Cancel --}}
                                <button wire:click="cancelInvitation('{{ $inv->id }}')"
                                        wire:confirm="Cancel this invitation?"
                                        class="text-xs font-medium px-3 py-1.5 rounded-lg transition-all duration-150
                                               dark:text-slate-500 text-gray-400
                                               dark:hover:text-red-400 hover:text-red-500
                                               dark:hover:bg-red-500/10 hover:bg-red-50">
                                    Cancel
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- ===== UPGRADE MODAL ===== --}}
    <x-upgrade-modal :show="$showUpgradeModal" feature="team" />

</div>
