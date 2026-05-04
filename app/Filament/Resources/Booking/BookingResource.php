<?php

namespace App\Filament\Resources\Booking;

use App\Filament\Resources\Booking\BookingResource\Pages;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Bookings';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('ulid')->disabled(),
            Forms\Components\TextInput::make('contact_name')->disabled(),
            Forms\Components\TextInput::make('contact_email')->disabled(),
            Forms\Components\Select::make('status')
                ->options(collect(BookingStatus::cases())
                    ->mapWithKeys(fn ($c) => [$c->value => ucfirst(str_replace('_', ' ', $c->value))])
                    ->all()),
            Forms\Components\TextInput::make('total_aed_cents')->disabled()
                ->formatStateUsing(fn ($state) => 'AED '.number_format($state / 100, 2)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ulid')->limit(8)->copyable(),
                Tables\Columns\TextColumn::make('starts_at')->dateTime('M j, H:i')->sortable(),
                Tables\Columns\TextColumn::make('contact_name')->searchable(),
                Tables\Columns\TextColumn::make('contact_email')->searchable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'gray' => 'hold',
                    'warning' => 'pending_payment',
                    'success' => 'confirmed',
                    'primary' => 'completed',
                    'danger' => 'cancelled',
                ]),
                Tables\Columns\TextColumn::make('total_aed_cents')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'AED '.number_format($state / 100, 2)),
            ])
            ->defaultSort('starts_at', 'desc')
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
