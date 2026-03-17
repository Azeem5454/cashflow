<div class="p-8 dark:text-white text-gray-900" x-data="{ tab: @entangle('activeTab').live }">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-xs text-slate-600 font-body mb-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate class="text-amber-400 hover:text-amber-300 transition-colors">Admin</a>
        <span>/</span>
        <span class="text-slate-400">Appearance</span>
    </div>

    <h1 class="font-display font-extrabold text-2xl text-white tracking-tight mb-2">Appearance & Branding</h1>
    <p class="font-body text-sm text-slate-500 mb-8">Manage your app identity, logos, colours, typography, and landing page copy.</p>

    {{-- ══════ Tab Navigation ══════ --}}
    <div class="flex flex-wrap gap-1 mb-8 bg-slate-900 rounded-xl p-1 max-w-3xl">
        @foreach([
            'general'    => 'General',
            'logos'      => 'Logos',
            'colours'    => 'Colours',
            'typography' => 'Typography',
            'copy'       => 'Landing Copy',
            'email'      => 'Email Sender',
        ] as $key => $label)
            <button @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'bg-primary/15 text-blue-light'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800/60'"
                    class="px-4 py-2 rounded-lg text-sm font-medium font-body transition-all duration-150">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="max-w-2xl">

        {{-- ══════════════════════════════════════════════════════════
             TAB: GENERAL
             ══════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'general'" x-cloak>
            <form wire:submit="saveGeneral">
                <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6 space-y-5">
                    <h2 class="font-heading font-bold text-sm text-white mb-1">App Identity</h2>
                    <p class="text-xs text-slate-500 font-body mb-4">These values appear in the browser title, navbar, emails, and PDF exports.</p>

                    {{-- App Name --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">App Name</label>
                        <input type="text" wire:model="appName" placeholder="CashFlow"
                               class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                      dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                        @error('appName') <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Tagline --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">Tagline</label>
                        <input type="text" wire:model="tagline" placeholder="Real-Time Cash Flow Tracking for Your Business"
                               class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                      dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                        @error('tagline') <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Support Email --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">Support Email</label>
                        <input type="email" wire:model="supportEmail" placeholder="support@cashflow.app"
                               class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                      dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                        @error('supportEmail') <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- App URL --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">App URL</label>
                        <input type="url" wire:model="appUrl" placeholder="https://cashflow.app"
                               class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                      dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                        @error('appUrl') <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Save --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                                class="px-5 py-2.5 bg-primary hover:bg-accent text-white text-sm font-medium font-body rounded-xl
                                       transition-all duration-150 shadow-lg shadow-primary/20 hover:shadow-accent/25">
                            Save Changes
                        </button>
                        @if($generalSuccess)
                            <span class="text-xs text-emerald-400 font-body" x-data x-init="setTimeout(() => $wire.set('generalSuccess', null), 3000)">
                                {{ $generalSuccess }}
                            </span>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             TAB: LOGOS
             ══════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'logos'" x-cloak>
            <div class="space-y-5">

                @if($logoSuccess)
                    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-3 text-sm text-emerald-400 font-body"
                         x-data x-init="setTimeout(() => $wire.set('logoSuccess', null), 3000)">
                        {{ $logoSuccess }}
                    </div>
                @endif

                {{-- Dark Logo --}}
                <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6">
                    <h2 class="font-heading font-bold text-sm text-white mb-1">Logo — Dark Mode</h2>
                    <p class="text-xs text-slate-500 font-body mb-4">Used on dark navbar and dark landing page. PNG only, max 1 MB. Recommended: 1200×400 px.</p>

                    <div class="flex items-start gap-6">
                        {{-- Preview --}}
                        <div class="w-24 h-24 rounded-xl bg-navy border border-slate-700 flex items-center justify-center flex-shrink-0 overflow-hidden">
                            @if(file_exists(public_path('brand/logo-dark.png')))
                                <img src="{{ asset('brand/logo-dark.png') }}?v={{ filemtime(public_path('brand/logo-dark.png')) }}" alt="Dark logo" class="max-w-full max-h-full object-contain p-2">
                            @else
                                <span class="text-xs text-slate-600 font-body">No logo</span>
                            @endif
                        </div>

                        <div class="flex-1 space-y-3">
                            <input type="file" wire:model="logoDark" accept=".png" class="block w-full text-sm text-slate-400 font-body
                                   file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium
                                   file:bg-primary/10 file:text-blue-light hover:file:bg-primary/20 file:transition-colors file:cursor-pointer">
                            @error('logoDark') <p class="text-xs text-red-400 font-body">{{ $message }}</p> @enderror

                            <div class="flex gap-2">
                                <button wire:click="uploadLogoDark" @disabled(!$logoDark)
                                        class="px-4 py-2 bg-primary hover:bg-accent text-white text-xs font-medium font-body rounded-lg
                                               transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed">
                                    Upload
                                </button>
                                @if(file_exists(public_path('brand/logo-dark.png')))
                                    <button wire:click="revertLogo('dark')" wire:confirm="Remove the custom dark logo?"
                                            class="px-4 py-2 border border-slate-700 text-slate-400 hover:text-white text-xs font-medium font-body rounded-lg transition-all duration-150">
                                        Revert to Default
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Light Logo --}}
                <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6">
                    <h2 class="font-heading font-bold text-sm text-white mb-1">Logo — Light Mode</h2>
                    <p class="text-xs text-slate-500 font-body mb-4">Used on light navbar and light landing page. PNG only, max 1 MB. Recommended: 1200×400 px.</p>

                    <div class="flex items-start gap-6">
                        <div class="w-24 h-24 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0 overflow-hidden">
                            @if(file_exists(public_path('brand/logo-light.png')))
                                <img src="{{ asset('brand/logo-light.png') }}?v={{ filemtime(public_path('brand/logo-light.png')) }}" alt="Light logo" class="max-w-full max-h-full object-contain p-2">
                            @else
                                <span class="text-xs text-slate-400 font-body">No logo</span>
                            @endif
                        </div>

                        <div class="flex-1 space-y-3">
                            <input type="file" wire:model="logoLight" accept=".png" class="block w-full text-sm text-slate-400 font-body
                                   file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium
                                   file:bg-primary/10 file:text-blue-light hover:file:bg-primary/20 file:transition-colors file:cursor-pointer">
                            @error('logoLight') <p class="text-xs text-red-400 font-body">{{ $message }}</p> @enderror

                            <div class="flex gap-2">
                                <button wire:click="uploadLogoLight" @disabled(!$logoLight)
                                        class="px-4 py-2 bg-primary hover:bg-accent text-white text-xs font-medium font-body rounded-lg
                                               transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed">
                                    Upload
                                </button>
                                @if(file_exists(public_path('brand/logo-light.png')))
                                    <button wire:click="revertLogo('light')" wire:confirm="Remove the custom light logo?"
                                            class="px-4 py-2 border border-slate-700 text-slate-400 hover:text-white text-xs font-medium font-body rounded-lg transition-all duration-150">
                                        Revert to Default
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Favicon --}}
                <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6">
                    <h2 class="font-heading font-bold text-sm text-white mb-1">Favicon</h2>
                    <p class="text-xs text-slate-500 font-body mb-4">Browser tab icon. PNG only, max 1 MB. Recommended: 512x512.</p>

                    <div class="flex items-start gap-6">
                        <div class="w-16 h-16 rounded-xl bg-navy border border-slate-700 flex items-center justify-center flex-shrink-0 overflow-hidden">
                            <img src="{{ asset('favicon.png') }}?v={{ @filemtime(public_path('favicon.png')) }}" alt="Favicon" class="max-w-full max-h-full object-contain p-1">
                        </div>

                        <div class="flex-1 space-y-3">
                            <input type="file" wire:model="favicon" accept=".png" class="block w-full text-sm text-slate-400 font-body
                                   file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium
                                   file:bg-primary/10 file:text-blue-light hover:file:bg-primary/20 file:transition-colors file:cursor-pointer">
                            @error('favicon') <p class="text-xs text-red-400 font-body">{{ $message }}</p> @enderror

                            <button wire:click="uploadFavicon" @disabled(!$favicon)
                                    class="px-4 py-2 bg-primary hover:bg-accent text-white text-xs font-medium font-body rounded-lg
                                           transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed">
                                Upload
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             TAB: COLOURS
             ══════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'colours'" x-cloak>
            <form wire:submit="saveColours">
                <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-1">
                        <h2 class="font-heading font-bold text-sm text-white">Colour Palette</h2>
                        <button type="button" wire:click="resetColours" wire:confirm="Reset all colours to brand defaults?"
                                class="text-xs text-slate-500 hover:text-amber-400 font-body transition-colors">
                            Reset to Defaults
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 font-body mb-6">Changes are written to <code class="text-slate-400">theme.css</code>. Save and reload to see the effect.</p>

                    <div class="grid grid-cols-2 gap-4">
                        @foreach([
                            ['colorNavy',       'Navy',         'Page backgrounds'],
                            ['colorDark',       'Dark',         'Cards, sidebars'],
                            ['colorPrimary',    'Primary Blue', 'Buttons, links'],
                            ['colorAccent',     'Accent Blue',  'Hover states'],
                            ['colorBlueLight',  'Light Blue',   'Subtext, icons'],
                            ['colorBlueXlight', 'X-Light Blue', 'Subtle backgrounds'],
                        ] as [$prop, $name, $desc])
                            <div>
                                <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">{{ $name }}</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" wire:model.live="{{ $prop }}"
                                           class="w-10 h-10 rounded-lg border border-slate-700 bg-transparent cursor-pointer flex-shrink-0"
                                           style="padding: 2px;">
                                    <div class="flex-1">
                                        <input type="text" wire:model.live="{{ $prop }}" maxlength="7"
                                               class="w-full px-3 py-2 text-xs font-mono dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                                      dark:text-white text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all duration-150">
                                        <p class="text-[10px] text-slate-600 mt-0.5">{{ $desc }}</p>
                                    </div>
                                </div>
                                @error($prop) <p class="mt-1 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>

                    {{-- Live preview strip --}}
                    <div class="mt-6 pt-5 border-t border-slate-800">
                        <p class="text-xs text-slate-500 font-body mb-3">Preview</p>
                        <div class="flex gap-2">
                            @foreach(['colorNavy','colorDark','colorPrimary','colorAccent','colorBlueLight','colorBlueXlight'] as $c)
                                <div class="flex-1 h-10 rounded-lg border border-slate-700" :style="'background-color: ' + $wire.{{ $c }}"></div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Save --}}
                    <div class="flex items-center gap-3 pt-5">
                        <button type="submit"
                                class="px-5 py-2.5 bg-primary hover:bg-accent text-white text-sm font-medium font-body rounded-xl
                                       transition-all duration-150 shadow-lg shadow-primary/20 hover:shadow-accent/25">
                            Save Colours
                        </button>
                        @if($colourSuccess)
                            <span class="text-xs text-emerald-400 font-body" x-data x-init="setTimeout(() => $wire.set('colourSuccess', null), 3000)">
                                {{ $colourSuccess }}
                            </span>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             TAB: TYPOGRAPHY
             ══════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'typography'" x-cloak>
            <form wire:submit="saveTypography">
                <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6 space-y-5">
                    <h2 class="font-heading font-bold text-sm text-white mb-1">Typography</h2>
                    <p class="text-xs text-slate-500 font-body mb-4">Choose a font for each typographic role. Changes are written to <code class="text-slate-400">theme.css</code>.</p>

                    @foreach([
                        ['fontDisplay', 'Display / Headings', 'Hero text, page titles, wordmark'],
                        ['fontHeading', 'Subheadings',        'Section headings, card titles'],
                        ['fontBody',    'Body / UI',          'Body copy, labels, form fields'],
                        ['fontMono',    'Monospace',           'Currency amounts, codes'],
                    ] as [$prop, $label, $desc])
                        <div>
                            <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">{{ $label }}</label>
                            <select wire:model.live="{{ $prop }}"
                                    class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                           dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 transition-all duration-150 appearance-none">
                                @foreach($fontOptions as $font)
                                    <option value="{{ $font }}">{{ $font }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-600 mt-0.5">{{ $desc }}</p>
                            @error($prop) <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    {{-- Live preview --}}
                    <div class="pt-5 border-t border-slate-800">
                        <p class="text-xs text-slate-500 font-body mb-3">Preview</p>
                        <div class="space-y-3 bg-navy rounded-xl p-5 border border-slate-700">
                            <p class="text-lg font-extrabold text-white" :style="'font-family: ' + $wire.fontDisplay">The quick brown fox jumps over the lazy dog</p>
                            <p class="text-base font-bold text-slate-300" :style="'font-family: ' + $wire.fontHeading">Section heading preview</p>
                            <p class="text-sm text-slate-400" :style="'font-family: ' + $wire.fontBody">Body text and UI labels look like this.</p>
                            <p class="text-sm text-green-400" :style="'font-family: ' + $wire.fontMono">$12,450.00 — PKR 3,450,000</p>
                        </div>
                    </div>

                    {{-- Save --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                                class="px-5 py-2.5 bg-primary hover:bg-accent text-white text-sm font-medium font-body rounded-xl
                                       transition-all duration-150 shadow-lg shadow-primary/20 hover:shadow-accent/25">
                            Save Typography
                        </button>
                        @if($typographySuccess)
                            <span class="text-xs text-emerald-400 font-body" x-data x-init="setTimeout(() => $wire.set('typographySuccess', null), 3000)">
                                {{ $typographySuccess }}
                            </span>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             TAB: LANDING COPY
             ══════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'copy'" x-cloak>
            <form wire:submit="saveCopy">
                <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6 space-y-5">
                    <h2 class="font-heading font-bold text-sm text-white mb-1">Landing Page Copy</h2>
                    <p class="text-xs text-slate-500 font-body mb-4">Override the default landing page text. Leave blank to use the built-in default.</p>

                    {{-- Hero Headline --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">Hero Headline</label>
                        <input type="text" wire:model="heroHeadline" placeholder="Know Exactly Where Your Cash Flows."
                               class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                      dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                        @error('heroHeadline') <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Hero Subheadline --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">Hero Subheadline</label>
                        <textarea wire:model="heroSubheadline" rows="3"
                                  placeholder="Real-time ledger for every business you run. Track income, expenses, and balance — across multiple books and teams — without needing an accountant."
                                  class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                         dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                         dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150 resize-none"></textarea>
                        @error('heroSubheadline') <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- CTA Text --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">CTA Button Text</label>
                        <input type="text" wire:model="ctaText" placeholder="Start for Free — No Card Needed"
                               class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                      dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                        @error('ctaText') <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Footer Tagline --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">Footer Tagline</label>
                        <input type="text" wire:model="footerTagline" placeholder="Built for business owners everywhere."
                               class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                      dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                        @error('footerTagline') <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Save --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                                class="px-5 py-2.5 bg-primary hover:bg-accent text-white text-sm font-medium font-body rounded-xl
                                       transition-all duration-150 shadow-lg shadow-primary/20 hover:shadow-accent/25">
                            Save Copy
                        </button>
                        @if($copySuccess)
                            <span class="text-xs text-emerald-400 font-body" x-data x-init="setTimeout(() => $wire.set('copySuccess', null), 3000)">
                                {{ $copySuccess }}
                            </span>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             TAB: EMAIL SENDER
             ══════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'email'" x-cloak>
            <form wire:submit="saveEmail">
                <div class="dark:bg-slate-900 bg-white border border-gray-200 dark:border-slate-800 rounded-xl p-6 space-y-5">
                    <h2 class="font-heading font-bold text-sm text-white mb-1">Email Sender</h2>
                    <p class="text-xs text-slate-500 font-body mb-4">Controls the "From" name and address on all outgoing emails. Does not expose SMTP credentials.</p>

                    {{-- From Name --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">From Name</label>
                        <input type="text" wire:model="mailFromName" placeholder="CashFlow"
                               class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                      dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                        @error('mailFromName') <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- From Address --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-400 font-body mb-1.5">From Address</label>
                        <input type="email" wire:model="mailFromAddress" placeholder="hello@cashflow.app"
                               class="w-full px-4 py-2.5 text-sm font-body dark:bg-slate-800 bg-white border border-gray-300 dark:border-slate-700
                                      dark:text-white text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary/50
                                      dark:placeholder:text-slate-500 placeholder:text-gray-400 transition-all duration-150">
                        @error('mailFromAddress') <p class="mt-1.5 text-xs text-red-400 font-body">{{ $message }}</p> @enderror
                    </div>

                    {{-- Save --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                                class="px-5 py-2.5 bg-primary hover:bg-accent text-white text-sm font-medium font-body rounded-xl
                                       transition-all duration-150 shadow-lg shadow-primary/20 hover:shadow-accent/25">
                            Save Email Settings
                        </button>
                        @if($emailSuccess)
                            <span class="text-xs text-emerald-400 font-body" x-data x-init="setTimeout(() => $wire.set('emailSuccess', null), 3000)">
                                {{ $emailSuccess }}
                            </span>
                        @endif
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>
