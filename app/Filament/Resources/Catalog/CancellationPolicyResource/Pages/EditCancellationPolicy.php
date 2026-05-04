<?php

namespace App\Filament\Resources\Catalog\CancellationPolicyResource\Pages;

use App\Filament\Resources\Catalog\CancellationPolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCancellationPolicy extends EditRecord
{
    protected static string $resource = CancellationPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
