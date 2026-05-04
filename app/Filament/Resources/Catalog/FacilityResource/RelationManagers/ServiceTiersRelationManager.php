<?php

namespace App\Filament\Resources\Catalog\FacilityResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceTiersRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceTiers';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description.en')->label('Description (EN)'),
            Forms\Components\TextInput::make('base_hourly_rate_aed_cents')
                ->label('Base hourly rate (AED cents)')
                ->numeric()
                ->required()
                ->helperText('AED 254.00 = 25400 cents'),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable(),
                Tables\Columns\TextColumn::make('base_hourly_rate_aed_cents')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state) => 'AED '.number_format($state / 100, 2)),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
