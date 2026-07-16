<?php

namespace App\Http\Controllers;

use App\Models\PaymentWebhookEvent;
use App\Services\MercadoPagoClient;
use App\Services\MercadoPagoWebhookSignature;
use App\Services\PremiumEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, MercadoPagoClient $client, MercadoPagoWebhookSignature $signature, PremiumEntitlementService $entitlements): JsonResponse
    {
        abort_unless($signature->isValid($request), 401);
        $payload = $request->all();
        $resourceId = (string) ($request->query('data.id') ?: $request->query('data_id') ?: Arr::get($payload, 'data.id'));
        $type = (string) ($payload['type'] ?? $request->query('type', 'unknown'));
        abort_if($resourceId === '', 422, __('api.webhook_missing_resource'));

        $providerEventId = implode(':', array_filter([$type, (string) ($payload['id'] ?? $request->header('x-request-id')), $resourceId]));
        $event = PaymentWebhookEvent::firstOrCreate(
            ['provider' => 'mercadopago', 'provider_event_id' => $providerEventId],
            ['type' => (string) ($payload['action'] ?? $type), 'payload' => $payload]
        );
        if (! $event->wasRecentlyCreated || $event->status === 'processed') {
            return response()->json(['received' => true]);
        }

        try {
            if ($type === 'payment') {
                $entitlements->syncPayment($client->getPayment($resourceId));
            }
            $event->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (Throwable $error) {
            $event->update(['status' => 'failed', 'error' => $error->getMessage()]);
            report($error);

            return response()->json(['received' => true], 202);
        }

        return response()->json(['received' => true]);
    }
}
