<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'accountant', 'salesperson']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Product Details')
                ->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('sku')
                        ->label('SKU')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100),

                    Forms\Components\TextInput::make('brand')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('model_number')
                        ->maxLength(100),

                    Forms\Components\Select::make('unit')
                        ->options([
                            'pcs'  => 'Pieces (pcs)',
                            'box'  => 'Box',
                            'roll' => 'Roll',
                            'set'  => 'Set',
                            'kg'   => 'Kilograms (kg)',
                            'm'    => 'Metres (m)',
                            'lot'  => 'Lot',
                            'job'  => 'Job',
                            'pts'  => 'Points (pts)',
                            'year' => 'Year',
                        ])
                        ->default('pcs')
                        ->required(),

                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Pricing')
                ->schema([
                    Forms\Components\TextInput::make('cost_price')
                        ->label('Cost Price (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->default(0),

                    Forms\Components\TextInput::make('selling_price')
                        ->label('Selling Price (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->required()
                        ->default(0),

                    Forms\Components\TextInput::make('installation_price')
                        ->label('Installation Price (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->default(0),
                ])->columns(3),

            Forms\Components\Section::make('Stock')
                ->schema([
                    Forms\Components\Toggle::make('is_service')
                        ->label('Service Item (no stock tracking)')
                        ->reactive()
                        ->default(false),

                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Current Stock')
                        ->numeric()
                        ->default(0)
                        ->hidden(fn (Forms\Get $get) => $get('is_service')),

                    Forms\Components\TextInput::make('low_stock_threshold')
                        ->label('Low Stock Alert At')
                        ->numeric()
                        ->default(5)
                        ->hidden(fn (Forms\Get $get) => $get('is_service')),

                    Forms\Components\TextInput::make('barcode')
                        ->maxLength(100),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Price (KES)')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->alignCenter()
                    ->color(fn (Product $record): string => match (true) {
                        $record->is_service    => 'gray',
                        $record->isOutOfStock() => 'danger',
                        $record->isLowStock()   => 'warning',
                        default                 => 'success',
                    })
                    ->formatStateUsing(fn (Product $record): string =>
                        $record->is_service ? 'Service' : (string) $record->stock_quantity
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('is_service')
                    ->label('Type')
                    ->trueLabel('Services only')
                    ->falseLabel('Physical products only'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('admin')),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $lowStock = Product::lowStock()->count();
        return $lowStock > 0 ? (string) $lowStock : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}