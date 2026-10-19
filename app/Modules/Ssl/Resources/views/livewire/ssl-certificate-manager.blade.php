<div class="space-y-6">
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">SSL/TLS Certificates</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Automated ACME Let's Encrypt TLS issuance, custom certificate management, and webserver encryption.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <x-button wire:click="openCustomModal" variant="secondary">
                <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Install Custom SSL
            </x-button>
            <x-button wire:click="openLetsEncryptModal" variant="primary">
                <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Issue Let's Encrypt
            </x-button>
        </div>
    </div>

    <!-- Metrics Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-metric-card 
            title="Total Certificates" 
            :value="$certificates->count()" 
            color="primary"
            subtitle="Installed certificates"
        />
        <x-metric-card 
            title="Active & Valid" 
            :value="$certificates->where('status', 'active')->count()" 
            color="emerald"
            subtitle="Passing health checks"
        />
        <x-metric-card 
            title="Auto-Renewing" 
            :value="$certificates->where('auto_renew', true)->count()" 
            color="indigo"
            subtitle="Managed by ACME"
        />
        <x-metric-card 
            title="Expiring Soon" 
            :value="$certificates->filter(fn($c) => $c->daysRemaining() <= 30)->count()" 
            color="amber"
            subtitle="Needs renewal <= 30d"
        />
    </div>

    <!-- Search & Filters -->
    <x-card>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="relative flex-1 w-full sm:w-auto">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search by domain name..." 
                    class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 text-sm"
                >
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <select 
                    wire:model.live="statusFilter" 
                    class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm py-2 px-3 focus:ring-2 focus:ring-primary-500"
                >
                    <option value="all">All Statuses</option>
                    <option value="active">Active Only</option>
                    <option value="revoked">Revoked</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
        </div>
    </x-card>

    <!-- Certificates Table -->
    <x-card :noPadding="true">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Domain / Common Name</th>
                        <th class="px-6 py-4">Certificate Authority</th>
                        <th class="px-6 py-4">Valid Until</th>
                        <th class="px-6 py-4">Days Left</th>
                        <th class="px-6 py-4">Auto-Renew</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($certificates as $cert)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <!-- Domain Name -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ $cert->domain->domain }}</div>
                                        @if(!empty($cert->sans))
                                            <div class="text-xs text-slate-400 font-mono">+{{ count($cert->sans) - 1 }} SANs</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Issuer -->
                            <td class="px-6 py-4">
                                <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                                    {{ $cert->issuer }}
                                </span>
                            </td>

                            <!-- Valid Until -->
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ $cert->valid_until->format('Y-m-d') }}
                            </td>

                            <!-- Days Left -->
                            <td class="px-6 py-4">
                                @php $days = $cert->daysRemaining(); @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                    @if($days > 30) bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300
                                    @elseif($days > 0) bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300
                                    @else bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 @endif">
                                    {{ $days > 0 ? $days . ' days' : 'Expired' }}
                                </span>
                            </td>

                            <!-- Auto-Renew -->
                            <td class="px-6 py-4">
                                @if($cert->auto_renew)
                                    <span class="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Active
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">Manual</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                <x-status-badge :status="$cert->status" />
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        wire:click="inspectCertificate({{ $cert->id }})" 
                                        class="p-1.5 text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                        title="View Certificate Details"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    @if($cert->status === 'active')
                                        <button 
                                            wire:click="revokeCertificate({{ $cert->id }})" 
                                            wire:confirm="Are you sure you want to revoke this certificate?"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                            title="Revoke Certificate"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No SSL certificates installed. Click "Issue Let's Encrypt" to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Issue Let's Encrypt Modal -->
    @if($showLetsEncryptModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-5 animate-in fade-in duration-200">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Issue Let's Encrypt TLS Certificate</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Automated ACME HTTP-01 challenge issuance with 90-day auto-renewal</p>
                    </div>
                    <button wire:click="$set('showLetsEncryptModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="issueLetsEncrypt" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Domain *</label>
                        <select wire:model="selectedDomainId" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5">
                            @foreach($domains as $d)
                                <option value="{{ $d->id }}">{{ $d->domain }} ({{ $d->hostingAccount->username }})</option>
                            @endforeach
                        </select>
                        @error('selectedDomainId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="includeWww" wire:model="includeWww" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                        <label for="includeWww" class="text-xs font-medium text-slate-700 dark:text-slate-300">
                            Include www wildcard alias (e.g., www.domain.com)
                        </label>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/50 rounded-xl p-3 text-xs text-blue-700 dark:text-blue-300 space-y-1">
                        <p class="font-semibold">Automated Certificate Lifecycle:</p>
                        <ul class="list-disc list-inside space-y-0.5 text-blue-600 dark:text-blue-400">
                            <li>Cryptographically signed with 2048-bit RSA key</li>
                            <li>Automatically updates Nginx/Apache virtual hosts with HTTPS</li>
                            <li>Renews automatically 30 days prior to expiration</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showLetsEncryptModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Issue Certificate</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Install Custom SSL Modal -->
    @if($showCustomModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full p-6 space-y-5 animate-in fade-in duration-200">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Install Custom SSL Certificate</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Upload a commercial certificate (DigiCert, Sectigo, Cloudflare, etc.)</p>
                    </div>
                    <button wire:click="$set('showCustomModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="installCustom" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target Domain *</label>
                        <select wire:model="customDomainId" class="w-full text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5">
                            @foreach($domains as $d)
                                <option value="{{ $d->id }}">{{ $d->domain }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Certificate (CRT/PEM) *</label>
                        <textarea 
                            wire:model="customCertificate" 
                            rows="4" 
                            placeholder="-----BEGIN CERTIFICATE----- ... -----END CERTIFICATE-----" 
                            class="w-full text-xs font-mono rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5"
                        ></textarea>
                        @error('customCertificate') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Private Key (KEY) *</label>
                        <textarea 
                            wire:model="customPrivateKey" 
                            rows="4" 
                            placeholder="-----BEGIN RSA PRIVATE KEY----- ... -----END RSA PRIVATE KEY-----" 
                            class="w-full text-xs font-mono rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5"
                        ></textarea>
                        @error('customPrivateKey') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">CA Intermediate Chain (Optional)</label>
                        <textarea 
                            wire:model="customChain" 
                            rows="3" 
                            placeholder="-----BEGIN CERTIFICATE----- (Intermediate Bundle)" 
                            class="w-full text-xs font-mono rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white p-2.5"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <x-button type="button" wire:click="$set('showCustomModal', false)" variant="secondary">Cancel</x-button>
                        <x-button type="submit" variant="primary">Validate & Install</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Inspect Certificate Modal -->
    @if($showInspectModal && $inspectingCert)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-xl w-full p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Certificate Details: {{ $inspectingCert->domain->domain }}</h3>
                    <button wire:click="$set('showInspectModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3 bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
                        <div>
                            <span class="text-slate-400 block font-medium">Common Name</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $inspectingCert->subject_dn }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Issuer</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $inspectingCert->issuer }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Valid From</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200">{{ $inspectingCert->valid_from->toFormattedDateString() }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Expires On</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200">{{ $inspectingCert->valid_until->toFormattedDateString() }}</span>
                        </div>
                    </div>

                    @if(!empty($inspectingCert->sans))
                        <div>
                            <span class="text-slate-400 block font-medium mb-1">Subject Alternative Names (SANs):</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($inspectingCert->sans as $san)
                                    <span class="font-mono px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $san }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <span class="text-slate-400 block font-medium mb-1">Raw PEM Certificate:</span>
                        <pre class="bg-slate-950 text-emerald-400 p-3 rounded-lg font-mono text-[11px] overflow-x-auto max-h-48 border border-slate-800 select-all">{{ $inspectingCert->certificate }}</pre>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-3 border-t border-slate-100 dark:border-slate-800">
                    <x-button type="button" wire:click="$set('showInspectModal', false)" variant="secondary">Close</x-button>
                </div>
            </div>
        </div>
    @endif
</div>
