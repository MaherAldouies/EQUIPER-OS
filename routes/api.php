<?php

use App\Http\Controllers\Api\SallaWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — External Integration Boundary
|--------------------------------------------------------------------------
| Per the Infrastructure Architecture document: every inbound external
| system connection is a dedicated, narrow endpoint — never a generic
| catch-all. Internal application routes (dashboard, content review,
| etc.) belong in routes/web.php and are out of scope for this Sprint 0
| scaffold; they follow once the corresponding feature's UI work begins.
*/

Route::post('/webhooks/salla', SallaWebhookController::class)
    ->name('webhooks.salla');
