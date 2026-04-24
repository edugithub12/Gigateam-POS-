<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuotationResource\Pages;
use App\Models\Quotation;
use App\Models\Customer;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documents';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'quotation_number';

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
            Forms\Components\Section::make('Client Information')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Existing Customer')
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
                        })
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('client_name')
                        ->required()
                        ->label('Client Name')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('client_phone')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('client_email')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('site_location')
                        ->label('Site / Installation Location')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('client_address')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Quotation Items')
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
                                ->live()
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->prefix('KES')
                                ->live()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('cost_price')
                                ->label('Cost Price')
                                ->numeric()
                                ->prefix('KES')
                                ->columnSpan(2)
                                ->hidden(),

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
                        ->default(false),

                    Forms\Components\DatePicker::make('valid_until')
                        ->label('Valid Until')
                        ->default(now()->addDays(30)),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn () => Auth::id()),

                    Forms\Components\Textarea::make('notes')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('terms')
                        ->label('Terms & Conditions')
                        ->rows(3)
                        ->default('This quotation is valid for 30 days. 50% deposit required before commencement of work.')
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
                Tables\Columns\TextColumn::make('quotation_number')
                    ->label('Quotation #')
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
                    ->color(fn (string $state): string => Quotation::$statusColors[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => Quotation::$statuses[$state] ?? $state),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total (KES)')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Quotation::$statuses),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (Quotation $record) => route('quotations.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('submit_for_approval')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (Quotation $record) => $record->status === 'draft')
                    ->action(function (Quotation $record) {
                        $record->update([
                            'status'       => 'pending_approval',
                            'submitted_at' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Quotation $record) => $record->status === 'pending_approval' && Auth::user()->hasRole('admin'))
                    ->action(function (Quotation $record) {
                        $record->update([
                            'status'      => 'approved',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Quotation::where('status', 'pending_approval')->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'edit'   => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }
}