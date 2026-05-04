<?php

namespace App\Filament\Resources\Catalog;

use App\Filament\Resources\Catalog\AddonResource\Pages;
use App\Modules\Catalog\Models\Addon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AddonResource extends Resource
{
    protected static ?string $model = Addon::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('facility_id')
                ->relationship('facility', 'slug')->required(),
            Forms\Components\TextInput::make('name.en')->label('Name (EN)')->required(),
            Forms\Components\Textarea::make('description.en')->label('Description (EN)'),
            Forms\Components\TextInput::make('price_aed_cents')->label('Price (AED cents)')->numeric()->required(),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('facility.slug')->label('Facility'),
            Tables\Columns\TextColumn::make('name.en')->label('Name'),
            Tables\Columns\TextColumn::make('price_aed_cents')->label('Price')
                ->formatStateUsing(fn ($state) => 'AED '.number_format($state / 100, 2)),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAddons::route('/'),
            'create' => Pages\CreateAddon::route('/create'),
            'edit' => Pages\EditAddon::route('/{record}/edit'),
        ];
    }
}
