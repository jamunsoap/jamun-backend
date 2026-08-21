<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shopping-bag';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shop Management';
    }

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->schema([
                        TextInput::make('order_number')
                            ->disabled()
                            ->required(),
                        Select::make('order_status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'packed' => 'Packed',
                                'shipped' => 'Shipped',
                                'out_for_delivery' => 'Out For Delivery',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record) {
                                    OrderStatusHistory::create([
                                        'order_id' => $record->id,
                                        'status' => $state,
                                        'location' => 'Fulfillment Center',
                                        'comment' => 'Status updated to ' . ucfirst($state) . ' via Admin Panel.',
                                    ]);
                                }
                            }),
                        TextInput::make('payment_method')
                            ->disabled(),
                        Select::make('is_paid')
                            ->options([
                                1 => 'Paid',
                                0 => 'Unpaid',
                            ])
                            ->required(),
                    ])->columns(2),

                Section::make('Customer & Shipping Details')
                    ->schema([
                        TextInput::make('customer_name')->required(),
                        TextInput::make('customer_phone')->required(),
                        TextInput::make('customer_email'),
                        TextInput::make('street')->required(),
                        TextInput::make('city')->required(),
                        TextInput::make('state')->required(),
                        TextInput::make('pincode')->required(),
                        TextInput::make('country')->default('India'),
                    ])->columns(2),

                Section::make('Shipping & Logistics Details (Shiprocket)')
                    ->schema([
                        TextInput::make('courier_name')->placeholder('e.g. BlueDart, Delhivery, Ecom Express'),
                        TextInput::make('tracking_number')->placeholder('e.g. 141234567890'),
                        TextInput::make('awb_code')->placeholder('e.g. AWB-987654'),
                        TextInput::make('shiprocket_order_id')->label('Shiprocket Order ID')->disabled(),
                        TextInput::make('shiprocket_shipment_id')->label('Shiprocket Shipment ID')->disabled(),
                    ])->columns(2),

                Section::make('Financial Details')
                    ->schema([
                        TextInput::make('items_price')->numeric()->prefix('₹')->disabled(),
                        TextInput::make('shipping_price')->numeric()->prefix('₹')->disabled(),
                        TextInput::make('total_price')->numeric()->prefix('₹')->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->searchable(),
                TextColumn::make('total_price')
                    ->label('Total')
                    ->money('INR')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('order_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'confirmed' => 'info',
                        'packed' => 'warning',
                        'shipped' => 'primary',
                        'out_for_delivery' => 'warning',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('is_paid')
                    ->label('Paid')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('order_status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'packed' => 'Packed',
                        'shipped' => 'Shipped',
                        'out_for_delivery' => 'Out For Delivery',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('accept_order')
                        ->label('Accept')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (Order $record) => $record->order_status === 'pending')
                        ->action(function (Order $record) {
                            $record->update(['order_status' => 'confirmed']);
                            OrderStatusHistory::create([
                                'order_id' => $record->id,
                                'status' => 'confirmed',
                                'location' => 'Merchant Store',
                                'comment' => 'Order accepted and confirmed by merchant.',
                            ]);
                        }),
                    Action::make('pack_order')
                        ->label('Pack')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->visible(fn (Order $record) => $record->order_status === 'confirmed')
                        ->action(function (Order $record) {
                            $record->update(['order_status' => 'packed']);
                            OrderStatusHistory::create([
                                'order_id' => $record->id,
                                'status' => 'packed',
                                'location' => 'Fulfillment Center',
                                'comment' => 'Order packed and prepared for shipment.',
                            ]);
                        }),
                    Action::make('ship_order')
                        ->label('Ship')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->visible(fn (Order $record) => $record->order_status === 'packed')
                        ->action(function (Order $record) {
                            $record->update(['order_status' => 'shipped']);
                            OrderStatusHistory::create([
                                'order_id' => $record->id,
                                'status' => 'shipped',
                                'location' => 'Gujarat Hub',
                                'comment' => 'Package handed over to Express Courier.',
                            ]);
                        }),
                    Action::make('out_for_delivery')
                        ->label('Out for Delivery')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->visible(fn (Order $record) => $record->order_status === 'shipped')
                        ->action(function (Order $record) {
                            $record->update(['order_status' => 'out_for_delivery']);
                            OrderStatusHistory::create([
                                'order_id' => $record->id,
                                'status' => 'out_for_delivery',
                                'location' => 'Local Delivery Hub',
                                'comment' => 'Package is out for delivery with courier agent.',
                            ]);
                        }),
                    Action::make('deliver_order')
                        ->label('Deliver')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Order $record) => in_array($record->order_status, ['shipped', 'out_for_delivery']))
                        ->action(function (Order $record) {
                            $record->update([
                                'order_status' => 'delivered',
                                'is_delivered' => true,
                                'delivered_at' => now(),
                                'is_paid' => true,
                            ]);
                            OrderStatusHistory::create([
                                'order_id' => $record->id,
                                'status' => 'delivered',
                                'location' => 'Customer Destination',
                                'comment' => 'Order successfully delivered to customer.',
                            ]);
                        }),
                    Action::make('shiprocket_push')
                        ->label('Push to Shiprocket')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Push Order to Shiprocket')
                        ->modalDescription('This will create the shipment in Shiprocket and generate an AWB tracking number.')
                        ->visible(fn (Order $record) => empty($record->awb_code) && $record->order_status !== 'cancelled')
                        ->action(function (Order $record) {
                            $service = app(\App\Services\ShiprocketService::class);
                            $result = $service->createShipment($record);
                            
                            if ($result['success']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Shiprocket Response')
                                    ->body(json_encode($result['raw_data'] ?? 'No raw data'))
                                    ->success()
                                    ->persistent()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Shiprocket Error')
                                    ->body($result['message'])
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),
                    Action::make('cancel_order')
                        ->label('Cancel Order')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Order')
                        ->modalDescription('Are you sure you want to cancel this order? This will also attempt to cancel the shipment in Shiprocket if applicable.')
                        ->visible(fn (Order $record) => $record->order_status !== 'cancelled' && $record->order_status !== 'delivered')
                        ->action(function (Order $record) {
                            if (!empty($record->shiprocket_order_id)) {
                                $service = app(\App\Services\ShiprocketService::class);
                                $result = $service->cancelOrder($record);
                                if (!$result['success']) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Shiprocket Cancel Failed')
                                        ->body($result['message'])
                                        ->danger()
                                        ->persistent()
                                        ->send();
                                    return;
                                }
                            }

                            $record->update(['order_status' => 'cancelled']);
                            OrderStatusHistory::create([
                                'order_id' => $record->id,
                                'status' => 'cancelled',
                                'location' => 'Merchant Store',
                                'comment' => 'Order was cancelled by the merchant.',
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Order Cancelled')
                                ->body('Order has been successfully cancelled locally and in Shiprocket (if applicable).')
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
