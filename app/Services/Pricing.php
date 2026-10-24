<?php

namespace App\Services;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PromotionType;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Models\Zone;

/**
 * Calcula subtotal, descuento, envío y total en el servidor.
 * El navegador solo muestra lo que devuelve esta clase; nunca manda montos.
 */
class Pricing
{
    /**
     * @param  array{subtotalCents: int, boxSubtotalCents: int, transferSubtotalCents?: int, transferBoxSubtotalCents?: int}  $cart  Resultado de Cart::resolve().
     * @return array{subtotalCents: int, discountCents: int, shippingCents: int, totalCents: int, promotion: ?Promotion, promotionError: ?string, freeShipping: bool, paymentMethod: PaymentMethod}
     */
    public function quote(array $cart, ?Zone $zone, ?string $code, ?string $phone = null, PaymentMethod $method = PaymentMethod::Card): array
    {
        // Los precios dependen de cómo paga el cliente (con transferencia hay descuento).
        if ($method === PaymentMethod::BankTransfer) {
            $cart = [
                'subtotalCents' => $cart['transferSubtotalCents'] ?? $cart['subtotalCents'],
                'boxSubtotalCents' => $cart['transferBoxSubtotalCents'] ?? $cart['boxSubtotalCents'],
            ];
        }

        $subtotal = $cart['subtotalCents'];
        $shipping = $zone?->fee_cents ?? 0;
        $discount = 0;
        $freeShipping = false;
        $promotion = null;
        $error = null;

        $code = $code !== null ? mb_strtoupper(trim($code)) : '';
        if ($code !== '') {
            try {
                $promotion = Promotion::where('code', $code)->first();
                if (! $promotion) {
                    throw new PromotionRejected('Ese código no existe.');
                }
                [$discount, $freeShipping] = $this->evaluate($promotion, $cart, $zone, $phone);
            } catch (PromotionRejected $e) {
                $promotion = null;
                $discount = 0;
                $freeShipping = false;
                $error = $e->getMessage();
            }
        }

        if ($freeShipping) {
            $shipping = 0;
        }

        return [
            'subtotalCents' => $subtotal,
            'discountCents' => $discount,
            'shippingCents' => $shipping,
            'totalCents' => max(0, $subtotal - $discount) + $shipping,
            'promotion' => $promotion,
            'promotionError' => $error,
            'freeShipping' => $freeShipping,
            'paymentMethod' => $method,
        ];
    }

    /**
     * @param  array{subtotalCents: int, boxSubtotalCents: int}  $cart
     * @return array{0: int, 1: bool} [descuento en centavos, envío gratis]
     */
    public function evaluate(Promotion $promotion, array $cart, ?Zone $zone, ?string $phone): array
    {
        $now = now();
        if (! $promotion->active
            || ($promotion->starts_at && $promotion->starts_at->isAfter($now))
            || ($promotion->ends_at && $promotion->ends_at->lessThanOrEqualTo($now))) {
            throw new PromotionRejected('Esta promoción no está vigente.');
        }

        if ($promotion->max_redemptions !== null
            && $promotion->redemptions()->count() >= $promotion->max_redemptions) {
            throw new PromotionRejected('Esta promoción ya se agotó.');
        }

        if ($cart['subtotalCents'] < $promotion->min_subtotal_cents) {
            $min = number_format($promotion->min_subtotal_cents / 100, 2);
            throw new PromotionRejected("Esta promoción aplica en compras desde Q{$min}.");
        }

        $base = $promotion->applies_to === 'boxes' ? $cart['boxSubtotalCents'] : $cart['subtotalCents'];
        if ($base <= 0) {
            throw new PromotionRejected('Esta promoción aplica solo a cajas.');
        }

        if (! $promotion->allow_national && $zone?->delivery_type === DeliveryType::National) {
            throw new PromotionRejected('Esta promoción no aplica a envíos por paquetería.');
        }

        if ($phone !== null) {
            if ($promotion->first_order_only && Order::where('customer_phone', $phone)
                ->whereNotNull('paid_at')
                ->whereNot('status', OrderStatus::Cancelled->value)
                ->exists()) {
                throw new PromotionRejected('Esta promoción es solo para tu primera compra.');
            }

            if ($promotion->once_per_customer && PromotionRedemption::where('promotion_id', $promotion->id)
                ->where('customer_phone', $phone)
                ->exists()) {
                throw new PromotionRejected('Ya usaste esta promoción.');
            }
        }

        return match ($promotion->type) {
            PromotionType::Percent => [intdiv($base * min(100, max(0, $promotion->value)), 100), false],
            PromotionType::Fixed => [min($promotion->value, $base), false],
            PromotionType::FreeShipping => [0, true],
        };
    }
}
