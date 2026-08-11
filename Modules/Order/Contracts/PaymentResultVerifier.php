<?php

namespace Modules\Order\Contracts;

interface PaymentResultVerifier
{
    public function verifyResultSignature(array $payload): bool;
}
