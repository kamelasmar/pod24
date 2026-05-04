<?php

namespace App\Filament\Resources\Catalog\PricingModifierResource\Pages;

use App\Filament\Resources\Catalog\PricingModifierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPricingModifiers extends ListRecords
{
    protected static string $resource = PricingModifierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
