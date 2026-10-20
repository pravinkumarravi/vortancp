<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Email Security & Anti-Spam Protection
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Rspamd and SpamAssassin mail filtering, automated greylisting, RBL defenses, and sender allowlists/blocklists.
            </p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Core Protection Settings (Left 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            <form wire:submit.prevent="savePolicy" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-xs space-y-6">
                <div class="border-b border-gray-200 dark:border-gray-700 pb-3">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Anti-Spam Engine & Threshold Scoring</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Control automated scoring heuristics and quarantine behaviors.</p>
                </div>

                <!-- Toggles -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <div>
                            <span class="text-xs font-semibold text-gray-900 dark:text-white block">SpamAssassin Filter</span>
                            <span class="text-[11px] text-gray-500">Heuristic mail analysis</span>
                        </div>
                        <input type="checkbox" wire:model="spam_filter_enabled" class="rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4" />
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <div>
                            <span class="text-xs font-semibold text-gray-900 dark:text-white block">Automated Greylisting</span>
                            <span class="text-[11px] text-gray-500">Temporarily defers unverified MTAs</span>
                        </div>
                        <input type="checkbox" wire:model="greylisting_enabled" class="rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4" />
                    </div>
                </div>

                <!-- Score Inputs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Spam Tag Score Threshold: <span class="text-emerald-600 dark:text-emerald-400 font-mono font-bold">{{ $required_score }}</span>
                        </label>
                        <input type="number" step="0.5" min="1" max="20" wire:model="required_score" class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white font-mono" />
                        <span class="text-[11px] text-gray-500 mt-1 block">Emails with score &ge; this are tagged as spam.</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Outright Reject Score: <span class="text-rose-600 font-mono font-bold">{{ $reject_score }}</span>
                        </label>
                        <input type="number" step="0.5" min="5" max="50" wire:model="reject_score" class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white font-mono" />
                        <span class="text-[11px] text-gray-500 mt-1 block">Emails with score &ge; this are rejected at SMTP level.</span>
                    </div>
                </div>

                <!-- Subject Tag & Auto Delete -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Subject Header Tag</label>
                        <input type="text" wire:model="spam_subject_tag" class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-900 dark:text-white font-mono" />
                    </div>

                    <div class="flex items-center gap-2 pt-6">
                        <input type="checkbox" id="autoDelete" wire:model="auto_delete_spam" class="rounded text-emerald-600 focus:ring-emerald-500 h-4 w-4" />
                        <label for="autoDelete" class="font-medium text-gray-700 dark:text-gray-300">Auto-delete spam without routing to Junk folder</label>
                    </div>
                </div>

                <div class="pt-2 text-right">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        Save Security Policy
                    </button>
                </div>
            </form>

            <!-- Sender Allowlist & Blocklist Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Allowlist -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                        <h3 class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Trusted Allowlist</h3>
                        <span class="text-[10px] text-gray-500">Always Delivered</span>
                    </div>

                    <div class="flex gap-2">
                        <input type="text" wire:model="newAllowlistEntry" placeholder="*@domain.com or user@domain.com" class="flex-1 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-2.5 py-1 text-xs text-gray-900 dark:text-white font-mono" />
                        <button wire:click="addAllowlist" type="button" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition shrink-0">Add</button>
                    </div>

                    <div class="space-y-1.5 max-h-48 overflow-y-auto">
                        @forelse($policy->allowlist ?? [] as $entry)
                            <div class="flex items-center justify-between p-2 rounded bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900 text-xs font-mono text-emerald-800 dark:text-emerald-300">
                                <span class="truncate">{{ $entry }}</span>
                                <button wire:click="removeAllowlist('{{ $entry }}')" type="button" class="text-rose-500 hover:text-rose-700 ml-2 font-bold">&times;</button>
                            </div>
                        @empty
                            <div class="text-gray-400 text-xs py-2 text-center">No allowlist entries configured.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Blocklist -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                        <h3 class="text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Sender Blocklist</h3>
                        <span class="text-[10px] text-gray-500">Auto-Rejected</span>
                    </div>

                    <div class="flex gap-2">
                        <input type="text" wire:model="newBlocklistEntry" placeholder="*@spammer.xyz" class="flex-1 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-2.5 py-1 text-xs text-gray-900 dark:text-white font-mono" />
                        <button wire:click="addBlocklist" type="button" class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg transition shrink-0">Block</button>
                    </div>

                    <div class="space-y-1.5 max-h-48 overflow-y-auto">
                        @forelse($policy->blocklist ?? [] as $entry)
                            <div class="flex items-center justify-between p-2 rounded bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 text-xs font-mono text-rose-800 dark:text-rose-300">
                                <span class="truncate">{{ $entry }}</span>
                                <button wire:click="removeBlocklist('{{ $entry }}')" type="button" class="text-rose-500 hover:text-rose-700 ml-2 font-bold">&times;</button>
                            </div>
                        @empty
                            <div class="text-gray-400 text-xs py-2 text-center">No blocklist entries configured.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Spam Rules Simulator (Right Col) -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-xs space-y-4">
            <div class="border-b border-gray-200 dark:border-gray-700 pb-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                    Live Heuristic Rule Tester
                </h3>
                <p class="text-[11px] text-gray-500 mt-0.5">Test real-time spam scoring and evaluate rule triggers.</p>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Sender Address</label>
                    <input type="text" wire:model="testSender" class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-white font-mono" />
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Subject Line</label>
                    <input type="text" wire:model="testSubject" class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-white" />
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Message Body</label>
                    <textarea rows="4" wire:model="testBody" class="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-2.5 py-1.5 text-xs text-gray-900 dark:text-white"></textarea>
                </div>

                <button
                    wire:click="evaluateTestMessage"
                    type="button"
                    class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-xs shadow-sm transition"
                >
                    Analyze Message Score
                </button>
            </div>

            <!-- Simulation Verdict Result -->
            @if($testResult)
                <div class="rounded-xl border p-4 space-y-3 {{ $testResult['is_spam'] ? 'border-rose-300 dark:border-rose-900 bg-rose-50 dark:bg-rose-950/20' : 'border-emerald-300 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-950/20' }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider {{ $testResult['is_spam'] ? 'text-rose-700 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400' }}">
                            Verdict: {{ strtoupper($testResult['action']) }}
                        </span>
                        <span class="font-mono text-sm font-bold {{ $testResult['score'] >= 5.0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ $testResult['score'] }} pts
                        </span>
                    </div>

                    <div class="space-y-1">
                        <span class="text-[10px] uppercase font-bold text-gray-500 block">Matched Rules:</span>
                        @foreach($testResult['triggered_rules'] as $rule)
                            <div class="text-[11px] font-mono text-gray-700 dark:text-gray-300 flex items-start gap-1">
                                <span>&bull;</span>
                                <span>{{ $rule }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
