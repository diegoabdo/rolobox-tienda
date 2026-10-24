<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Baking = 'baking';
    case OutForDelivery = 'out_for_delivery';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Esperando pago',
            self::Paid => 'Pagado',
            self::Baking => 'En el horno',
            self::OutForDelivery => 'En camino',
            self::Shipped => 'Enviado por paquetería',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
            self::Expired => 'Pago no completado',
        };
    }

    /** Estados en los que el pedido ya se cerró (sirve para los plazos de conservación). */
    public function isClosed(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Expired], true);
    }
}
