<?php

namespace App\Filament\Resources;

use App\Exports\ProductsExport;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';

    // Per-shop catalog: shop managers and cashiers can now create/manage
    // their own products, not just super admins. Each shop owns its rows.
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accountant', 'salesperson', 'manager']) ?? false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if ($user?->isSuperAdmin()) return true;

        // Shop managers can add products to their own shop's catalog
        return $user && activeShopId() && $user->shopRoleAt(activeShopId()) === 'shop_manager';
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if ($user?->isSuperAdmin()) return true;

        return $user && $user->shopRoleAt($record->location_id) === 'shop_manager';
    }

    public static function canDelete($record): bool
    {
        return self::canEdit($record);
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
                        // Unique only within THIS shop's catalog, not globally —
                        // two shops can both have a product with SKU "PHN-001".
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn ($rule) =>
                                $rule->where('location_id', activeShopId())
                        )
                        ->maxLength(100)
                        ->helperText('Must be unique within your shop only — other shops may reuse this SKU.'),

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

                    Forms\Components\FileUpload::make('image')
                        ->label('Product Image')
                        ->image()
                        ->nullable()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('1:1')
                        ->imageResizeTargetWidth('400')
                        ->imageResizeTargetHeight('400')
                        ->directory('products')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText('Optional. Only shown in POS — never printed on documents.')
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Pricing')
                ->schema([
                    Forms\Components\TextInput::make('cost_price')
                        ->label('Cost Price (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->minValue(0)
                        ->default(0),

                    Forms\Components\TextInput::make('selling_price')
                        ->label('Selling Price (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->minValue(0)
                        ->required()
                        ->default(0),

                    Forms\Components\TextInput::make('installation_price')
                        ->label('Installation Price (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->minValue(0)
                        ->default(0),
                ])->columns(3),

            Forms\Components\Section::make('Stock')
                ->schema([
                    Forms\Components\Toggle::make('is_service')
                        ->label('Service Item (no stock tracking)')
                        ->reactive()
                        ->default(false),

                    // Each shop enters their own opening stock directly —
                    // there's no shared warehouse quantity anymore.
                    Forms\Components\TextInput::make('quantity')
                        ->label('Opening Stock')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->hidden(fn (Forms\Get $get) => $get('is_service'))
                        ->helperText('Stock quantity for your shop only. Other shops manage their own stock of the same product separately.'),

                    Forms\Components\TextInput::make('reorder_point')
                        ->label('Low Stock Alert At')
                        ->numeric()
                        ->default(5)
                        ->hidden(fn (Forms\Get $get) => $get('is_service')),

                    Forms\Components\TextInput::make('barcode')
                        ->maxLength(100)
                        ->helperText('Optional — used for quick scanning in POS.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $isAdmin = auth()->user()?->isSuperAdmin();

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(null)
                    ->width(40)
                    ->height(40),

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

                // Visible only to super admin viewing "All Shops" — shows
                // which shop's catalog this row belongs to.
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Shop')
                    ->badge()
                    ->color('gray')
                    ->visible(fn () => $isAdmin && activeShopId() === null),

                Tables\Columns\TextColumn::make('brand')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Price (KES)')
                    ->money('KES')
                    ->sortable(),

                // Stock now lives directly on the product row.
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Stock')
                    ->alignCenter()
                    ->formatStateUsing(fn (Product $record) =>
                        $record->is_service ? 'Service' : (string) $record->quantity
                    )
                    ->color(fn (Product $record) => match (true) {
                        $record->is_service        => 'gray',
                        $record->isOutOfStock()    => 'danger',
                        $record->isLowStock()      => 'warning',
                        default                     => 'success',
                    }),

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

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query) =>
                        $query->where('is_service', false)
                              ->where('quantity', '>', 0)
                              ->whereColumn('quantity', '<=', 'reorder_point')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) =>
                        $query->where('is_service', false)->where('quantity', '<=', 0)
                    )
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin']))
                    ->action(fn () => Excel::download(
                        new ProductsExport(activeShopId()),
                        'gigateam-products-' . now()->format('Y-m-d') . '.xlsx'
                    )),
            ])
            ->actions([
                // "Check Other Locations" removed — under the per-shop catalog
                // model, products no longer share a row across shops. To see
                // what other shops carry, use the Network Stock page instead.
                Tables\Actions\EditAction::make()
                    ->visible(fn (Product $record) => self::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Product $record) => self::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin'])),
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
        // Product's global ShopScope already filters to the active shop,
        // or shows everything if super admin has "All Shops" selected.
        $count = Product::where('is_service', false)
            ->where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_point')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}