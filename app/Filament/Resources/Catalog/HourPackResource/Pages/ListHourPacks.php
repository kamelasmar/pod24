<?php

namespace App\Filament\Resources\Catalog\HourPackResource\Pages;

use App\Filament\Resources\Catalog\HourPackResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHourPacks extends ListRecords
{
    protected static string $resource = HourPackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
