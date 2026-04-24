<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'sale_number';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'accountant', 'salesperson']) ?? false;
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
            Forms\Components\Section::make('Sale Information')
                ->schema([
                    Forms\Components\TextInput::make('sale_number')
                        ->disabled()
                        ->placeholder('Auto-generated'),

                    Forms\Components\Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('sale_type')
                        ->options(Sale::$saleTypes)
                        ->default('walk_in')
                        ->required(),

                    Forms\Components\Select::make('payment_status')
                        ->options(Sale::$paymentStatuses)
                        ->default('unpaid')
                        ->required(),

                    Forms\Components\TextInput::make('total')
                        ->label('Total (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->disabled(),

                    Forms\Components\TextInput::make('amount_paid')
                        ->label('Amount Paid (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->default(0),

                    Forms\Components\Textarea::make('notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale_number')
                    ->label('Sale #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->default('Walk-in'),

                Tables\Columns\TextColumn::make('sale_type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => Sale::$saleTypes[$state] ?? $state),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total (KES)')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        'unpaid'  => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Sale::$paymentStatuses[$state] ?? $state),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cashier')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options(Sale::$paymentStatuses),

                Tables\Filters\SelectFilter::make('sale_type')
                    ->options(Sale::$saleTypes),

                Tables\Filters\Filter::make('today')
                    ->label('Today only')
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index'  => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit'   => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}