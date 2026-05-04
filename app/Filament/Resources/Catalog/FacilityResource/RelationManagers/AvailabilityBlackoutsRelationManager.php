<?php

namespace App\Filament\Resources\Catalog\FacilityResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AvailabilityBlackoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilityBlackouts';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DateTimePicker::make('starts_at')->required()->seconds(false),
            Forms\Components\DateTimePicker::make('ends_at')->required()->seconds(false),
            Forms\Components\TextInput::make('reason')->required()->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('reason')->searchable(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
