<?php

namespace App\Filament\Resources\Flavors\Pages;

use App\Filament\Resources\Flavors\FlavorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFlavor extends CreateRecord
{
    protected static string $resource = FlavorResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
