<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoClient
{
    public function createPayment(Workspace $workspace, User $payer, array $formData, string $billingCycle, string $idempotencyKey): array
    {
        $plan = config('mercadopago.premium_plans.'.$billingCycle);
        if (! is_array($plan)) {
            throw new RuntimeException(__('api.invalid_premium_cycle'));
        }
        $amount = (float) $plan['amount'];
        if ($amount <= 0) {
            throw new RuntimeException(__('api.invalid_premium_amount'));
        }

        $paymentMethod = (string) ($formData['payment_method_id'] ?? '');
        $payerData = (array) ($formData['payer'] ?? []);
        $payload = [
            'transaction_amount' => $amount,
            'description' => 'Tabor Ads Premium '.$plan['label'],
            'payment_method_id' => $paymentMethod,
            'external_reference' => 'workspace:'.$workspace->id,
            'statement_descriptor' => 'TABORADS',
            'payer' => [
                'email' => (string) ($payerData['email'] ?? $payer->email),
            ],
            'metadata' => [
                'workspace_id' => $workspace->id,
                'plan_code' => 'premium',
                'billing_cycle' => $billingCycle,
                'duration_months' => (int) $plan['duration_months'],
            ],
        ];

        if (! empty($payerData['identification']['type']) && ! empty($payerData['identification']['number'])) {
            $payload['payer']['identification'] = [
                'type' => (string) $payerData['identification']['type'],
                'number' => preg_replace('/\D/', '', (string) $payerData['identification']['number']),
            ];
        }

        if ($paymentMethod !== 'pix') {
            $payload['token'] = (string) ($formData['token'] ?? '');
            $payload['installments'] = min(
                (int) $plan['max_installments'],
                max(1, (int) ($formData['installments'] ?? 1))
            );
            if (! empty($formData['issuer_id'])) {
                $payload['issuer_id'] = (string) $formData['issuer_id'];
            }
        }

        $notificationUrl = $this->publicNotificationUrl();
        if ($notificationUrl) {
            $payload['notification_url'] = $notificationUrl;
        }

        return $this->request()
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->post($this->url('/v1/payments'), $payload)
            ->throw()
            ->json();
    }

    public function getPayment(string $id): array
    {
        return $this->request()->get($this->url('/v1/payments/'.rawurlencode($id)))->throw()->json();
    }

    private function request(): PendingRequest
    {
        $token = config('mercadopago.access_token');
        if (! $token) {
            throw new RuntimeException(__('api.mercado_pago_not_configured'));
        }

        return Http::withToken($token)->acceptJson()->asJson()->timeout(15);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('mercadopago.base_url'), '/').$path;
    }

    public function publicNotificationUrl(): ?string
    {
        $url = trim((string) config('mercadopago.notification_url'));
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost')
            || in_array($host, ['127.0.0.1', '0.0.0.0', '::1'], true)) {
            return null;
        }

        return $url;
    }
}
