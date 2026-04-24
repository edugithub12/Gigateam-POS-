<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?string $navigationGroup = 'Documents';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'accountant', 'salesperson']) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Invoice Details')
                ->schema([
                    Forms\Components\TextInput::make('invoice_number')
                        ->label('Invoice Number')
                        ->disabled()
                        ->placeholder('Auto-generated on save'),

                    Forms\Components\Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $customer = Customer::find($state);
                                if ($customer) {
                                    $set('client_name', $customer->company_name ?? $customer->name);
                                    $set('client_phone', $customer->phone);
                                    $set('client_email', $customer->email);
                                    $set('client_address', $customer->address);
                                }
                            }
                        }),

                    Forms\Components\TextInput::make('client_name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('client_phone')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('client_email')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('delivery_number')
                        ->label('Delivery No.')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('order_number')
                        ->label('Order No.')
                        ->maxLength(50),

                    Forms\Components\DatePicker::make('due_date')
                        ->label('Due Date'),

                    Forms\Components\Textarea::make('client_address')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Invoice Items')
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
                                            $set('unit_price', $product->selling_price);
                                            $set('cost_price', $product->cost_price);
                                            $set('unit', $product->unit);
                                        }
                                    }
                                })
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('description')
                                ->required()
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('unit')
                                ->default('pcs')
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->prefix('KES')
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('discount')
                                ->numeric()
                                ->prefix('KES')
                                ->default(0)
                                ->columnSpan(2),
                        ])
                        ->columns(12)
                        ->addActionLabel('+ Add Item')
                        ->reorderable()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Totals & Settings')
                ->schema([
                    Forms\Components\TextInput::make('discount_amount')
                        ->label('Overall Discount (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->default(0),

                    Forms\Components\Toggle::make('include_vat')
                        ->label('Include VAT (16%)')
                        ->default(true),

                    Forms\Components\Select::make('status')
                        ->options(Invoice::$statuses)
                        ->default('unpaid')
                        ->required(),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn () => Auth::id()),

                    Forms\Components\Textarea::make('notes')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('footer_text')
                        ->label('Footer Text')
                        ->default('Accounts are due on demand.')
                        ->columnSpanFull(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => Invoice::$statusColors[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => Invoice::$statuses[$state] ?? $state),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total (KES)')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Paid (KES)')
                    ->money('KES')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($state, Invoice $record) =>
                        $state && $state < now() && $record->status !== 'paid' ? 'danger' : null
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Invoice::$statuses),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (Invoice $record) => route('invoices.pdf', $record))
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
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}