<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Services\MercadoPagoClient;
use App\Services\PremiumEntitlementService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    use ResolvesWorkspace;

    public function configuration(Request $request): JsonResponse
    {
        $workspace = $this->workspace($request);
        abort_unless($this->role($request, $workspace) === 'owner', 403, __('api.billing_owner'));
        $plans = collect(config('mercadopago.premium_plans'))->map(fn (array $plan) => [
            'label' => $plan['label'],
            'amount' => (float) $plan['amount'],
            'duration_months' => (int) $plan['duration_months'],
            'max_installments' => (int) $plan['max_installments'],
        ]);

        return response()->json([
            'public_key' => config('mercadopago.public_key'),
            'currency' => config('mercadopago.currency', 'BRL'),
            'payer' => ['email' => $request->user()->email],
            'plans' => $plans,
            'configured' => filled(config('mercadopago.public_key'))
                && filled(config('mercadopago.access_token'))
                && $plans->every(fn (array $plan) => $plan['amount'] > 0),
        ]);
    }

    public function payment(
        Request $request,
        MercadoPagoClient $client,
        PremiumEntitlementService $entitlements,
    ): JsonResponse {
        $workspace = $this->workspace($request);
        abort_unless($this->role($request, $workspace) === 'owner', 403, __('api.billing_owner'));

        $cycles = array_keys(config('mercadopago.premium_plans'));
        $data = $request->validate([
            'billing_cycle' => ['required', Rule::in($cycles)],
            'payment_method_id' => ['required', 'string', 'max:50'],
            'token' => ['nullable', 'string', 'max:200'],
            'issuer_id' => ['nullable'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:12'],
            'payer.email' => ['required', 'email'],
            'payer.identification.type' => ['nullable', 'string', 'max:10'],
            'payer.identification.number' => ['nullable', 'string', 'max:30'],
        ]);
        $cycle = $data['billing_cycle'];
        $plan = config('mercadopago.premium_plans.'.$cycle);
        if (! config('mercadopago.access_token') || (float) $plan['amount'] <= 0) {
            return response()->json(['message' => __('api.billing_configuration')], 422);
        }
        if ($data['payment_method_id'] !== 'pix' && empty($data['token'])) {
            return response()->json(['message' => __('api.card_token_missing')], 422);
        }
        if ($cycle === 'monthly' && (int) ($data['installments'] ?? 1) !== 1) {
            return response()->json(['message' => __('api.monthly_single_installment')], 422);
        }

        try {
            $payment = $client->createPayment($workspace, $request->user(), $data, $cycle, (string) Str::uuid());
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => __('api.mercado_pago_unavailable')], 502);
        } catch (RequestException $exception) {
            report($exception);
            $details = $exception->response?->json('message');

            return response()->json([
                'message' => $details
                    ? __('api.payment_failed_details', ['details' => $details])
                    : __('api.payment_failed'),
            ], $exception->response?->clientError() ? 422 : 502);
        }

        $entitlements->syncPayment($payment, $cycle);
        $transaction = (array) data_get($payment, 'point_of_interaction.transaction_data', []);

        return response()->json([
            'payment_id' => (string) $payment['id'],
            'status' => $payment['status'] ?? 'pending',
            'status_detail' => $payment['status_detail'] ?? null,
            'billing_cycle' => $cycle,
            'payment_method_id' => $payment['payment_method_id'] ?? $data['payment_method_id'],
            'qr_code' => $transaction['qr_code'] ?? null,
            'qr_code_base64' => $transaction['qr_code_base64'] ?? null,
        ]);
    }
}
