<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Location;
use App\Models\Technician;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Staff Accounts';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Personal Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('+254 7XX XXX XXX'),
                ])->columns(3),

            Forms\Components\Section::make('Password')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => !empty($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->minLength(8)
                        ->confirmed()
                        ->helperText('Leave blank to keep current password when editing.'),

                    Forms\Components\TextInput::make('password_confirmation')
                        ->password()
                        ->label('Confirm Password')
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(false),
                ])->columns(2),

            Forms\Components\Section::make('Role & Access')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Global Role')
                        ->options(Role::all()->pluck('name', 'name')->map(fn ($name) => ucfirst($name)))
                        ->required()
                        ->native(false)
                        ->relationship('roles', 'name')
                        ->preload()
                        ->live()
                        ->helperText('Super Admin / Admin: full access | Manager: approves transfers | Accountant: finance | Salesperson: sales & quotes | Technician: job cards only'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Account Active')
                        ->default(true)
                        ->helperText('Inactive accounts cannot log in.'),
                ])->columns(2),

            // ── Shop assignments via pivot ─────────────────────────────────────
            Forms\Components\Section::make('Shop Assignments')
                ->description('Assign this staff member to one or more shops. Set their role per shop and mark one as primary.')
                ->schema([
                    Forms\Components\Repeater::make('locationAssignments')
                        ->label('Assigned Shops')
                        ->schema([
                            Forms\Components\Select::make('location_id')
                                ->label('Shop')
                                ->options(Location::active()->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->native(false)
                                ->distinct(),

                            Forms\Components\Select::make('shop_role')
                                ->label('Role at this shop')
                                ->options([
                                    'shop_manager' => 'Shop Manager',
                                    'cashier'      => 'Cashier',
                                    'stock_clerk'  => 'Stock Clerk',
                                ])
                                ->required()
                                ->native(false)
                                ->default('cashier'),

                            Forms\Components\Toggle::make('is_primary')
                                ->label('Primary shop')
                                ->default(false)
                                ->helperText('The shop this user lands on at login.'),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Add shop')
                        ->dehydrated(false) // We handle saving manually in afterSave
                        ->visible(fn (Get $get) => $get('roles') !== 'super_admin'),
                ]),

            // Technician fields — only visible when Technician role is selected
            Forms\Components\Section::make('Technician Details')
                ->schema([
                    Forms\Components\Select::make('specialization')
                        ->options(Technician::$specializations)
                        ->default('General'),

                    Forms\Components\Select::make('technician_status')
                        ->label('Availability')
                        ->options(Technician::$statuses)
                        ->default('active'),

                    Forms\Components\TextInput::make('id_number')
                        ->label('National ID')
                        ->maxLength(20),

                    Forms\Components\Textarea::make('technician_notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(fn (Get $get): bool => $get('roles') === 'technician'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin'       => 'danger',
                        'manager'     => 'info',
                        'accountant'  => 'warning',
                        'salesperson' => 'success',
                        'technician'  => 'primary',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),

                // ── Fixed: pull primary location from pivot, not belongsTo ──
                Tables\Columns\TextColumn::make('primary_location')
                    ->label('Primary Shop')
                    ->badge()
                    ->color('success')
                    ->placeholder('— All Locations —')
                    ->getStateUsing(fn (User $record) =>
                        $record->activeLocations()
                               ->wherePivot('is_primary', true)
                               ->first()?->name
                        ?? $record->activeLocations()->first()?->name
                        ?? null
                    ),

                Tables\Columns\TextColumn::make('specialization')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Filter by Role'),

                // ── Fixed: filter by location via pivot, not relationship() ──
                Tables\Filters\SelectFilter::make('location')
                    ->label('Filter by Shop')
                    ->options(Location::active()->orderBy('name')->pluck('name', 'id'))
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('activeLocations', fn ($q) =>
                                $q->where('locations.id', $data['value'])
                            );
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->confirmed(),
                        Forms\Components\TextInput::make('new_password_confirmation')
                            ->password()
                            ->label('Confirm New Password')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['password' => Hash::make($data['new_password'])]);
                        \Filament\Notifications\Notification::make()
                            ->title('Password reset successfully')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (User $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (User $record) => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['is_active' => !$record->is_active])),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }
}
