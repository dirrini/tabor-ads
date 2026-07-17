<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/up', fn () => response()->json(['status' => 'ok']));
Route::get('/', fn () => response()->json(['name' => 'Tabor Ads API', 'status' => 'ok']));
Route::get('/t/{trackingKey}', TrackingController::class)->where('trackingKey', '[0-9a-fA-F-]+(?:\\.gif)?')->middleware('throttle:tracking');
Route::post('/api/webhooks/mercadopago', MercadoPagoWebhookController::class)->middleware('throttle:120,1');

Route::prefix('api')->group(function () {
    Route::get('/csrf-cookie', fn () => response()->noContent());
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

    Route::middleware('auth')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::patch('/auth/preferences', [AuthController::class, 'preferences']);
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1');
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/workspace', [WorkspaceController::class, 'show']);
        Route::post('/workspace/invitations', [WorkspaceController::class, 'invite']);
        Route::post('/workspace/invitations/{token}/accept', [WorkspaceController::class, 'accept']);
        Route::get('/campaigns', [CampaignController::class, 'index']);
        Route::post('/campaigns', [CampaignController::class, 'store']);
        Route::patch('/campaigns/{campaign}', [CampaignController::class, 'update']);
        Route::delete('/campaigns/{campaign}', [CampaignController::class, 'archive']);
        Route::post('/campaigns/{campaign}/ads', [AdController::class, 'store']);
        Route::delete('/ads/{ad}', [AdController::class, 'archive']);
        Route::get('/analytics', AnalyticsController::class)->middleware('throttle:60,1');
        Route::post('/simulation/start', [SimulationController::class, 'start'])->middleware('throttle:6,1');
        Route::post('/simulation/tick', [SimulationController::class, 'tick'])->middleware('throttle:240,1');
        Route::post('/simulation/stop', [SimulationController::class, 'stop'])->middleware('throttle:30,1');
        Route::get('/billing/configuration', [BillingController::class, 'configuration']);
        Route::post('/billing/payment', [BillingController::class, 'payment'])->middleware('throttle:6,1');
        Route::get('/billing/payments/{paymentId}/status', [BillingController::class, 'status'])
            ->where('paymentId', '[0-9]+')
            ->middleware('throttle:20,1');
    });
});
