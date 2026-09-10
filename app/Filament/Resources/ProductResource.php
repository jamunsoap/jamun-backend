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
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;

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
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->required(),
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

                Section::make('Product Media & Gallery')
                    ->schema([
                        FileUpload::make('images')
                            ->label('Product Images')
                            ->multiple()
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->reorderable()
                            ->columnSpanFull()
                            ->helperText('Upload product images. They will be stored securely and served smoothly to the frontend.'),
                    ]),

                Section::make('Ingredients & Benefits')
                    ->schema([
                        TagsInput::make('ingredients')
                            ->label('Key Ingredients')
                            ->placeholder('Type ingredient & hit Enter')
                            ->helperText('e.g. Wild Jamun Extract, Cold-Pressed Neem Oil'),
                        TagsInput::make('benefits')
                            ->label('Key Benefits')
                            ->placeholder('Type benefit & hit Enter')
                            ->helperText('e.g. Reduces blemishes, Sebum control'),
                    ])->columns(2),

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
                ImageColumn::make('images')
                    ->label('Image')
                    ->circular()
                    ->stacked()
                    ->limit(2)
                    ->disk('public')
                    ->defaultImageUrl('/images/front-box-all.png'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('category')
                    ->badge()
                    ->color('info')
                    ->sortable(),
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
                DeleteAction::make(),
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
