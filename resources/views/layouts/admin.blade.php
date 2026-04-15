<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          darkMode: (localStorage.getItem('cashflow_theme') ?? 'light') === 'dark',
          toggleTheme() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('cashflow_theme', this.darkMode ? 'dark' : 'light');
              document.documentElement.classList.toggle('dark', this.darkMode);
          }
      }"
      :class="{ 'dark': darkMode }"
      class="h-full bg-navy">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Prevent dark mode flash --}}
    <script>if ((localStorage.getItem('cashflow_theme') ?? 'light') === 'dark') { document.documentElement.classList.add('dark'); }</script>
    <script>document.addEventListener('livewire:navigated', function() { document.documentElement.classList.toggle('dark', (localStorage.getItem('cashflow_theme') ?? 'light') === 'dark'); });</script>

    <title>Admin · {{ config('app.name', 'TheCashFox') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ @filemtime(public_path('favicon.png')) }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ \App\Helpers\Setting::get('google_fonts_url', 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..60,400;12..60,700;12..60,800&family=Plus+Jakarta+Sans:wght@400;600;700&family=Outfit:wght@300;400;500&family=Geist+Mono:wght@400&display=swap') }}" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(file_exists(public_path('brand/theme.css')))
        <link rel="stylesheet" href="{{ asset('brand/theme.css') }}?v={{ filemtime(public_path('brand/theme.css')) }}">
    @endif
    @livewireStyles
</head>
<body class="font-body antialiased dark:bg-navy bg-slate-50 dark:text-white text-gray-900 transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    {{-- ===== ADMIN SIDEBAR ===== --}}
    <aside class="w-56 flex-shrink-0 flex flex-col dark:bg-slate-900 bg-white border-r border-gray-200 dark:border-slate-800">

        {{-- Logo + Admin badge --}}
        <div class="flex items-center gap-2.5 h-14 px-4 flex-shrink-0 border-b border-gray-200 dark:border-slate-800">
            <x-app-logo />
            <span class="text-[10px] font-bold uppercase tracking-widest text-amber-400 bg-amber-400/10 px-1.5 py-0.5 rounded">
                Admin
            </span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-2.5 py-4 space-y-0.5 overflow-y-auto">

            <p class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 px-2.5 pb-2">Overview</p>

            <a href="{{ route('admin.dashboard') }}" wire:navigate
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('admin.dashboard') ? 'dark:bg-primary/15 bg-primary/10 dark:text-blue-light text-primary' : 'dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                </svg>
                Dashboard
            </a>

            <div class="pt-3">
                <p class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 px-2.5 pb-2">Manage</p>
            </div>

            <a href="{{ route('admin.users') }}" wire:navigate
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('admin.users*') ? 'dark:bg-primary/15 bg-primary/10 dark:text-blue-light text-primary' : 'dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                </svg>
                Users
            </a>

            <a href="{{ route('admin.businesses') }}" wire:navigate
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('admin.businesses') ? 'dark:bg-primary/15 bg-primary/10 dark:text-blue-light text-primary' : 'dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                </svg>
                Businesses
            </a>

            <a href="{{ route('admin.subscriptions') }}" wire:navigate
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('admin.subscriptions') ? 'dark:bg-primary/15 bg-primary/10 dark:text-blue-light text-primary' : 'dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                </svg>
                Subscriptions
            </a>

            <a href="{{ route('admin.invitations') }}" wire:navigate
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('admin.invitations') ? 'dark:bg-primary/15 bg-primary/10 dark:text-blue-light text-primary' : 'dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                </svg>
                Invitations
            </a>

            <div class="pt-3">
                <p class="text-[10px] font-bold uppercase tracking-widest dark:text-slate-600 text-gray-400 px-2.5 pb-2">Settings</p>
            </div>

            <a href="{{ route('admin.appearance') }}" wire:navigate
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('admin.appearance') ? 'dark:bg-primary/15 bg-primary/10 dark:text-blue-light text-primary' : 'dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/>
                </svg>
                Appearance
            </a>

            <a href="{{ route('admin.announcement') }}" wire:navigate
               class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('admin.announcement') ? 'dark:bg-primary/15 bg-primary/10 dark:text-blue-light text-primary' : 'dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"/>
                </svg>
                Announcement
            </a>

        </nav>

        {{-- Bottom: theme toggle + links + user --}}
        <div class="p-2.5 flex-shrink-0 border-t border-gray-200 dark:border-slate-800 space-y-0.5">

            {{-- Theme toggle --}}
            <button @click="toggleTheme()"
                    class="flex items-center gap-2.5 w-full px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                           dark:text-slate-400 text-gray-600 dark:hover:bg-slate-800/80 hover:bg-gray-100 dark:hover:text-white hover:text-gray-900">
                <svg x-show="darkMode" class="w-4 h-4 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                </svg>
                <svg x-show="!darkMode" class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
                </svg>
                <span x-text="darkMode ? 'Light Mode' : 'Dark Mode'" class="flex-1 text-left text-sm"></span>
                {{-- Pill toggle --}}
                <div class="relative w-9 h-5 rounded-full transition-colors duration-200 flex-shrink-0"
                     :class="darkMode ? 'bg-primary' : 'bg-gray-300'">
                    <div class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform duration-200"
                         :class="darkMode ? 'translate-x-4' : 'translate-x-0.5'"></div>
                </div>
            </button>



            {{-- User chip with dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.outside="open = false"
                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm transition-all duration-150
                               dark:hover:bg-slate-800/80 hover:bg-gray-100">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white text-sm font-bold flex-shrink-0 shadow">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 text-left min-w-0">
                        <p class="text-sm font-semibold dark:text-white text-gray-900 truncate leading-tight">{{ auth()->user()->name }}</p>
                        <p class="text-xs dark:text-slate-500 text-gray-400 truncate leading-tight">{{ auth()->user()->email }}</p>
                    </div>
                    <svg class="w-4 h-4 dark:text-slate-500 text-gray-400 flex-shrink-0 transition-transform duration-150"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="absolute bottom-full left-0 right-0 mb-1 dark:bg-[#1e293b] bg-white dark:border-slate-700 border border-gray-200 rounded-xl shadow-2xl overflow-hidden z-50"
                     style="display:none;">
                    <a href="{{ route('admin.profile') }}" wire:navigate
                       class="flex items-center gap-2.5 w-full px-4 py-3 text-sm dark:text-slate-300 text-gray-700 dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors border-b border-gray-100 dark:border-slate-700">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                        Profile Settings
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-2.5 w-full px-4 py-3 text-sm text-red-500 dark:hover:bg-slate-700/50 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 20.25h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15"/>
                            </svg>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </aside>

    {{-- ===== MAIN AREA ===== --}}
    <main class="flex-1 overflow-y-auto dark:bg-navy bg-slate-50">
        {{ $slot }}
    </main>

</div>

@livewireScripts
</body>
</html>
