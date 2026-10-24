<?php

namespace App\Filament\Resources\CashClosings\Pages;

use App\Filament\Resources\CashClosings\CashClosingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCashClosing extends ViewRecord
{
    protected static string $resource = CashClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Reabrir el día')
                ->modalHeading('¿Reabrir este día?')
                ->modalDescription('Se borra el cierre para corregir gastos o ventas en persona de ese día. Después vuelves a cerrarlo.')
                ->modalSubmitActionLabel('Sí, reabrir'),
        ];
    }
}
