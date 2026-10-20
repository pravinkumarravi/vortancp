<?php

declare(strict_types=1);

use App\Modules\Billing\Http\Controllers\BillingApiController;
use App\Modules\Billing\Http\Middleware\ValidateApiKey;
use Illuminate\Support\Facades\Route;

Route::middleware([ValidateApiKey::class])->group(function () {
    Route::post('/accounts/create', [BillingApiController::class, 'createAccount'])->name('billing.accounts.create');
    Route::post('/accounts/{username}/suspend', [BillingApiController::class, 'suspendAccount'])->name('billing.accounts.suspend');
    Route::post('/accounts/{username}/unsuspend', [BillingApiController::class, 'unsuspendAccount'])->name('billing.accounts.unsuspend');
    Route::post('/accounts/{username}/terminate', [BillingApiController::class, 'terminateAccount'])->name('billing.accounts.terminate');
    Route::post('/accounts/{username}/change-package', [BillingApiController::class, 'changePackage'])->name('billing.accounts.change-package');
    Route::get('/accounts/{username}/status', [BillingApiController::class, 'getAccountStatus'])->name('billing.accounts.status');
    Route::get('/accounts', [BillingApiController::class, 'listAccounts'])->name('billing.accounts.index');
    Route::get('/plans', [BillingApiController::class, 'listPlans'])->name('billing.plans.index');
});
