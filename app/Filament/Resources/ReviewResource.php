<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chat-bubble-bottom-center-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Marketing & Growth';
    }

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Review Details')
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required(),
                        TextInput::make('user_name')
                            ->required(),
                        TextInput::make('rating')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->required(),
                        Toggle::make('is_approved')
                            ->label('Approved & Live on Website')
                            ->default(true),
                        Textarea::make('comment')
                            ->columnSpanFull()
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable()
                    ->limit(25),
                TextColumn::make('user_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rating')
                    ->badge()
                    ->color(fn (int $state): string => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger')),
                TextColumn::make('comment')
                    ->limit(40)
                    ->searchable(),
                IconColumn::make('is_approved')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('toggle_approval')
                    ->label(fn (Review $record): string => $record->is_approved ? 'Unapprove' : 'Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color(fn (Review $record): string => $record->is_approved ? 'warning' : 'success')
                    ->action(fn (Review $record) => $record->update(['is_approved' => !$record->is_approved])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('product');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
