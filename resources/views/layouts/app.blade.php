<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ 
          darkMode: localStorage.getItem('darkMode') === 'true', 
          sidebarOpen: false,
          userMenuOpen: false,
          notificationsOpen: false
      }" 
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" 
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name', 'VortanCP') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-slate-950 text-gray-800 dark:text-gray-200 transition-colors duration-150 min-h-screen flex flex-col">
    <div class="flex h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside 
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 border-r border-slate-800 text-slate-300 transition-transform duration-200 ease-in-out flex flex-col lg:static lg:translate-x-0"
        >
            <!-- BRAND LOGO -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-slate-800/80 bg-slate-950/40">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-cyan-500 flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold tracking-tight text-white block leading-none">VORTAN<span class="text-emerald-400">CP</span></span>
                        <span class="text-[10px] font-medium tracking-widest text-slate-400 uppercase">Cloud Platform</span>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- CURRENT PANEL IDENTIFIER -->
            @php
                $currentPanel = $panel ?? (request()->is('admin*') ? 'admin' : (request()->is('reseller*') ? 'reseller' : 'customer'));
                $panelBadge = match($currentPanel) {
                    'admin' => ['label' => 'Administrator Panel', 'bg' => 'bg-purple-950/60 border-purple-800 text-purple-300'],
                    'reseller' => ['label' => 'Reseller Center', 'bg' => 'bg-blue-950/60 border-blue-800 text-blue-300'],
                    default => ['label' => 'Hosting Panel', 'bg' => 'bg-emerald-950/60 border-emerald-800 text-emerald-300'],
                };
            @endphp
            <div class="px-5 py-3 border-b border-slate-800/60">
                <div class="flex items-center justify-between text-xs font-semibold px-2.5 py-1.5 rounded-lg border {{ $panelBadge['bg'] }}">
                    <span>{{ $panelBadge['label'] }}</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                </div>
            </div>

            <!-- NAVIGATION SECTIONS -->
            <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
                @if(isset($navRegistry))
                    @foreach($navRegistry->getNavigationForUser($currentPanel, auth()->user()) as $section)
                        <div>
                            <h3 class="px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">
                                {{ $section->getTitle() }}
                            </h3>
                            <ul class="space-y-1">
                                @foreach($section->getAuthorizedItems(auth()->user()) as $item)
                                    <li>
                                        <a href="{{ $item->getUrl() }}" 
                                           class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl transition-all duration-150 {{ $item->isActive() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                                            <div class="flex items-center gap-3">
                                                <span class="{{ $item->isActive() ? 'text-emerald-400' : 'text-slate-400' }}">
                                                    <!-- Icon placeholder / svg rendering -->
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                    </svg>
                                                </span>
                                                <span>{{ $item->getTitle() }}</span>
                                            </div>
                                            @if($item->getBadge())
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                    {{ $item->getBadge() }}
                                                </span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @endif
            </nav>

            <!-- SERVER NODE HEARTBEAT / FOOTER WIDGET -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/30">
                <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                    <span>Cluster Node 01</span>
                    <span class="text-emerald-400 font-medium">Online</span>
                </div>
                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: 24%"></div>
                </div>
                <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                    <span>Load: 0.42</span>
                    <span>RAM: 24%</span>
                </div>
            </div>
        </aside>

        <!-- MAIN VIEWPORT -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            
            <!-- HEADER -->
            <header class="h-16 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between px-6 sticky top-0 z-20">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-800">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>

                    <!-- SEARCH BAR -->
                    <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-400 text-sm w-72">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <span>Search resources (Ctrl+K)...</span>
                    </div>
                </div>

                <!-- RIGHT CONTROLS -->
                <div class="flex items-center gap-3">
                    
                    <!-- PANEL SWITCHER (FOR MULTI-ROLE USERS) -->
                    @if(auth()->check() && auth()->user()->isSuperAdmin())
                        <div class="hidden sm:flex items-center bg-gray-100 dark:bg-slate-800 p-1 rounded-xl text-xs font-semibold">
                            <a href="{{ route('admin.dashboard') }}" class="px-3 py-1 rounded-lg {{ $currentPanel === 'admin' ? 'bg-white dark:bg-slate-700 shadow-sm text-purple-600 dark:text-purple-300' : 'text-gray-500 dark:text-gray-400' }}">Admin</a>
                            <a href="{{ route('reseller.dashboard') }}" class="px-3 py-1 rounded-lg {{ $currentPanel === 'reseller' ? 'bg-white dark:bg-slate-700 shadow-sm text-blue-600 dark:text-blue-300' : 'text-gray-500 dark:text-gray-400' }}">Reseller</a>
                            <a href="{{ route('customer.dashboard') }}" class="px-3 py-1 rounded-lg {{ $currentPanel === 'customer' ? 'bg-white dark:bg-slate-700 shadow-sm text-emerald-600 dark:text-emerald-300' : 'text-gray-500 dark:text-gray-400' }}">Customer</a>
                        </div>
                    @endif

                    <!-- DARK / LIGHT MODE TOGGLE -->
                    <button @click="darkMode = !darkMode" class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-slate-800 transition-colors" title="Toggle Theme">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                        <svg x-show="darkMode" class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </button>

                    <!-- NOTIFICATIONS BELL -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-slate-800 relative transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900"></span>
                        </button>

                        <div x-show="open" @click.outside="open = false" 
                             x-transition class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-700 py-2 z-50">
                            <div class="px-4 py-2 border-b border-gray-100 dark:border-slate-700/60 flex items-center justify-between">
                                <span class="font-semibold text-sm">Notifications</span>
                                <span class="text-xs text-emerald-600 dark:text-emerald-400">All Systems Operational</span>
                            </div>
                            <div class="p-4 text-center text-xs text-gray-500 dark:text-gray-400">
                                No critical alerts or provisioning errors.
                            </div>
                        </div>
                    </div>

                    <!-- USER PROFILE MENU -->
                    @auth
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-3 p-1 rounded-xl hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <div class="hidden md:block text-left">
                                <span class="block text-xs font-semibold leading-tight">{{ auth()->user()->name }}</span>
                                <span class="block text-[10px] text-gray-400 leading-tight">{{ auth()->user()->email }}</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" 
                             x-transition class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-700 py-1.5 z-50">
                            <div class="px-4 py-2 border-b border-gray-100 dark:border-slate-700/60">
                                <p class="text-xs text-gray-400">Signed in as</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            
                            <a href="{{ route('profile.show') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                Account & 2FA Settings
                            </a>
                            
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100 dark:border-slate-700/60 mt-1">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 font-medium">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                    @endauth
                </div>
            </header>

            <!-- FLASH ALERTS -->
            <div class="px-6 pt-4">
                @if (session('success'))
                    <div class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-sm">
                        <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
            </div>

            <!-- PAGE CONTENT -->
            <main class="flex-1 p-6">
                {{ $slot }}
            </main>

            <!-- FOOTER -->
            <footer class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 text-center text-xs text-gray-500 dark:text-gray-400">
                <span>VortanCP Modular Hosting Engine v1.0.0 &bull; Built with Laravel 13, Livewire, Alpine.js & TailAdmin</span>
            </footer>
        </div>
    </div>

    @livewireScripts
</body>
</html>
