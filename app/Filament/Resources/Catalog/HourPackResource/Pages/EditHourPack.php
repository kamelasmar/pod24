<?php

namespace App\Filament\Resources\Catalog\HourPackResource\Pages;

use App\Filament\Resources\Catalog\HourPackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHourPack extends EditRecord
{
    protected static string $resource = HourPackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
