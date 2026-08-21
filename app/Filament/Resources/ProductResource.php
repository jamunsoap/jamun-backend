<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-sparkles';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Shop Management';
    }

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('category')
                            ->default('soap')
                            ->required(),
                        TextInput::make('weight')
                            ->default('125g'),
                        Textarea::make('short_description')
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Pricing & Inventory')
                    ->schema([
                        TextInput::make('price')
                            ->numeric()
                            ->prefix('₹')
                            ->required(),
                        TextInput::make('discount_price')
                            ->numeric()
                            ->prefix('₹')
                            ->default(0),
                        TextInput::make('stock')
                            ->numeric()
                            ->default(50)
                            ->required(),
                    ])->columns(3),

                Section::make('Visibility & Highlights')
                    ->schema([
                        Toggle::make('is_featured')
                            ->label('Feature on Homepage')
                            ->default(true),
                        Toggle::make('is_active')
                            ->label('Active for Sale')
                            ->default(true),
                        TextInput::make('average_rating')
                            ->numeric()
                            ->default(4.90),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('price')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('discount_price')
                    ->money('INR'),
                TextColumn::make('stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 10 ? 'success' : 'danger'),
                IconColumn::make('is_featured')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('average_rating')
                    ->numeric(2)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_featured'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
