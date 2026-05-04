<?php

namespace App\Filament\Resources\Catalog\PricingModifierResource\Pages;

use App\Filament\Resources\Catalog\PricingModifierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPricingModifier extends EditRecord
{
    protected static string $resource = PricingModifierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
