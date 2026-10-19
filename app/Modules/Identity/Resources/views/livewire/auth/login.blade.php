<div>
    @if(!$showTwoFactorStep)
        <div>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-white tracking-tight">Sign into your account</h2>
                <p class="text-xs text-slate-400 mt-1">Enter your credentials to access the control plane</p>
            </div>

            <form wire:submit="login" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address</label>
                    <input 
                        type="email" 
                        wire:model="email" 
                        required 
                        autocomplete="email"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all"
                        placeholder="user@vortan.local"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-300">Password</label>
                        <a href="#" class="text-xs text-emerald-400 hover:text-emerald-300">Forgot?</a>
                    </div>
                    <input 
                        type="password" 
                        wire:model="password" 
                        required 
                        autocomplete="current-password"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition-all"
                        placeholder="••••••••••••"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="remember" class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-emerald-600 focus:ring-emerald-500/50">
                        <span class="text-xs text-slate-400">Remember session</span>
                    </label>
                </div>

                <button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    wire:target="login"
                    class="w-full mt-2 py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-semibold text-sm shadow-lg shadow-emerald-600/25 transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/50 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                >
                    <svg wire:loading wire:target="login" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Sign In</span>
                </button>
            </form>

            <!-- DEMO CREDENTIAL SHORTCUTS -->
            <div class="mt-8 pt-6 border-t border-slate-800/80">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider text-center mb-3">One-Click Development Logins</p>
                <div class="grid grid-cols-3 gap-2">
                    <button wire:click="fillCredentials('admin')" type="button" class="px-2 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-purple-300 text-xs font-medium border border-purple-800/50 transition-colors">
                        Super Admin
                    </button>
                    <button wire:click="fillCredentials('reseller')" type="button" class="px-2 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-blue-300 text-xs font-medium border border-blue-800/50 transition-colors">
                        Reseller
                    </button>
                    <button wire:click="fillCredentials('customer')" type="button" class="px-2 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-emerald-300 text-xs font-medium border border-emerald-800/50 transition-colors">
                        Customer
                    </button>
                </div>
            </div>
        </div>
    @else
        <!-- TWO-FACTOR AUTHENTICATION STEP -->
        <div>
            <div class="mb-6 text-center">
                <div class="inline-flex w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                </div>
                <h2 class="text-xl font-bold text-white tracking-tight">Two-Factor Authentication</h2>
                <p class="text-xs text-slate-400 mt-1">Enter the 6-digit code from your authenticator app</p>
            </div>

            <form wire:submit="verifyTwoFactor" class="space-y-4">
                <div>
                    <input 
                        type="text" 
                        wire:model="twoFactorCode" 
                        maxlength="6"
                        autofocus
                        class="w-full text-center tracking-[0.5em] text-2xl font-mono px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-emerald-400 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500"
                        placeholder="••••••"
                    >
                    @error('twoFactorCode')
                        <p class="mt-1.5 text-xs text-rose-400 text-center">{{ $message }}</p>
                    @enderror
                </div>

                <button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    wire:target="verifyTwoFactor"
                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-semibold text-sm shadow-lg shadow-emerald-600/25 transition-all flex items-center justify-center gap-2"
                >
                    <svg wire:loading wire:target="verifyTwoFactor" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Verify Code</span>
                </button>

                <button 
                    type="button" 
                    wire:click="$set('showTwoFactorStep', false)" 
                    class="w-full text-center text-xs text-slate-400 hover:text-white pt-2"
                >
                    &larr; Back to login
                </button>
            </form>
        </div>
    @endif
</div>
