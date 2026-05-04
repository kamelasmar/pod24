<?php

namespace App\Filament\Resources\Content\FaqItemResource\Pages;

use App\Filament\Resources\Content\FaqItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFaqItem extends EditRecord
{
    protected static string $resource = FaqItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
