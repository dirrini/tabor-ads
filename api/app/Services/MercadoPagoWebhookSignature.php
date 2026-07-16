<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class MercadoPagoWebhookSignature
{
    public function isValid(Request $request): bool
    {
        $secret = config('mercadopago.webhook_secret');
        if (! $secret) {
            return true;
        }

        $signature = $this->parts((string) $request->header('x-signature'));
        $requestId = (string) $request->header('x-request-id');
        $dataId = (string) (
            $request->query('data.id')
            ?: $request->query('data_id')
            ?: Arr::get($request->all(), 'data.id')
        );
        if (! isset($signature['ts'], $signature['v1']) || $requestId === '' || $dataId === '') {
            return false;
        }

        $manifest = 'id:'.strtolower($dataId).';request-id:'.$requestId.';ts:'.$signature['ts'].';';

        return hash_equals($signature['v1'], hash_hmac('sha256', $manifest, $secret));
    }

    private function parts(string $header): array
    {
        $parts = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key && $value) {
                $parts[$key] = $value;
            }
        }

        return $parts;
    }
}
