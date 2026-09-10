<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-credit-card';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shop Management';
    }

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Transaction Details')
                    ->schema([
                        TextInput::make('order.order_number')
                            ->label('Order Number')
                            ->disabled(),
                        TextInput::make('gateway')
                            ->label('Payment Gateway')
                            ->disabled(),
                        TextInput::make('transaction_id')
                            ->label('Transaction / Payment ID')
                            ->disabled(),
                        TextInput::make('amount')
                            ->prefix('₹')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('currency')
                            ->default('INR')
                            ->disabled(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'successful' => 'Successful',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                        'razorpay' => 'primary',
                        'cod' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable()
                    ->placeholder('N/A'),
                TextColumn::make('amount')
                    ->money('INR')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ((string) $state) {
                        'successful' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'successful' => 'Successful',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('gateway')
                    ->options([
                        'Razorpay' => 'Razorpay',
                        'COD' => 'COD',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
