<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" 
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" 
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Sign In' }} - {{ config('app.name', 'VortanCP') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-slate-950 text-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        
        <!-- BRAND LOGO -->
        <div class="text-center mb-8">
            <div class="inline-flex w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-cyan-500 items-center justify-center shadow-xl shadow-emerald-500/20 mb-3">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white">VORTAN<span class="text-emerald-400">CP</span></h1>
            <p class="text-xs text-slate-400 mt-1">Enterprise Web Hosting Control Panel</p>
        </div>

        <!-- AUTH CARD -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl shadow-black/60 relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            {{ $slot }}
        </div>

        <!-- FOOTER -->
        <p class="text-center text-xs text-slate-500 mt-6">
            Protected by Vortan Zero-Trust Security &bull; Argon2id & TOTP 2FA
        </p>
    </div>

    @livewireScripts
</body>
</html>
