<div>
    @if(!$showTwoFactorStep)
        <div>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-black dark:text-white tracking-tight">Sign into your account</h2>
                <p class="text-xs text-body dark:text-bodydark mt-1">Enter your credentials to access the control plane</p>
            </div>

            <form wire:submit="login" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-black dark:text-white mb-1.5">Email Address</label>
                    <input 
                        type="email" 
                        wire:model="email" 
                        required 
                        autocomplete="email"
                        class="w-full px-3.5 py-2.5 rounded-lg bg-transparent border border-stroke dark:border-strokedark dark:bg-boxdark-2 text-black dark:text-white text-sm placeholder-body dark:placeholder-bodydark focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all"
                        placeholder="user@vortan.local"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-[#DC3545]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-black dark:text-white">Password</label>
                        <a href="#" class="text-xs text-brand-500 hover:text-brand-600">Forgot?</a>
                    </div>
                    <input 
                        type="password" 
                        wire:model="password" 
                        required 
                        autocomplete="current-password"
                        class="w-full px-3.5 py-2.5 rounded-lg bg-transparent border border-stroke dark:border-strokedark dark:bg-boxdark-2 text-black dark:text-white text-sm placeholder-body dark:placeholder-bodydark focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all"
                        placeholder="••••••••••••"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs text-[#DC3545]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="remember" class="w-4 h-4 rounded border-stroke dark:border-strokedark text-brand-500 focus:ring-brand-500">
                        <span class="text-xs text-body dark:text-bodydark">Remember session</span>
                    </label>
                </div>

                <button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    wire:target="login"
                    class="w-full mt-2 py-3 px-4 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-medium text-sm shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-brand-500/50 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                >
                    <svg wire:loading wire:target="login" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Sign In</span>
                </button>
            </form>

            <!-- DEMO CREDENTIAL SHORTCUTS -->
            <div class="mt-8 pt-6 border-t border-stroke dark:border-strokedark">
                <p class="text-[11px] font-semibold text-body dark:text-bodydark uppercase tracking-wider text-center mb-3">One-Click Development Logins</p>
                <div class="grid grid-cols-3 gap-2">
                    <button wire:click="fillCredentials('admin')" type="button" class="px-2 py-1.5 rounded-lg bg-[#EFF2F7] dark:bg-[#313D4A] hover:bg-brand-500 hover:text-white dark:hover:bg-brand-500 text-purple-700 dark:text-purple-300 text-xs font-medium border border-stroke dark:border-strokedark transition-colors">
                        Super Admin
                    </button>
                    <button wire:click="fillCredentials('reseller')" type="button" class="px-2 py-1.5 rounded-lg bg-[#EFF2F7] dark:bg-[#313D4A] hover:bg-brand-500 hover:text-white dark:hover:bg-brand-500 text-blue-700 dark:text-blue-300 text-xs font-medium border border-stroke dark:border-strokedark transition-colors">
                        Reseller
                    </button>
                    <button wire:click="fillCredentials('customer')" type="button" class="px-2 py-1.5 rounded-lg bg-[#EFF2F7] dark:bg-[#313D4A] hover:bg-brand-500 hover:text-white dark:hover:bg-brand-500 text-brand-700 dark:text-brand-300 text-xs font-medium border border-stroke dark:border-strokedark transition-colors">
                        Customer
                    </button>
                </div>
            </div>
        </div>
    @else
        <!-- TWO-FACTOR AUTHENTICATION STEP -->
        <div>
            <div class="mb-6 text-center">
                <div class="inline-flex w-12 h-12 rounded-xl bg-[#FEF8EC] dark:bg-[#D38B0B]/15 border border-[#D38B0B]/20 text-[#D38B0B] dark:text-[#FFBA00] items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                </div>
                <h2 class="text-xl font-bold text-black dark:text-white tracking-tight">Two-Factor Authentication</h2>
                <p class="text-xs text-body dark:text-bodydark mt-1">Enter the 6-digit code from your authenticator app</p>
            </div>

            <form wire:submit="verifyTwoFactor" class="space-y-4">
                <div>
                    <input 
                        type="text" 
                        wire:model="twoFactorCode" 
                        maxlength="6"
                        autofocus
                        class="w-full text-center tracking-[0.5em] text-2xl font-mono px-4 py-3 rounded-lg bg-transparent border border-stroke dark:border-strokedark dark:bg-boxdark-2 text-brand-500 placeholder-body dark:placeholder-bodydark focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                        placeholder="••••••"
                    >
                    @error('twoFactorCode')
                        <p class="mt-1.5 text-xs text-[#DC3545] text-center">{{ $message }}</p>
                    @enderror
                </div>

                <button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    wire:target="verifyTwoFactor"
                    class="w-full py-3 px-4 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-medium text-sm shadow-sm transition-all flex items-center justify-center gap-2"
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
                    class="w-full text-center text-xs text-body dark:text-bodydark hover:text-black dark:hover:text-white pt-2"
                >
                    &larr; Back to login
                </button>
            </form>
        </div>
    @endif
</div>
