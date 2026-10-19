<?php

namespace App\Contracts;

interface PaymentGateway
{
    /**
     * @return array{payment_url:?string, gateway_payment_id:string, raw:array<string,mixed>}
     */
    public function createPayment(string $reference, float $amount, string $callbackUrl, string $intent): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array{success:bool, transaction_id:?string, amount:?float, status:string, raw:array<string,mixed>}
     */
    public function verify(array $payload): array;
}
