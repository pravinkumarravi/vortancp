<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Security Center & WAF
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                ModSecurity Web Application Firewall, OWASP Core Rule Set, malware signature scanner, and server hardening advisor.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if(count($servers) > 1)
                <select wire:model.live="selectedServerId" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($servers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->ip_address }})</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/40 p-4 border border-green-200 dark:border-green-800 flex items-center justify-between">
            <div class="flex items-center text-sm font-medium text-green-800 dark:text-green-200">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/40 p-4 border border-red-200 dark:border-red-800 flex items-center justify-between">
            <div class="flex items-center text-sm font-medium text-red-800 dark:text-red-200">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('activeTab', 'advisor')" class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'advisor' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                Hardening Advisor
            </button>
            <button wire:click="$set('activeTab', 'waf')" class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'waf' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                ModSecurity WAF & CRS
            </button>
            <button wire:click="$set('activeTab', 'scanner')" class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'scanner' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                Malware Scanner ({{ $scans->count() }})
            </button>
            <button wire:click="$set('activeTab', 'quarantine')" class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'quarantine' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                Quarantine Vault ({{ $quarantinedFiles->count() }})
            </button>
        </nav>
    </div>

    @if($activeTab === 'advisor')
        <!-- Hardening Advisor Tab -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Score Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col items-center justify-center text-center">
                <div class="relative w-36 h-36 flex items-center justify-center">
                    <div class="w-32 h-32 rounded-full border-8 {{ $advisorReport['score'] >= 80 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40' : ($advisorReport['score'] >= 60 ? 'border-amber-500 bg-amber-50 dark:bg-amber-950/40' : 'border-red-500 bg-red-50 dark:bg-red-950/40') }} flex flex-col items-center justify-center shadow-inner">
                        <span class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $advisorReport['score'] }}%</span>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Score: {{ $advisorReport['grade'] }}</span>
                    </div>
                </div>
                <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Security Posture Rating</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Evaluated across host firewalls, WAF rules, encryption standards, and process jail boundaries.</p>
            </div>

            <!-- Checks List -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Automated Security Audits</h3>
                <div class="space-y-4">
                    @foreach($advisorReport['checks'] as $check)
                        <div class="flex items-start p-3.5 rounded-lg border {{ $check['passed'] ? 'border-green-200 dark:border-green-900/60 bg-green-50/50 dark:bg-green-950/20' : 'border-amber-200 dark:border-amber-900/60 bg-amber-50/50 dark:bg-amber-950/20' }}">
                            <div class="mt-0.5 mr-3 flex-shrink-0">
                                @if($check['passed'])
                                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $check['title'] }}</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">{{ $check['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    @elseif($activeTab === 'waf')
        <!-- ModSecurity WAF Tab -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Form Configuration -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-5">
                <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">ModSecurity v3 & OWASP CRS Policy</h3>
                    <div>
                        <select wire:model.live="selectedDomainId" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-xs text-gray-900 dark:text-white py-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Global Server Default</option>
                            @foreach($domains as $d)
                                <option value="{{ $d->id }}">{{ $d->domain }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <form wire:submit="saveWafSettings" class="space-y-4">
                    <div class="flex items-center justify-between p-3.5 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white block">Web Application Firewall Engine</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Inspect and block malicious HTTP traffic in real time.</span>
                        </div>
                        <input type="checkbox" wire:model="waf_is_enabled" class="rounded text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">CRS Paranoia Level (1 - 4)</label>
                        <select wire:model="paranoia_level" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="1">Level 1 - Minimal false positives (Standard websites)</option>
                            <option value="2">Level 2 - High security (E-commerce / sensitive portals)</option>
                            <option value="3">Level 3 - Strict enforcement (Banking / enterprise)</option>
                            <option value="4">Level 4 - Maximum paranoia (Mission critical)</option>
                        </select>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">OWASP Attack Vector Filters</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" wire:model="block_sqli" class="rounded text-indigo-600 focus:ring-indigo-500 mr-2">
                                Block SQL Injection (SQLi)
                            </label>
                            <label class="flex items-center text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" wire:model="block_xss" class="rounded text-indigo-600 focus:ring-indigo-500 mr-2">
                                Block Cross-Site Scripting (XSS)
                            </label>
                            <label class="flex items-center text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" wire:model="block_rfi" class="rounded text-indigo-600 focus:ring-indigo-500 mr-2">
                                Block Remote / Local File Inclusion
                            </label>
                            <label class="flex items-center text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" wire:model="block_code_injection" class="rounded text-indigo-600 focus:ring-indigo-500 mr-2">
                                Block PHP / Code Execution
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Excluded Rule IDs (Comma separated)</label>
                        <input type="text" wire:model="disabled_rule_ids_input" placeholder="942100, 941100" class="w-full font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs py-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Custom Directives</label>
                        <textarea wire:model="custom_rules" rows="3" placeholder="SecRule REQUEST_URI &quot;@streq /api/webhook&quot; &quot;id:1001,phase:1,pass,nolog,ctl:ruleRemoveTargetByTag=attack-sqli;ARGS:data&quot;" class="w-full font-mono rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs p-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm">
                            Save WAF Configuration
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preview Snippet -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">Compiled Nginx/Apache ModSec Snippet</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Live directives included in virtual host configuration:</p>
                <pre class="flex-1 bg-gray-900 text-gray-100 p-4 rounded-xl font-mono text-xs overflow-x-auto whitespace-pre leading-relaxed border border-gray-800">{{ $compiledWafConfig }}</pre>
            </div>
        </div>

    @elseif($activeTab === 'scanner')
        <!-- Malware Scanner Tab -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">Launch On-Demand Integrity Scan</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Scan document roots for webshells, obfuscated PHP backdoors, eval/base64 injection, and suspicious payloads.</p>

                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text" wire:model="scan_path" placeholder="Leave empty for all sandboxes, or enter path e.g. /home/user/public_html" class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-mono focus:ring-indigo-500 focus:border-indigo-500">
                    <button wire:click="runScan" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Scan Directory
                    </button>
                </div>
            </div>

            <!-- Recent Scans Table -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent Scan Reports</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Target Path</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Files Scanned</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Threats</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($scans as $scan)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-gray-900 dark:text-white">
                                        {{ $scan->target_path }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                        {{ $scan->scanned_files_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($scan->infected_files_count > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                                {{ $scan->infected_files_count }} infected
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                                Clean
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs capitalize text-gray-600 dark:text-gray-300">
                                        {{ $scan->status }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                        {{ $scan->completed_at ? $scan->completed_at->format('M d, H:i') : 'In progress' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No recent malware scan reports.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @else
        <!-- Quarantine Vault Tab -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Quarantine Isolation Vault</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">Infected files are automatically neutralized and removed from web execution.</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Detected Threat</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Original Location</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quarantined At</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($quarantinedFiles as $q)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                        {{ $q->threat_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono text-gray-700 dark:text-gray-300 max-w-md truncate">
                                    {{ $q->original_path }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ $q->quarantined_at->format('M d, H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button wire:click="restoreQuarantine({{ $q->id }})" class="text-amber-600 hover:text-amber-900 dark:text-amber-400 text-xs">
                                        Restore
                                    </button>
                                    <button wire:confirm="Permanently purge this infected file?" wire:click="deleteQuarantine({{ $q->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400 text-xs">
                                        Purge
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <p class="mt-2 font-medium text-gray-900 dark:text-white">Quarantine Vault is empty</p>
                                    <p class="mt-1 text-gray-500 dark:text-gray-400">No infected files are currently quarantined.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
