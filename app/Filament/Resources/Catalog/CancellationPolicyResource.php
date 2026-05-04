<?php

namespace App\Filament\Resources\Catalog;

use App\Filament\Resources\Catalog\CancellationPolicyResource\Pages;
use App\Modules\Catalog\Models\CancellationPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CancellationPolicyResource extends Resource
{
    protected static ?string $model = CancellationPolicy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('facility_id')->relationship('facility', 'slug')->required(),
            Forms\Components\TextInput::make('hours_before_min')->numeric()->required()
                ->helperText('e.g., 168 = 7 days'),
            Forms\Components\TextInput::make('refund_percentage')->numeric()->required()->suffix('%'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('facility.slug'),
            Tables\Columns\TextColumn::make('hours_before_min')->sortable(),
            Tables\Columns\TextColumn::make('refund_percentage')->suffix('%'),
        ])->defaultSort('hours_before_min', 'desc')
          ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCancellationPolicies::route('/'),
            'create' => Pages\CreateCancellationPolicy::route('/create'),
            'edit' => Pages\EditCancellationPolicy::route('/{record}/edit'),
        ];
    }
}
