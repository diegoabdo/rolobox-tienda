<?php

namespace App\Filament\Support;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\PromotionType;
use App\Enums\SalesChannel;
use BackedEnum;

/** Textos y colores en español para los enums del dominio dentro del panel. */
class Labels
{
    /** Valor de un enum o string que viene de un campo del formulario. */
    public static function value(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return $value === null ? null : (string) $value;
    }

    /** @return array<string, string> */
    public static function orderStatuses(): array
    {
        $options = [];
        foreach (OrderStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    public static function orderStatus(mixed $status): string
    {
        $status = $status instanceof OrderStatus ? $status : OrderStatus::tryFrom((string) $status);

        return $status?->label() ?? '—';
    }

    public static function orderStatusColor(mixed $status): string
    {
        $status = $status instanceof OrderStatus ? $status : OrderStatus::tryFrom((string) $status);

        return match ($status) {
            OrderStatus::Paid => 'info',
            OrderStatus::Baking => 'warning',
            OrderStatus::OutForDelivery, OrderStatus::Shipped => 'primary',
            OrderStatus::Delivered => 'success',
            OrderStatus::Cancelled => 'danger',
            default => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function deliveryTypes(): array
    {
        return [
            DeliveryType::Local->value => DeliveryType::Local->label(),
            DeliveryType::National->value => DeliveryType::National->label(),
        ];
    }

    public static function deliveryType(mixed $type): string
    {
        return self::deliveryTypes()[self::value($type)] ?? '—';
    }

    /** @return array<string, string> */
    public static function productTypes(): array
    {
        return [
            ProductType::Single->value => 'Se vende suelto',
            ProductType::Box->value => 'Es una caja',
        ];
    }

    public static function productType(mixed $type): string
    {
        return self::productTypes()[self::value($type)] ?? '—';
    }

    /** @return array<string, string> */
    public static function promotionTypes(): array
    {
        return [
            PromotionType::Percent->value => 'Porcentaje',
            PromotionType::Fixed->value => 'Monto fijo',
            PromotionType::FreeShipping->value => 'Envío gratis',
        ];
    }

    /** @return array<string, string> */
    public static function paymentMethods(): array
    {
        return [
            PaymentMethod::Card->value => PaymentMethod::Card->label(),
            PaymentMethod::BankTransfer->value => PaymentMethod::BankTransfer->label(),
            PaymentMethod::Cash->value => PaymentMethod::Cash->label(),
        ];
    }

    /** @return array<string, string> */
    public static function channels(): array
    {
        return [
            SalesChannel::Online->value => SalesChannel::Online->label(),
            SalesChannel::InPerson->value => SalesChannel::InPerson->label(),
        ];
    }

    public static function channel(mixed $channel): string
    {
        return self::channels()[self::value($channel)] ?? '—';
    }

    public static function paymentMethod(mixed $method): string
    {
        $value = self::value($method);

        return self::paymentMethods()[$value] ?? ($value ?? '—');
    }

    /** @return array<string, string> */
    public static function paymentStatuses(): array
    {
        return [
            PaymentStatus::Pending->value => 'Pendiente',
            PaymentStatus::Processing->value => 'Transferencia en proceso',
            PaymentStatus::Succeeded->value => 'Pagado',
            PaymentStatus::Failed->value => 'Falló',
            PaymentStatus::Expired->value => 'Vencido',
            PaymentStatus::Refunded->value => 'Devuelto',
        ];
    }

    public static function slot(?string $slot): string
    {
        if ($slot === null || $slot === '') {
            return '—';
        }

        return config('rolobox.delivery.slots')[$slot] ?? $slot;
    }
}
