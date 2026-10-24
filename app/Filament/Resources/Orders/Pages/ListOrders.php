<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\InPersonSaleAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            InPersonSaleAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $active = [
            OrderStatus::Paid->value,
            OrderStatus::Baking->value,
            OrderStatus::OutForDelivery->value,
            OrderStatus::Shipped->value,
        ];
        $closed = [OrderStatus::Delivered->value, OrderStatus::Cancelled->value, OrderStatus::Expired->value];

        return [
            'activos' => Tab::make('Por atender')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $active))
                ->badge(Order::whereIn('status', $active)->count()),
            'pendientes' => Tab::make('Esperando pago')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::PendingPayment->value)),
            'cerrados' => Tab::make('Cerrados')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $closed)),
            'todos' => Tab::make('Todos'),
        ];
    }
}
