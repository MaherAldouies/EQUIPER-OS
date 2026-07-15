<?php

use App\Http\Controllers\Api\SallaWebhookController;
use App\Http\Controllers\Api\SocialInboxWebhookController;
use App\Http\Controllers\Api\V1\AnalyticsSignalController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — External Integration Boundary + Read API
|--------------------------------------------------------------------------
| Per the Infrastructure Architecture document: every inbound external
| system connection is a dedicated, narrow endpoint — never a generic
| catch-all. /v1 below is a read-only JSON API for the same data the web
| dashboard shows, Sanctum-token protected, for future external/mobile
| clients — it reuses the same Repositories the web Livewire/Controllers
| use, not new query logic.
*/

Route::post('/webhooks/salla', [SallaWebhookController::class, 'handle'])
    ->name('webhooks.salla');

Route::get('/webhooks/whatsapp', [SocialInboxWebhookController::class, 'verifyWhatsApp'])
    ->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [SocialInboxWebhookController::class, 'handleWhatsApp'])
    ->name('webhooks.whatsapp');

Route::get('/webhooks/meta', [SocialInboxWebhookController::class, 'verifyMeta'])
    ->name('webhooks.meta.verify');
Route::post('/webhooks/meta', [SocialInboxWebhookController::class, 'handleMeta'])
    ->name('webhooks.meta');

Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('analytics-signals', [AnalyticsSignalController::class, 'index'])->name('analytics-signals.index');
    Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
});
