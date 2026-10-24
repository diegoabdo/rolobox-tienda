<?php

namespace App\Filament\Resources\Flavors\Pages;

use App\Filament\Resources\Flavors\FlavorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFlavors extends ListRecords
{
    protected static string $resource = FlavorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
