<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model           = Activity::class;
    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Audit Log';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int    $navigationSort  = 4;
    protected static ?string $modelLabel      = 'Activity';
    protected static ?string $pluralModelLabel= 'Audit Log';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'accountant']) ?? false;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->latest())
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->width('160px'),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Staff Member')
                    ->sortable()
                    ->default('System')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('log_name')
                    ->label('Module')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'sales'          => 'success',
                        'invoices'       => 'info',
                        'quotations'     => 'warning',
                        'job_cards'      => 'danger',
                        'delivery_notes' => 'gray',
                        'products'       => 'info',
                        'customers'      => 'success',
                        'payments'       => 'success',
                        'users'          => 'warning',
                        'suppliers'      => 'gray',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'sales'          => 'Sales',
                        'invoices'       => 'Invoices',
                        'quotations'     => 'Quotations',
                        'job_cards'      => 'Job Cards',
                        'delivery_notes' => 'Delivery Notes',
                        'products'       => 'Products',
                        'customers'      => 'Customers',
                        'payments'       => 'Payments',
                        'users'          => 'Staff',
                        'suppliers'      => 'Suppliers',
                        'stock'          => 'Stock',
                        default          => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'voided'  => 'danger',
                        'paid'    => 'success',
                        'sent'    => 'info',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Record')
                    ->formatStateUsing(function ($state, $record) {
                        $type = class_basename($state ?? '');
                        $id   = $record->subject_id;
                        return $type ? "{$type} #{$id}" : '—';
                    })
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Module')
                    ->options([
                        'sales'          => 'Sales',
                        'invoices'       => 'Invoices',
                        'quotations'     => 'Quotations',
                        'job_cards'      => 'Job Cards',
                        'delivery_notes' => 'Delivery Notes',
                        'products'       => 'Products',
                        'customers'      => 'Customers',
                        'payments'       => 'Payments',
                        'users'          => 'Staff',
                        'suppliers'      => 'Suppliers',
                        'stock'          => 'Stock',
                    ]),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'voided'  => 'Voided',
                        'paid'    => 'Paid',
                    ]),

                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('Staff Member')
                    ->options(User::orderBy('name')->pluck('name', 'id')),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'],  fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_changes')
                    ->label('Changes')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Changes — ' . ucfirst($record->event) . ' ' . class_basename($record->subject_type ?? ''))
                    ->modalContent(fn ($record) => view('filament.audit-changes', ['activity' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No activity recorded yet')
            ->emptyStateDescription('All actions taken in the system will appear here.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}