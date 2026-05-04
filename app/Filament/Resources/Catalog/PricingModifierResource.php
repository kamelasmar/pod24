<?php

namespace App\Filament\Resources\Catalog;

use App\Filament\Resources\Catalog\PricingModifierResource\Pages;
use App\Modules\Catalog\Models\PricingModifier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PricingModifierResource extends Resource
{
    protected static ?string $model = PricingModifier::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('facility_id')->relationship('facility', 'slug')->required(),
            Forms\Components\Select::make('type')
                ->options(['weekend' => 'Weekend', 'after_hours' => 'After hours'])
                ->required()
                ->reactive(),
            Forms\Components\TextInput::make('percentage')->numeric()->required()->suffix('%'),
            Forms\Components\TimePicker::make('after_hours_start')->visible(fn ($get) => $get('type') === 'after_hours'),
            Forms\Components\TimePicker::make('after_hours_end')->visible(fn ($get) => $get('type') === 'after_hours'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('facility.slug'),
            Tables\Columns\TextColumn::make('type'),
            Tables\Columns\TextColumn::make('percentage')->suffix('%'),
            Tables\Columns\TextColumn::make('after_hours_start')->time('H:i'),
            Tables\Columns\TextColumn::make('after_hours_end')->time('H:i'),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPricingModifiers::route('/'),
            'create' => Pages\CreatePricingModifier::route('/create'),
            'edit' => Pages\EditPricingModifier::route('/{record}/edit'),
        ];
    }
}
