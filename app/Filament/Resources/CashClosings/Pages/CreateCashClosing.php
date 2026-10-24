<?php

namespace App\Filament\Resources\CashClosings\Pages;

use App\Filament\Resources\CashClosings\CashClosingResource;
use App\Models\CashClosing;
use Carbon\CarbonImmutable;
use Filament\Resources\Pages\CreateRecord;

class CreateCashClosing extends CreateRecord
{
    protected static string $resource = CashClosingResource::class;

    protected static ?string $title = 'Cerrar el día';

    /** Los números se recalculan aquí, en el servidor, al momento de guardar. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $day = CarbonImmutable::parse($data['period_start'], config('rolobox.timezone'));

        return [
            ...CashClosing::snapshot(
                $day,
                $day,
                countedDepositCents: isset($data['counted_cents']) ? (int) $data['counted_cents'] : null,
                openingCashCents: (int) ($data['opening_cash_cents'] ?? 0),
                countedCashCents: isset($data['counted_cash_cents']) ? (int) $data['counted_cash_cents'] : null,
            ),
            'notes' => $data['notes'] ?? null,
            'closed_by' => auth()->id(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
