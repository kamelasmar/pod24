<?php

namespace App\Filament\Resources\Catalog\FacilityResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AvailabilityRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilityRules';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('day_of_week')
                ->options([
                    0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
                    4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
                ])
                ->required(),
            Forms\Components\TimePicker::make('open_time')->required()->seconds(false),
            Forms\Components\TimePicker::make('close_time')->required()->seconds(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('day_of_week')
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->formatStateUsing(fn ($state) => ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$state])
                    ->sortable(),
                Tables\Columns\TextColumn::make('open_time')->time('H:i'),
                Tables\Columns\TextColumn::make('close_time')->time('H:i'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
