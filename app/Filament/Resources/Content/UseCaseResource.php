<?php

namespace App\Filament\Resources\Content;

use App\Filament\Resources\Content\UseCaseResource\Pages;
use App\Modules\Content\Models\UseCase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UseCaseResource extends Resource
{
    protected static ?string $model = UseCase::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title.en')->label('Title (EN)')->required(),
            Forms\Components\Textarea::make('description.en')->label('Description (EN)')->required()->rows(3),
            Forms\Components\TextInput::make('image_path')->label('Image path')->nullable(),
            Forms\Components\Toggle::make('is_published')->default(true),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title.en')->label('Title')->searchable(),
                Tables\Columns\TextColumn::make('description.en')->label('Description')->limit(60),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUseCases::route('/'),
            'create' => Pages\CreateUseCase::route('/create'),
            'edit' => Pages\EditUseCase::route('/{record}/edit'),
        ];
    }
}
