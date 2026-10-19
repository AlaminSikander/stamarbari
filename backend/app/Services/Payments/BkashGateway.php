<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class BkashGateway implements PaymentGateway
{
    public function createPayment(string $reference, float $amount, string $callbackUrl, string $intent): array
    {
        if ($this->sandboxWithoutCredentials()) {
            $id = 'SANDBOX-'.Str::uuid();

            return [
                'payment_url' => $callbackUrl.'?paymentID='.$id.'&status=success&amount='.$amount.'&reference='.$reference,
                'gateway_payment_id' => $id,
                'raw' => ['mode' => 'sandbox-local'],
            ];
        }

        $token = $this->grantToken();
        $response = Http::withHeaders($this->headers($token))
            ->post($this->baseUrl().'/tokenized/checkout/create', [
                'mode' => '0011',
                'payerReference' => $reference,
                'callbackURL' => $callbackUrl,
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => 'BDT',
                'intent' => $intent,
                'merchantInvoiceNumber' => $reference,
            ]);

        if (! $response->successful() || empty($response->json('paymentID'))) {
            throw new RuntimeException('Unable to start bKash payment.');
        }

        $data = $response->json();

        return [
            'payment_url' => $data['bkashURL'] ?? null,
            'gateway_payment_id' => $data['paymentID'],
            'raw' => $data,
        ];
    }

    public function verify(array $payload): array
    {
        if (($payload['status'] ?? '') === 'cancel' || ($payload['status'] ?? '') === 'failure') {
            return [
                'success' => false,
                'transaction_id' => $payload['paymentID'] ?? null,
                'amount' => isset($payload['amount']) ? (float) $payload['amount'] : null,
                'status' => $payload['status'] === 'cancel' ? 'cancelled' : 'failed',
                'raw' => $payload,
            ];
        }

        if ($this->sandboxWithoutCredentials()) {
            return [
                'success' => ($payload['status'] ?? 'success') === 'success',
                'transaction_id' => $payload['paymentID'] ?? ('SANDBOX-'.Str::uuid()),
                'amount' => isset($payload['amount']) ? (float) $payload['amount'] : null,
                'status' => ($payload['status'] ?? 'success') === 'success' ? 'successful' : 'failed',
                'raw' => $payload,
            ];
        }

        $token = $this->grantToken();
        $execute = Http::withHeaders($this->headers($token))
            ->post($this->baseUrl().'/tokenized/checkout/execute', [
                'paymentID' => $payload['paymentID'] ?? null,
            ]);

        $data = $execute->json() ?? [];
        $ok = ($data['transactionStatus'] ?? '') === 'Completed';

        return [
            'success' => $ok,
            'transaction_id' => $data['trxID'] ?? ($payload['paymentID'] ?? null),
            'amount' => isset($data['amount']) ? (float) $data['amount'] : null,
            'status' => $ok ? 'successful' : 'failed',
            'raw' => $data,
        ];
    }

    private function sandboxWithoutCredentials(): bool
    {
        return empty(config('services.bkash.app_key')) && app()->environment(['local', 'testing']);
    }

    private function grantToken(): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'username' => config('services.bkash.username'),
            'password' => config('services.bkash.password'),
        ])->post($this->baseUrl().'/tokenized/checkout/token/grant', [
            'app_key' => config('services.bkash.app_key'),
            'app_secret' => config('services.bkash.app_secret'),
        ]);

        $token = $response->json('id_token');
        if (! $token) {
            throw new RuntimeException('Unable to authenticate with bKash.');
        }

        return $token;
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $token): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'authorization' => $token,
            'x-app-key' => (string) config('services.bkash.app_key'),
        ];
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.bkash.base_url'), '/');
    }
}
