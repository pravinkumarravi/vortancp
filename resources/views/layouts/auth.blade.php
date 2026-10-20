<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }"
      x-init="$watch('darkMode', val => { localStorage.setItem('theme', val ? 'dark' : 'light'); if(val) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); } })"
      :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Sign In' }} | {{ config('app.name', 'VortanCP') }}</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>

<body class="font-outfit antialiased bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        
        <!-- BRAND LOGO -->
        <div class="text-center mb-7">
            <div class="inline-flex w-12 h-12 rounded-2xl bg-brand-500 items-center justify-center text-white shadow-theme-md mb-3.5">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white font-outfit">VORTAN<span class="text-brand-500">CP</span></h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Next-Gen Modular Web Hosting Control Panel</p>
        </div>

        <!-- AUTH CARD -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900 sm:p-8 relative overflow-hidden">
            {{ $slot }}
        </div>

        <!-- FOOTER -->
        <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">
            Protected by Vortan Zero-Trust Security &bull; Argon2id & TOTP 2FA
        </p>
    </div>

    @livewireScripts
</body>
</html>
