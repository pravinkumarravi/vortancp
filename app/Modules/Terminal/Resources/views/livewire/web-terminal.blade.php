<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Web Terminal Console
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Direct in-browser interactive shell with jailed account isolation, security barriers, and audit trail logging.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button
                wire:click="clearScreen"
                type="button"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-700 shadow-sm text-xs font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Clear Screen
            </button>
            <button
                wire:click="resetTerminal"
                type="button"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-700 shadow-sm text-xs font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset Session
            </button>
        </div>
    </div>

    @if(!$isEntitled)
        <!-- Plan Upgrade Required Banner -->
        <div class="rounded-xl border border-amber-300 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-950/20 p-6 text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 mb-4">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">SSH & Web Terminal Access Restricted</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 max-w-md mx-auto">
                In-browser shell execution and SSH capabilities are disabled on your current hosting package. Please upgrade to a Professional or Business hosting plan to unlock the Web Terminal.
            </p>
            <div class="mt-5">
                <a
                    href="{{ route('customer.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition"
                >
                    View Hosting Plan Quotas
                </a>
            </div>
        </div>
    @else
        <!-- Quick Presets Toolbar -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-3 border border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-2 text-xs">
            <span class="text-gray-500 dark:text-gray-400 font-medium px-2 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Quick Commands:
            </span>
            <button wire:click="runPreset('pwd')" type="button" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300 font-mono transition">pwd</button>
            <button wire:click="runPreset('ls -la')" type="button" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300 font-mono transition">ls -la</button>
            <button wire:click="runPreset('php -v')" type="button" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300 font-mono transition">php -v</button>
            <button wire:click="runPreset('composer -V')" type="button" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300 font-mono transition">composer -V</button>
            <button wire:click="runPreset('git status')" type="button" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300 font-mono transition">git status</button>
            <button wire:click="runPreset('whoami')" type="button" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300 font-mono transition">whoami</button>
            <button wire:click="runPreset('date')" type="button" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300 font-mono transition">date</button>
        </div>

        <!-- Terminal Window Frame -->
        <div 
            x-data="{
                scrollBottom() {
                    const el = this.$refs.terminalBody;
                    if (el) { el.scrollTop = el.scrollHeight; }
                }
            }"
            x-init="scrollBottom(); $watch('$wire.history', () => setTimeout(() => scrollBottom(), 50))"
            class="rounded-xl overflow-hidden border border-gray-800 shadow-2xl bg-gray-950 font-mono text-xs sm:text-sm flex flex-col"
            style="min-height: 480px;"
        >
            <!-- Terminal Titlebar -->
            <div class="bg-gray-900 border-b border-gray-800 px-4 py-2.5 flex items-center justify-between select-none">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500/80 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                    <span class="ml-2 text-xs font-semibold text-gray-400 truncate max-w-xs sm:max-w-md">
                        {{ $userLabel }}:{{ $cwd }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-950 text-emerald-400 border border-emerald-800/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Active PTY
                    </span>
                    <span class="text-[11px] text-gray-500">vortan-shell</span>
                </div>
            </div>

            <!-- Terminal Output Viewport -->
            <div
                x-ref="terminalBody"
                class="p-4 overflow-y-auto flex-1 space-y-3 font-mono text-gray-300 max-h-[520px]"
            >
                @foreach($history as $item)
                    <div class="space-y-1">
                        @if($item['command'] !== null)
                            <!-- Command Line Prompt -->
                            <div class="flex items-center gap-2 text-emerald-400 font-semibold select-none">
                                <span class="text-cyan-400">[{{ $userLabel }}]</span>
                                <span class="text-gray-500 font-normal text-xs">{{ $item['time'] }}</span>
                                <span class="text-gray-400">$</span>
                                <span class="text-white">{{ $item['command'] }}</span>
                            </div>
                        @endif

                        <!-- Command Output -->
                        @if(!empty($item['output']))
                            <div class="pl-2 border-l-2 {{ $item['exit_code'] === 0 ? 'border-gray-800' : 'border-red-500/50' }} text-gray-300 whitespace-pre-wrap leading-relaxed {{ $item['exit_code'] !== 0 ? 'text-rose-300' : '' }}">
                                {{ $item['output'] }}
                            </div>
                        @endif

                        @if($item['command'] !== null && $item['duration_ms'] > 0)
                            <div class="text-[10px] text-gray-600 flex items-center gap-2 select-none">
                                <span>Exit: {{ $item['exit_code'] }}</span>
                                <span>•</span>
                                <span>{{ $item['duration_ms'] }}ms</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Terminal Input Prompt -->
            <div class="bg-gray-900/90 border-t border-gray-800 p-3">
                <form wire:submit.prevent="execute" class="flex items-center gap-2">
                    <span class="text-emerald-400 font-bold select-none whitespace-nowrap">
                        {{ $userLabel }}:$
                    </span>
                    <input
                        type="text"
                        wire:model="commandInput"
                        x-ref="commandField"
                        autofocus
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="Type a command (e.g. ls, php -v, git status, help)..."
                        class="flex-1 bg-transparent border-0 focus:ring-0 text-white font-mono text-xs sm:text-sm placeholder-gray-600 focus:outline-none"
                    />
                    <button
                        type="submit"
                        class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-xs font-semibold transition flex items-center gap-1"
                    >
                        <span>Run</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Terminal Footer Status Bar -->
            <div class="bg-gray-950 border-t border-gray-900 px-4 py-1.5 flex items-center justify-between text-[11px] text-gray-500 select-none">
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Jailed execution environment & audit logger active.</span>
                </div>
                <div class="truncate max-w-sm text-gray-600">
                    CWD: {{ $cwd }}
                </div>
            </div>
        </div>
    @endif
</div>
