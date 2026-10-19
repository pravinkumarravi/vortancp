<?php

declare(strict_types=1);

use App\Modules\Dashboard\Livewire\AdminDashboard;
use App\Modules\Dashboard\Livewire\CustomerDashboard;
use App\Modules\Dashboard\Livewire\ResellerDashboard;
use App\Modules\Identity\Livewire\Auth\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/login', Login::class)->name('login');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    
    // Gateway Redirector
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->isSuperAdmin() || $user->isServerAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isReseller()) {
            return redirect()->route('reseller.dashboard');
        }

        return redirect()->route('customer.dashboard');
    })->name('dashboard');

    // Logout
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out successfully.');
    })->name('logout');

    // 1. Administrator Panel
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:super_admin,server_admin')
        ->group(function () {
            Route::get('/', AdminDashboard::class)->name('dashboard');
            
            Route::get('/servers', \App\Modules\Servers\Livewire\ServerIndex::class)->name('servers.index');
            Route::get('/accounts', \App\Modules\Accounts\Livewire\AccountIndex::class)->name('accounts.index');
            Route::get('/domains', \App\Modules\Domains\Livewire\DomainIndex::class)->name('domains.index');
            Route::get('/dns', \App\Modules\Dns\Livewire\DnsZoneEditor::class)->name('dns.index');
            Route::get('/ssl', \App\Modules\Ssl\Livewire\SslCertificateManager::class)->name('ssl.index');
            Route::get('/files', \App\Modules\FileManager\Livewire\FileManagerIndex::class)->name('files.index');
            Route::get('/databases', \App\Modules\Databases\Livewire\DatabaseIndex::class)->name('databases.index');
            Route::get('/email', \App\Modules\Email\Livewire\MailboxManager::class)->name('email.index');
            Route::get('/php', \App\Modules\PhpManager\Livewire\PhpManagerIndex::class)->name('php.index');
            Route::get('/ftp', \App\Modules\Ftp\Livewire\FtpIndex::class)->name('ftp.index');
            Route::get('/cron', \App\Modules\Cron\Livewire\CronIndex::class)->name('cron.index');
            Route::get('/backups', \App\Modules\Backups\Livewire\BackupIndex::class)->name('backups.index');
            Route::get('/firewall', \App\Modules\Firewall\Livewire\FirewallIndex::class)->name('firewall.index');

            // Placeholder routes for navigation links
            Route::get('/users', function () {
                return redirect()->route('admin.dashboard');
            })->name('users');

            Route::get('/roles', function () {
                return redirect()->route('admin.dashboard');
            })->name('roles');

            Route::get('/audit', function () {
                return redirect()->route('admin.dashboard');
            })->name('audit.index');
        });

    // 2. Reseller Panel
    Route::prefix('reseller')
        ->name('reseller.')
        ->middleware('role:reseller')
        ->group(function () {
            Route::get('/', ResellerDashboard::class)->name('dashboard');
        });

    // 3. Customer Hosting Panel
    Route::prefix('app')
        ->name('customer.')
        ->middleware('role:customer')
        ->group(function () {
            Route::get('/', CustomerDashboard::class)->name('dashboard');
            Route::get('/domains', \App\Modules\Domains\Livewire\DomainIndex::class)->name('domains.index');
            Route::get('/dns', \App\Modules\Dns\Livewire\DnsZoneEditor::class)->name('dns.index');
            Route::get('/ssl', \App\Modules\Ssl\Livewire\SslCertificateManager::class)->name('ssl.index');
            Route::get('/files', \App\Modules\FileManager\Livewire\FileManagerIndex::class)->name('files.index');
            Route::get('/databases', \App\Modules\Databases\Livewire\DatabaseIndex::class)->name('databases.index');
            Route::get('/email', \App\Modules\Email\Livewire\MailboxManager::class)->name('email.index');
            Route::get('/php', \App\Modules\PhpManager\Livewire\PhpManagerIndex::class)->name('php.index');
            Route::get('/ftp', \App\Modules\Ftp\Livewire\FtpIndex::class)->name('ftp.index');
            Route::get('/cron', \App\Modules\Cron\Livewire\CronIndex::class)->name('cron.index');
            Route::get('/backups', \App\Modules\Backups\Livewire\BackupIndex::class)->name('backups.index');
        });

    // User Profile & Settings
    Route::get('/profile', function () {
        return redirect()->route('dashboard');
    })->name('profile.show');
});
