<?php

namespace App\Filament\Resources\Flavors\Pages;

use App\Filament\Resources\Flavors\FlavorResource;
use Filament\Resources\Pages\EditRecord;

class EditFlavor extends EditRecord
{
    protected static string $resource = FlavorResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
