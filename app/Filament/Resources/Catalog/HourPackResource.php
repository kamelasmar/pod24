<?php

namespace App\Filament\Resources\Catalog;

use App\Filament\Resources\Catalog\HourPackResource\Pages;
use App\Modules\Catalog\Models\HourPack;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HourPackResource extends Resource
{
    protected static ?string $model = HourPack::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('facility_id')->relationship('facility', 'slug')->required(),
            Forms\Components\TextInput::make('name.en')->label('Name (EN)')->required(),
            Forms\Components\Textarea::make('description.en')->label('Description (EN)'),
            Forms\Components\TextInput::make('hours')->numeric()->required(),
            Forms\Components\TextInput::make('price_aed_cents')->label('Price (AED cents)')->numeric()->required(),
            Forms\Components\TextInput::make('expiry_days')->numeric()->default(365),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('facility.slug'),
            Tables\Columns\TextColumn::make('name.en')->label('Name'),
            Tables\Columns\TextColumn::make('hours'),
            Tables\Columns\TextColumn::make('price_aed_cents')->formatStateUsing(fn ($s) => 'AED '.number_format($s / 100, 2)),
            Tables\Columns\TextColumn::make('expiry_days'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHourPacks::route('/'),
            'create' => Pages\CreateHourPack::route('/create'),
            'edit' => Pages\EditHourPack::route('/{record}/edit'),
        ];
    }
}
