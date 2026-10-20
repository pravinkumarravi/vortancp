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
<body class="font-sans antialiased bg-[#F1F5F9] dark:bg-boxdark-2 text-body dark:text-bodydark transition-colors duration-150 min-h-screen flex flex-col">
    <!-- ===== TailAdmin Layout Wrapper Start ===== -->
    <div class="flex h-screen overflow-hidden">
        
        <!-- ===== SIDEBAR START ===== -->
        <aside 
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-72 bg-[#1C2434] dark:bg-boxdark border-r border-[#2E3A47] text-bodydark1 transition-transform duration-200 ease-in-out flex flex-col lg:static lg:translate-x-0"
        >
            <!-- BRAND LOGO -->
            <div class="flex items-center justify-between h-18 px-6 border-b border-[#2E3A47] bg-[#1A222C]/60">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center shadow-lg shadow-brand-500/30 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xl font-bold tracking-tight text-white block leading-none">VORTAN<span class="text-brand-400">CP</span></span>
                        <span class="text-[10px] font-semibold tracking-widest text-bodydark2 uppercase">Cloud Platform</span>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-bodydark hover:text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- CURRENT PANEL IDENTIFIER -->
            @php
                $currentPanel = $panel ?? (request()->is('admin*') ? 'admin' : (request()->is('reseller*') ? 'reseller' : 'customer'));
                $panelBadge = match($currentPanel) {
                    'admin' => ['label' => 'Administrator Fleet Panel', 'bg' => 'bg-purple-950/60 border-purple-800/80 text-purple-300'],
                    'reseller' => ['label' => 'Reseller Tenancy Center', 'bg' => 'bg-blue-950/60 border-blue-800/80 text-blue-300'],
                    default => ['label' => 'Customer Hosting Panel', 'bg' => 'bg-brand-950/60 border-brand-800/80 text-brand-300'],
                };
            @endphp
            <div class="px-5 py-3 border-b border-[#2E3A47]">
                <div class="flex items-center justify-between text-xs font-semibold px-3 py-1.5 rounded-lg border {{ $panelBadge['bg'] }}">
                    <span>{{ $panelBadge['label'] }}</span>
                    <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></span>
                </div>
            </div>

            <!-- NAVIGATION SECTIONS -->
            <nav class="flex-1 px-4 py-4 space-y-6 overflow-y-auto">
                @if(isset($navRegistry))
                    @foreach($navRegistry->getNavigationForUser($currentPanel, auth()->user()) as $section)
                        <div>
                            <h3 class="px-3 text-[11px] font-semibold text-bodydark2 uppercase tracking-wider mb-2">
                                {{ $section->getTitle() }}
                            </h3>
                            <ul class="space-y-1">
                                @foreach($section->getAuthorizedItems(auth()->user()) as $item)
                                    <li>
                                        <a href="{{ $item->getUrl() }}" 
                                           class="flex items-center justify-between px-3.5 py-2.5 text-sm font-medium rounded-lg transition-all duration-150 {{ $item->isActive() ? 'bg-brand-500 text-white font-semibold shadow-sm' : 'text-bodydark1 hover:bg-[#333A48] hover:text-white' }}">
                                            <div class="flex items-center gap-3">
                                                <span class="{{ $item->isActive() ? 'text-white' : 'text-bodydark2' }}">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                    </svg>
                                                </span>
                                                <span>{{ $item->getTitle() }}</span>
                                            </div>
                                            @if($item->getBadge())
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $item->isActive() ? 'bg-white/20 text-white' : 'bg-brand-500/20 text-brand-300 border border-brand-500/30' }}">
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
            <div class="p-4 border-t border-[#2E3A47] bg-[#1A222C]/60">
                <div class="flex items-center justify-between text-xs text-bodydark mb-1.5">
                    <span class="font-medium">Cluster Node 01</span>
                    <span class="text-[#10B981] font-semibold">Online</span>
                </div>
                <div class="w-full bg-[#313D4A] rounded-full h-1.5 overflow-hidden">
                    <div class="bg-brand-500 h-1.5 rounded-full" style="width: 24%"></div>
                </div>
                <div class="flex justify-between text-[10px] text-bodydark2 mt-1.5">
                    <span>Load: 0.42</span>
                    <span>RAM: 24%</span>
                </div>
            </div>
        </aside>
        <!-- ===== SIDEBAR END ===== -->

        <!-- ===== MAIN CONTENT WRAPPER START ===== -->
        <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
            
            <!-- ===== HEADER START ===== -->
            <header class="sticky top-0 z-40 flex w-full bg-white drop-shadow-1 dark:bg-boxdark dark:drop-shadow-none border-b border-stroke dark:border-strokedark px-4 md:px-6 2xl:px-10 py-3.5">
                <div class="flex items-center justify-between w-full">
                    
                    <!-- LEFT: MOBILE TOGGLE & SEARCH -->
                    <div class="flex items-center gap-4">
                        <button 
                            @click.stop="sidebarOpen = !sidebarOpen" 
                            class="lg:hidden p-2 rounded-lg text-body dark:text-bodydark hover:bg-gray-100 dark:hover:bg-[#313D4A]"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <!-- TAILADMIN SEARCH INPUT -->
                        <div class="hidden sm:block">
                            <form action="#" method="GET">
                                <div class="relative">
                                    <button class="absolute left-0 top-1/2 -translate-y-1/2 text-body dark:text-bodydark">
                                        <svg class="fill-current w-4 h-4 ml-3" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M9.16666 3.33332C5.945 3.33332 3.33332 5.945 3.33332 9.16666C3.33332 12.3883 5.945 15 9.16666 15C12.3883 15 15 12.3883 15 9.16666C15 5.945 12.3883 3.33332 9.16666 3.33332ZM1.66666 9.16666C1.66666 5.02452 5.02452 1.66666 9.16666 1.66666C13.3088 1.66666 16.6667 5.02452 16.6667 9.16666C16.6667 13.3088 13.3088 16.6667 9.16666 16.6667C5.02452 16.6667 1.66666 13.3088 1.66666 9.16666Z" fill=""/>
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M13.2857 13.2857C13.6112 12.9603 14.1388 12.9603 14.4642 13.2857L18.0892 16.9107C18.4147 17.2362 18.4147 17.7638 18.0892 18.0892C17.7638 18.4147 17.2362 18.4147 16.9107 18.0892L13.2857 14.4642C12.9603 14.1388 12.9603 13.6112 13.2857 13.2857Z" fill=""/>
                                        </svg>
                                    </button>
                                    <input 
                                        type="text" 
                                        placeholder="Type to search resources..." 
                                        class="w-full bg-transparent pl-9.5 pr-4 text-black focus:outline-none dark:text-white text-sm font-medium xl:w-80"
                                    />
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- RIGHT: CONTROLS & USER MENU -->
                    <div class="flex items-center gap-3 2xl:gap-6">
                        
                        <!-- PANEL SWITCHER FOR MULTI-ROLE -->
                        @if(auth()->check() && auth()->user()->isSuperAdmin())
                            <div class="hidden sm:flex items-center bg-[#F1F5F9] dark:bg-[#313D4A] p-1 rounded-lg text-xs font-semibold">
                                <a href="{{ route('admin.dashboard') }}" class="px-3 py-1 rounded-md transition {{ $currentPanel === 'admin' ? 'bg-white dark:bg-boxdark shadow-xs text-brand-600 dark:text-brand-400 font-bold' : 'text-body dark:text-bodydark' }}">Admin</a>
                                <a href="{{ route('reseller.dashboard') }}" class="px-3 py-1 rounded-md transition {{ $currentPanel === 'reseller' ? 'bg-white dark:bg-boxdark shadow-xs text-brand-600 dark:text-brand-400 font-bold' : 'text-body dark:text-bodydark' }}">Reseller</a>
                                <a href="{{ route('customer.dashboard') }}" class="px-3 py-1 rounded-md transition {{ $currentPanel === 'customer' ? 'bg-white dark:bg-boxdark shadow-xs text-brand-600 dark:text-brand-400 font-bold' : 'text-body dark:text-bodydark' }}">Customer</a>
                            </div>
                        @endif

                        <!-- TAILADMIN DARK / LIGHT MODE SWITCHER -->
                        <button 
                            @click="darkMode = !darkMode" 
                            class="relative flex h-8.5 w-8.5 items-center justify-center rounded-full border border-stroke bg-gray-100 hover:text-brand-500 dark:border-strokedark dark:bg-meta-4 dark:text-white"
                            title="Toggle Dark/Light Mode"
                        >
                            <svg x-show="!darkMode" class="w-4.5 h-4.5 text-body" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg x-show="darkMode" class="w-4.5 h-4.5 text-[#FFBA00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>

                        <!-- NOTIFICATIONS BELL -->
                        <div class="relative" x-data="{ open: false }">
                            <button 
                                @click="open = !open" 
                                class="relative flex h-8.5 w-8.5 items-center justify-center rounded-full border border-stroke bg-gray-100 hover:text-brand-500 dark:border-strokedark dark:bg-meta-4 dark:text-white"
                            >
                                <svg class="w-4.5 h-4.5 text-body dark:text-bodydark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span class="absolute -top-0.5 -right-0.5 z-1 h-2 w-2 rounded-full bg-[#10B981]">
                                    <span class="absolute -z-1 inline-flex h-full w-full animate-ping rounded-full bg-[#10B981] opacity-75"></span>
                                </span>
                            </button>

                            <div 
                                x-show="open" 
                                @click.outside="open = false" 
                                x-transition 
                                class="absolute right-0 mt-2.5 flex h-90 w-75 flex-col rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark sm:w-80 z-50"
                            >
                                <div class="px-4.5 py-3 border-b border-stroke dark:border-strokedark flex items-center justify-between">
                                    <h5 class="text-sm font-semibold text-black dark:text-white">Notifications</h5>
                                    <span class="rounded-md bg-[#E1F9EE] px-2 py-0.5 text-xs font-semibold text-[#0A9952] dark:bg-[#0A9952]/15 dark:text-[#3CD856]">Fleet Healthy</span>
                                </div>
                                <div class="p-6 text-center text-xs text-body dark:text-bodydark">
                                    No critical host alarms or unhandled service events.
                                </div>
                            </div>
                        </div>

                        <!-- USER PROFILE MENU -->
                        @auth
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-3">
                                <span class="hidden text-right lg:block">
                                    <span class="block text-sm font-medium text-black dark:text-white leading-tight">{{ auth()->user()->name }}</span>
                                    <span class="block text-xs text-body dark:text-bodydark leading-tight">{{ auth()->user()->email }}</span>
                                </span>
                                <div class="h-10 w-10 rounded-full bg-brand-500 text-white font-bold flex items-center justify-center text-sm shadow-xs">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <svg class="hidden fill-current sm:block w-3 h-3 text-body dark:text-bodydark" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M0.410765 0.910734C0.736202 0.585297 1.26384 0.585297 1.58928 0.910734L6.00002 5.32148L10.4108 0.910734C10.7362 0.585297 11.2638 0.585297 11.5893 0.910734C11.9147 1.23617 11.9147 1.76381 11.5893 2.08924L6.58928 7.08924C6.26384 7.41468 5.7362 7.41468 5.41077 7.08924L0.410765 2.08924C0.0853277 1.76381 0.0853277 1.23617 0.410765 0.910734Z" fill=""/>
                                </svg>
                            </button>

                            <div 
                                x-show="open" 
                                @click.outside="open = false" 
                                x-transition 
                                class="absolute right-0 mt-3 flex w-62.5 flex-col rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark z-50 py-1.5"
                            >
                                <div class="px-4 py-2 border-b border-stroke dark:border-strokedark">
                                    <p class="text-xs text-body dark:text-bodydark">Signed in as</p>
                                    <p class="text-sm font-semibold text-black dark:text-white truncate">{{ auth()->user()->email }}</p>
                                </div>
                                
                                <a href="{{ route('profile.show') }}" class="flex items-center gap-3.5 px-4 py-2.5 text-sm font-medium duration-300 ease-in-out hover:text-brand-500 text-body dark:text-bodydark">
                                    <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Account & 2FA Settings
                                </a>
                                
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-stroke dark:border-strokedark mt-1">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center gap-3.5 px-4 py-2.5 text-sm font-medium text-[#EA2B2B] hover:bg-[#FEECEC] dark:hover:bg-[#EA2B2B]/10 duration-300 ease-in-out">
                                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endauth
                    </div>
                </div>
            </header>
            <!-- ===== HEADER END ===== -->

            <!-- FLASH ALERTS -->
            <div class="px-4 md:px-6 2xl:px-10 pt-4">
                @if (session('success'))
                    <div class="mb-4 flex items-center gap-3 p-4 rounded-lg bg-[#E1F9EE] border border-[#0A9952]/20 text-[#0A9952] dark:bg-[#0A9952]/15 dark:text-[#3CD856] text-sm">
                        <svg class="w-5 h-5 text-[#0A9952] dark:text-[#3CD856] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-4 flex items-center gap-3 p-4 rounded-lg bg-[#FEECEC] border border-[#EA2B2B]/20 text-[#EA2B2B] dark:bg-[#EA2B2B]/15 dark:text-[#FB5454] text-sm">
                        <svg class="w-5 h-5 text-[#EA2B2B] dark:text-[#FB5454] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                @endif
            </div>

            <!-- ===== MAIN PAGE CONTENT START ===== -->
            <main class="flex-1">
                <div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10 w-full">
                    {{ $slot }}
                </div>
            </main>
            <!-- ===== MAIN PAGE CONTENT END ===== -->

            <!-- FOOTER -->
            <footer class="px-6 py-4 border-t border-stroke dark:border-strokedark text-center text-xs text-body dark:text-bodydark">
                <span>VortanCP Enterprise Cloud Hosting Engine &bull; Built with Laravel 13, Livewire, Alpine.js & TailAdmin</span>
            </footer>
        </div>
        <!-- ===== MAIN CONTENT WRAPPER END ===== -->
    </div>
    <!-- ===== TailAdmin Layout Wrapper End ===== -->

    @livewireScripts
</body>
</html>
