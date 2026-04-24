<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryNoteResource\Pages;
use App\Models\DeliveryNote;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DeliveryNoteResource extends Resource
{
    protected static ?string $model = DeliveryNote::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Documents';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'delivery_number';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'accountant', 'salesperson', 'technician']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'salesperson']) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Delivery Details')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options(DeliveryNote::$types)
                        ->default('customer')
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'customer')
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $customer = Customer::find($state);
                                if ($customer) {
                                    $set('recipient_name', $customer->company_name ?? $customer->name);
                                    $set('recipient_phone', $customer->phone);
                                    $set('delivery_address', $customer->address);
                                }
                            }
                        }),

                    Forms\Components\Select::make('technician_id')
                        ->label('Technician')
                        ->relationship('technician', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'technician')
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $tech = Technician::find($state);
                                if ($tech) {
                                    $set('recipient_name', $tech->name);
                                    $set('recipient_phone', $tech->phone);
                                }
                            }
                        }),

                    Forms\Components\TextInput::make('recipient_name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('recipient_phone')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('site_location')
                        ->label('Site / Delivery Location')
                        ->maxLength(255),

                    Forms\Components\DatePicker::make('delivery_date')
                        ->default(today()),

                    Forms\Components\Select::make('status')
                        ->options(DeliveryNote::$statuses)
                        ->default('pending')
                        ->required(),

                    Forms\Components\Textarea::make('delivery_address')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn () => Auth::id()),
                ])->columns(2),

            Forms\Components\Section::make('Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Product')
                                ->options(Product::active()->pluck('name', 'id'))
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('description', $product->name);
                                            $set('unit', $product->unit);
                                        }
                                    }
                                })
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('description')
                                ->required()
                                ->columnSpan(4),

                            Forms\Components\TextInput::make('unit')
                                ->default('pcs')
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('notes')
                                ->placeholder('Serial no., condition...')
                                ->columnSpan(2),
                        ])
                        ->columns(12)
                        ->addActionLabel('+ Add Item')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('footer_text')
                        ->label('Footer Text')
                        ->default('Accounts are due on demand.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('delivery_number')
                    ->label('DN #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'customer'   => 'info',
                        'technician' => 'warning',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => DeliveryNote::$types[$state] ?? $state),

                Tables\Columns\TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => DeliveryNote::$statusColors[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => DeliveryNote::$statuses[$state] ?? $state),

                Tables\Columns\TextColumn::make('delivery_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('site_location')
                    ->label('Location')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(DeliveryNote::$types),

                Tables\Filters\SelectFilter::make('status')
                    ->options(DeliveryNote::$statuses),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'salesperson'])),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (DeliveryNote $record) => route('delivery-notes.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDeliveryNotes::route('/'),
            'create' => Pages\CreateDeliveryNote::route('/create'),
            'edit'   => Pages\EditDeliveryNote::route('/{record}/edit'),
        ];
    }
}