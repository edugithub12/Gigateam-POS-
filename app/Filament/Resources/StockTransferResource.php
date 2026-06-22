<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Notifications\TransferStatusNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Stock Transfers';
    protected static ?int $navigationSort = 2;

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        $user   = auth()->user();
        $shopId = activeShopId();

        return $form->schema([
            Forms\Components\Section::make('Transfer Request')
                ->schema([
                    Forms\Components\Select::make('to_location_id')
                        ->label('Requesting For (Your Shop)')
                        ->options(
                            $user->isSuperAdmin()
                                ? Location::active()->pluck('name', 'id')
                                : $user->activeLocations()->pluck('name', 'locations.id')
                        )
                        ->default(fn () => request('to_location_id') ?? $shopId)
                        ->disabled(fn () => ! $user->isSuperAdmin() && ! $user->hasRole('admin'))
                        ->dehydrated()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('from_location_id', null)),

                    Forms\Components\Select::make('from_location_id')
                        ->label('Request Stock From (Shop)')
                        ->options(function (Get $get) {
                            $toLocation = $get('to_location_id');
                            return Location::active()
                                ->when($toLocation, fn ($q) => $q->where('id', '!=', $toLocation))
                                ->pluck('name', 'id');
                        })
                        ->default(fn () => request('from_location_id'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('donor_product_id', null)),

                    Forms\Components\Select::make('donor_product_id')
                        ->label('Product (from their catalog)')
                        ->options(function (Get $get) {
                            $fromLocation = $get('from_location_id');
                            if (! $fromLocation) return [];

                            return Product::withoutShopScope()
                                ->where('location_id', $fromLocation)
                                ->where('is_active', true)
                                ->where('is_service', false)
                                ->where('quantity', '>', 0)
                                ->get()
                                ->mapWithKeys(fn ($p) =>
                                    [$p->id => "{$p->name} ({$p->sku}) — {$p->quantity} available"]
                                );
                        })
                        ->default(fn () => request('donor_product_id'))
                        ->searchable()
                        ->required()
                        ->live()
                        ->helperText('Only products with available stock at the selected shop are shown.'),

                    Forms\Components\Placeholder::make('receiver_note')
                        ->label('')
                        ->content(function (Get $get) {
                            $donorId = $get('donor_product_id');
                            $toId    = $get('to_location_id');
                            if (! $donorId || ! $toId) return '';

                            $donor = Product::withoutShopScope()->find($donorId);
                            if (! $donor) return '';

                            $match = Product::withoutShopScope()
                                ->where('location_id', $toId)
                                ->where(fn ($q) =>
                                    $q->where('sku', $donor->sku)
                                      ->when($donor->barcode, fn ($q2) => $q2->orWhere('barcode', $donor->barcode))
                                )
                                ->first();

                            return $match
                                ? "✓ You already have a matching product ({$match->name}) — stock will be added to it."
                                : "ℹ A new product will be created in your catalog when this transfer is received, copying name, price, and category from the donor.";
                        })
                        ->visible(fn (Get $get) => $get('donor_product_id') && $get('to_location_id')),

                    Forms\Components\TextInput::make('quantity_requested')
                        ->label('Quantity Needed')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Get $get, callable $set) {
                            $donorId = $get('donor_product_id');
                            if (! $donorId) return;

                            $available = Product::withoutShopScope()->find($donorId)?->quantity ?? 0;

                            if ((int) $state > $available) {
                                $set('quantity_requested', $available);
                                Notification::make()
                                    ->warning()
                                    ->title("Only {$available} units available")
                                    ->send();
                            }
                        }),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notes / Reason')
                        ->placeholder('Why do you need this stock?')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Approval')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(StockTransfer::$statuses)
                        ->required(),

                    Forms\Components\TextInput::make('quantity_approved')
                        ->label('Approved Quantity')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Can be less than requested.'),

                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->rows(2)
                        ->visible(fn (Get $get) => $get('status') === 'rejected'),
                ])
                ->columns(2)
                ->visible(fn ($record) => $record && self::canApprove($record)),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        $user   = auth()->user();
        $shopId = activeShopId();

        return $table
            ->modifyQueryUsing(function ($query) use ($user, $shopId) {
                if ($user->isSuperAdmin()) return $query;

                return $query->where(function ($q) use ($user, $shopId) {
                    $q->where('requested_by', $user->id);
                    if ($shopId) {
                        $q->orWhere('from_location_id', $shopId)
                          ->orWhere('to_location_id', $shopId);
                    }
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('donorProduct.name')
                    ->label('Product')
                    ->searchable(),

                Tables\Columns\TextColumn::make('fromLocation.name')
                    ->label('From')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('toLocation.name')
                    ->label('To')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('quantity_requested')
                    ->label('Requested'),

                Tables\Columns\TextColumn::make('quantity_approved')
                    ->label('Approved')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('product_was_auto_created')
                    ->label('New Product?')
                    ->boolean()
                    ->trueIcon('heroicon-o-sparkles')
                    ->falseIcon('heroicon-o-check')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => StockTransfer::$statusColors[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Requested By')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(StockTransfer::$statuses),

                Tables\Filters\SelectFilter::make('from_location_id')
                    ->label('From Shop')
                    ->options(Location::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('to_location_id')
                    ->label('To Shop')
                    ->options(Location::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StockTransfer $r) => $r->status === 'pending' && self::canApprove($r))
                    ->form([
                        Forms\Components\TextInput::make('quantity_approved')
                            ->label('Approved Quantity')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (StockTransfer $record, array $data) {
                        $record->update([
                            'status'            => 'approved',
                            'quantity_approved' => $data['quantity_approved'],
                            'approved_by'       => auth()->id(),
                            'approved_at'       => now(),
                        ]);
                        $record->requestedBy?->notify(new TransferStatusNotification($record));
                        Notification::make()->success()->title('Transfer approved')->send();
                    }),

                Tables\Actions\Action::make('dispatch')
                    ->label('Mark Dispatched')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->visible(fn (StockTransfer $r) => $r->status === 'approved' && self::canApprove($r))
                    ->action(function (StockTransfer $record) {
                        $record->update([
                            'status'        => 'dispatched',
                            'dispatched_at' => now(),
                        ]);
                        $record->requestedBy?->notify(new TransferStatusNotification($record));
                        Notification::make()->success()->title('Marked as dispatched')->send();
                    }),

                Tables\Actions\Action::make('receive')
                    ->label('Confirm Received')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('success')
                    ->visible(fn (StockTransfer $r) => $r->status === 'dispatched' && self::canReceive($r))
                    ->requiresConfirmation()
                    ->modalDescription("Confirming receipt will add this stock to your shop's catalog — creating a new product if you don't already carry it.")
                    ->action(function (StockTransfer $record) {
                        $record->update([
                            'status'      => 'received',
                            'received_by' => auth()->id(),
                            'received_at' => now(),
                        ]);
                        Notification::make()->success()->title('Stock received and added to your catalog')->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (StockTransfer $r) => $r->status === 'pending' && self::canApprove($r))
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason for rejection')
                            ->required(),
                    ])
                    ->action(function (StockTransfer $record, array $data) {
                        $record->update([
                            'status'           => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'approved_by'      => auth()->id(),
                        ]);
                        $record->requestedBy?->notify(new TransferStatusNotification($record));
                        Notification::make()->warning()->title('Transfer rejected')->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ── Permission helpers ────────────────────────────────────────────────────

    private static function canApprove(StockTransfer $record): bool
    {
        $user = auth()->user();
        if ($user->isSuperAdmin() || $user->hasRole('admin')) return true;

        return $user->shopRoleAt($record->from_location_id) === 'shop_manager';
    }

    private static function canReceive(StockTransfer $record): bool
    {
        $user = auth()->user();
        if ($user->isSuperAdmin() || $user->hasRole('admin')) return true;

        return $user->canAccessLocation($record->to_location_id);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit'   => Pages\EditStockTransfer::route('/{record}/edit'),
            'view'   => Pages\ViewStockTransfer::route('/{record}'),
        ];
    }
}
