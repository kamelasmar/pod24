<?php

namespace App\Filament\Resources\Catalog\FacilityResource\Pages;

use App\Filament\Resources\Catalog\FacilityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacility extends EditRecord
{
    protected static string $resource = FacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
