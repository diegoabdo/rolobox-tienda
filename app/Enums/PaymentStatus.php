<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';

    /** El cliente inició una transferencia y el procesador espera que llegue el dinero. */
    case Processing = 'processing';

    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';
}
