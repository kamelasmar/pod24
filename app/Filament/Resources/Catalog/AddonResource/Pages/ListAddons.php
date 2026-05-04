<?php

namespace App\Filament\Resources\Catalog\AddonResource\Pages;

use App\Filament\Resources\Catalog\AddonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAddons extends ListRecords
{
    protected static string $resource = AddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
