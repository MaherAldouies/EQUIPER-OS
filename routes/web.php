<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Web\BrandVoiceController;
use App\Http\Controllers\Web\CampaignController;
use App\Http\Controllers\Web\ContentApprovalController;
use App\Http\Controllers\Web\ContentCalendarController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\GoogleOAuthController;
use App\Http\Controllers\Web\ProductController;
use App\Livewire\Approvals\Queue as ApprovalsQueue;
use App\Livewire\Inbox\Index as InboxIndex;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Settings\Integrations as IntegrationsSettings;
use App\Livewire\Tasks\Index as TasksIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| No public registration (PRD F2) — the Owner adds Team Members directly
| (Users\Index Livewire component, permission: team.manage), setting
| their password manually rather than depending on outbound mail.
*/

Route::get('/', fn () => redirect()->route('dashboard'))->middleware('auth');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware(['auth', 'current-organization'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/sync-now', [DashboardController::class, 'syncNow'])
        ->name('dashboard.sync-now')
        ->middleware('permission:integration.configure');

    Route::middleware('permission:product.view')->group(function () {
        Route::get('products', ProductsIndex::class)->name('products.index');
        // No single Product instance to run ProductPolicy against for a
        // bulk action, so authorization is coarse-grained here instead
        // (matches ProductController::bulkRecategorize()'s own doc comment).
        Route::post('products/bulk-recategorize', [ProductController::class, 'bulkRecategorize'])
            ->name('products.bulk-recategorize')
            ->middleware('permission:product.manage_category');
        Route::post('products/{product}/enrich', [ProductController::class, 'enrich'])->name('products.enrich');
    });

    Route::middleware('permission:content.view')->group(function () {
        Route::get('approvals', ApprovalsQueue::class)->name('approvals.index');
        Route::post('approvals/{approval}/approve', [ContentApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{approval}/reject', [ContentApprovalController::class, 'reject'])->name('approvals.reject');

        Route::get('content-calendar', [ContentCalendarController::class, 'index'])->name('content-calendar.index');
        Route::post('content-calendar/{asset}/schedule', [ContentCalendarController::class, 'schedule'])->name('content-calendar.schedule');
        Route::post('content-calendar/{asset}/confirm-published', [ContentCalendarController::class, 'confirmPublished'])->name('content-calendar.confirm-published');
        Route::post('content-calendar/{asset}/publish-now', [ContentCalendarController::class, 'publishNow'])
            ->name('content-calendar.publish-now')
            ->middleware('permission:social.manage');
    });

    Route::middleware('permission:brand_voice.manage')->group(function () {
        Route::get('brand-voice', [BrandVoiceController::class, 'edit'])->name('brand-voice.edit');
        Route::post('brand-voice', [BrandVoiceController::class, 'update'])->name('brand-voice.update');
    });

    Route::middleware('permission:campaign.view')->group(function () {
        Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store')->middleware('permission:campaign.manage');
        Route::post('campaigns/{campaign}/complete', [CampaignController::class, 'complete'])->name('campaigns.complete');
    });

    Route::middleware('permission:team.manage')->group(function () {
        Route::get('users', UsersIndex::class)->name('users.index');
    });

    Route::middleware('permission:task.manage')->group(function () {
        Route::get('tasks', TasksIndex::class)->name('tasks.index');
    });

    Route::middleware('permission:social.manage')->group(function () {
        Route::get('inbox', InboxIndex::class)->name('inbox.index');
    });

    Route::middleware('permission:integration.configure')->group(function () {
        Route::get('settings/integrations', IntegrationsSettings::class)->name('settings.integrations');

        Route::get('integrations/google/connect/{provider}', [GoogleOAuthController::class, 'connect'])
            ->where('provider', 'google_analytics|google_merchant')
            ->name('integrations.google.connect');
        Route::get('integrations/google/callback', [GoogleOAuthController::class, 'callback'])
            ->name('integrations.google.callback');
    });
});
