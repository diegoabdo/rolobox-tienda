<?php

namespace App\Services\Payments;

final readonly class RemoteCheckout
{
    public function __construct(
        public string $id,
        public bool $paid,
        public ?int $totalCents = null,
        public ?string $paymentId = null,
        public ?string $cardBrand = null,
        public ?string $cardLast4 = null,
        /** Método con el que se pagó según el procesador: "card", "bank_transfer"… */
        public ?string $paymentMethod = null,
        /** El cliente inició el pago (por ejemplo, una transferencia) y falta que se acredite. */
        public bool $processing = false,
    ) {}
}
