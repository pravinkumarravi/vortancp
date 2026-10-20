@php
    $currentPanel = $panel ?? (request()->is('admin*') ? 'admin' : (request()->is('reseller*') ? 'reseller' : 'customer'));
    $currentPath = request()->path();

    $panelBadge = match($currentPanel) {
        'admin' => ['label' => 'Administrator Fleet', 'bg' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 border-brand-200 dark:border-brand-500/20'],
        'reseller' => ['label' => 'Reseller Tenancy', 'bg' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400 border-blue-200 dark:border-blue-500/20'],
        default => ['label' => 'Customer Hosting', 'bg' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400 border-success-200 dark:border-success-500/20'],
    };
@endphp

<aside id="sidebar"
    class="fixed flex flex-col mt-0 top-0 px-5 start-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 ltr:border-r rtl:border-l border-gray-200 w-[90px] [.sidebar-expanded_&]:min-w-[290px]"
    x-data="{
        isActive(url) {
            const currentUrl = window.location.href;
            const currentPath = window.location.pathname;
            return currentUrl === url || currentPath === url.replace(/^https?:\/\/[^\/]+/, '');
        }
    }"
    :class="{
        'translate-x-0': $store.sidebar.isMobileOpen,
        'max-xl:-translate-x-full max-xl:rtl:translate-x-full': !$store.sidebar.isMobileOpen
    }"
    @mouseenter="if (!$store.sidebar.isExpanded) $store.sidebar.setHovered(true)"
    @mouseleave="$store.sidebar.setHovered(false)">

    <!-- Logo Section -->
    <div class="pt-7 pb-6 flex items-center gap-3 border-b border-gray-100 dark:border-gray-800" 
         :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center' : 'justify-between'">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center text-white shrink-0 shadow-theme-xs">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <div class="hidden [.sidebar-expanded_&]:block" x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                <span class="text-xl font-bold tracking-tight text-gray-900 dark:text-white block leading-none font-outfit">VORTAN<span class="text-brand-500">CP</span></span>
                <span class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">Modular Control Panel</span>
            </div>
        </a>
    </div>

    <!-- Active Panel Badge -->
    <div class="py-3 border-b border-gray-100 dark:border-gray-800" x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
        <div class="flex items-center justify-between text-xs font-semibold px-3 py-1.5 rounded-lg border {{ $panelBadge['bg'] }}">
            <span>{{ $panelBadge['label'] }}</span>
            <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar py-4">
        <nav class="mb-6 space-y-5">
            @if(isset($navRegistry))
                @foreach($navRegistry->getNavigationForUser($currentPanel, auth()->user()) as $section)
                    <div>
                        <!-- Menu Group Title -->
                        <h2 class="mb-2 text-[11px] uppercase font-semibold text-gray-400 dark:text-gray-500 tracking-wider flex leading-[20px]"
                            :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'lg:justify-center' : 'justify-start px-3'">
                            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                                {{ $section->getTitle() }}
                            </span>
                            <span x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen" class="w-4 h-0.5 bg-gray-300 dark:bg-gray-700 rounded my-2"></span>
                        </h2>

                        <!-- Menu Items -->
                        <ul class="flex flex-col gap-1">
                            @foreach($section->getAuthorizedItems(auth()->user()) as $item)
                                <li>
                                    <a href="{{ $item->getUrl() }}"
                                       class="menu-item group {{ $item->isActive() ? 'menu-item-active' : 'menu-item-inactive' }}"
                                       :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center px-0' : 'px-3'">
                                        <span class="shrink-0 {{ $item->isActive() ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                            </svg>
                                        </span>
                                        <span class="text-theme-sm font-medium truncate" x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
                                            {{ $item->getTitle() }}
                                        </span>
                                        @if($item->getBadge())
                                            <span class="ml-auto rounded-full px-2 py-0.5 text-xs font-semibold {{ $item->isActive() ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}"
                                                  x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
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
    </div>

    <!-- Cluster Node Heartbeat Footer -->
    <div class="py-4 border-t border-gray-100 dark:border-gray-800" x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-white/[0.03] p-3 text-xs">
            <div class="flex items-center justify-between text-gray-600 dark:text-gray-300 font-medium mb-1.5">
                <span>Node 01</span>
                <span class="inline-flex items-center gap-1 text-success-600 dark:text-success-500 font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-success-500"></span>
                    Online
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-brand-500 h-1.5 rounded-full" style="width: 24%"></div>
            </div>
            <div class="flex justify-between text-[11px] text-gray-400 mt-1.5">
                <span>Load: 0.42</span>
                <span>RAM: 24%</span>
            </div>
        </div>
    </div>
</aside>
