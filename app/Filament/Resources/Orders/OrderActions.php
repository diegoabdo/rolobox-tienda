<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** Botones para avanzar el estado de un pedido. Se usan en la tabla y en la vista del pedido. */
class OrderActions
{
    /** @return list<Action> */
    public static function all(): array
    {
        return [
            self::move('bake', OrderStatus::Baking, 'Pasar al horno', Heroicon::OutlinedFire, 'warning'),
            self::move('outForDelivery', OrderStatus::OutForDelivery, 'Salió a entregar', Heroicon::OutlinedTruck, 'primary'),
            self::move('shipped', OrderStatus::Shipped, 'Enviado por paquetería', Heroicon::OutlinedPaperAirplane, 'primary'),
            self::move('deliver', OrderStatus::Delivered, 'Marcar entregado', Heroicon::OutlinedCheckCircle, 'success', confirm: true),
            self::cancel(),
        ];
    }

    private static function move(string $name, OrderStatus $to, string $label, Heroicon $icon, string $color, bool $confirm = false): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (Order $record): bool => $record->canMoveTo($to))
            ->requiresConfirmation($confirm)
            ->modalHeading(fn (Order $record): string => "{$label} · {$record->reference}")
            ->action(function (Order $record) use ($to): void {
                $record->moveTo($to);

                Notification::make()
                    ->title("{$record->reference}: {$to->label()}")
                    ->success()
                    ->send();
            });
    }

    private static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('Cancelar pedido')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Order $record): bool => $record->canMoveTo(OrderStatus::Cancelled))
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record): string => "¿Cancelar {$record->reference}?")
            ->modalDescription(fn (Order $record): string => $record->isPaid()
                ? 'Este pedido ya está pagado. Si lo pide el cliente, la política es sin reembolsos. Si cancela Twist & Rolls porque no puede cumplir, hay que devolver lo pagado desde el panel de Recurrente.'
                : 'Todavía no se pagó. Si el cliente paga después de cancelarlo, el pago queda registrado y aparece una advertencia en el log.')
            ->modalSubmitActionLabel('Sí, cancelar')
            ->action(function (Order $record): void {
                $record->moveTo(OrderStatus::Cancelled);

                Notification::make()
                    ->title("{$record->reference} cancelado")
                    ->danger()
                    ->send();
            });
    }
}
