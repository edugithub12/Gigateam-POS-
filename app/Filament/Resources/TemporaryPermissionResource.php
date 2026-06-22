<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemporaryPermissionResource\Pages;
use App\Models\TemporaryPermission;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TemporaryPermissionResource extends Resource
{
    protected static ?string $model            = TemporaryPermission::class;
    protected static ?string $navigationIcon   = 'heroicon-o-key';
    protected static ?string $navigationLabel  = 'Temp Permissions';
    protected static ?string $navigationGroup  = 'Administration';
    protected static ?int    $navigationSort   = 3;
    protected static ?string $modelLabel       = 'Temporary Permission';
    protected static ?string $pluralModelLabel = 'Temporary Permissions';

    // ── Access control – admin only ──────────────────────────────────────────
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    // ── All available permissions grouped by module ──────────────────────────
    public static function availablePermissions(): array
    {
        return [
            'POS & Sales' => [
                'access pos'     => 'Access POS Screen',
                'create sales'   => 'Create Sales',
                'void sales'     => 'Void / Cancel Sales',
                'apply discount' => 'Apply Discounts',
            ],
            'Invoices' => [
                'view invoices'   => 'View Invoices',
                'create invoices' => 'Create Invoices',
                'edit invoices'   => 'Edit Invoices',
                'delete invoices' => 'Delete Invoices',
                'record payments' => 'Record Payments',
            ],
            'Quotations' => [
                'view quotations'   => 'View Quotations',
                'create quotations' => 'Create Quotations',
                'edit quotations'   => 'Edit Quotations',
                'delete quotations' => 'Delete Quotations',
            ],
            'Job Cards' => [
                'view job cards'    => 'View Job Cards',
                'create job cards'  => 'Create Job Cards',
                'edit job cards'    => 'Edit Job Cards',
                'delete job cards'  => 'Delete Job Cards',
                'complete job cards'=> 'Mark Jobs as Completed',
            ],
            'Delivery Notes' => [
                'view delivery notes'   => 'View Delivery Notes',
                'create delivery notes' => 'Create Delivery Notes',
                'edit delivery notes'   => 'Edit Delivery Notes',
            ],
            'Inventory' => [
                'view products'    => 'View Products',
                'create products'  => 'Create Products',
                'edit products'    => 'Edit Products',
                'delete products'  => 'Delete Products',
                'adjust stock'     => 'Adjust Stock Levels',
                'view suppliers'   => 'View Suppliers',
                'manage suppliers' => 'Manage Suppliers',
            ],
            'Customers' => [
                'view customers'   => 'View Customers',
                'create customers' => 'Create Customers',
                'edit customers'   => 'Edit Customers',
                'delete customers' => 'Delete Customers',
            ],
            'Reports' => [
                'view reports'      => 'View Reports',
                'export reports'    => 'Export PDF Reports',
                'view vat report'   => 'View VAT Report',
                'view stock report' => 'View Stock Report',
            ],
            'Administration' => [
                'manage staff'   => 'Manage Staff Accounts',
                'manage roles'   => 'Manage Roles & Permissions',
                'view audit log' => 'View Audit Log',
            ],
        ];
    }

    /**
     * Get permission keys the user already has via their permanent role,
     * plus any active temporary permissions they already hold.
     */
    public static function getUserExistingPermissions(int $userId): array
    {
        $user = User::with('roles.permissions', 'permissions')->find($userId);
        if (!$user) return [];

        // Permissions from their role
        $rolePerms = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        // Direct permissions on the user
        $directPerms = $user->permissions->pluck('name')->toArray();

        // Active temporary permissions they already have
        $tempPerms = TemporaryPermission::query()
            ->active()
            ->where('user_id', $userId)
            ->pluck('permission')
            ->toArray();

        return array_unique(array_merge($rolePerms, $directPerms, $tempPerms));
    }

    // ── Form ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        $allPermissions = collect(static::availablePermissions())
            ->flatMap(fn($perms, $group) => collect($perms)->mapWithKeys(
                fn($label, $key) => [$key => "[{$group}] {$label}"]
            ))
            ->toArray();

        return $form->schema([

            Forms\Components\Section::make('Grant Details')
                ->description('Select the staff member first — the list will show only permissions they do not already have.')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Staff Member')
                        ->options(
                            User::where('is_active', true)
                                ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn($u) => [$u->id => $u->name . ' (' . $u->email . ')'])
                        )
                        ->searchable()
                        ->required()
                        ->live()                    // re-renders permissions when user changes
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('permissions', []))
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('role_info')
                        ->label('Current Role & Permissions')
                        ->content(function (Get $get) {
                            $userId = $get('user_id');
                            if (!$userId) return 'Select a staff member to see their current role and permissions.';

                            $user = User::with('roles')->find($userId);
                            if (!$user) return '—';

                            $roles = $user->roles->pluck('name')->join(', ');
                            $existing = static::getUserExistingPermissions((int) $userId);
                            $count = count($existing);

                            return "Role: {$roles} | Already has {$count} permission(s). The checklist below shows only what they are missing.";
                        })
                        ->columnSpanFull(),

                    Forms\Components\CheckboxList::make('permissions')
                        ->label('Permissions to Grant')
                        ->options(function (Get $get) use ($allPermissions) {
                            $userId = $get('user_id');

                            // No user selected — show all
                            if (!$userId) return $allPermissions;

                            // Filter out what the user already has
                            $existing = static::getUserExistingPermissions((int) $userId);

                            return collect($allPermissions)
                                ->filter(fn($label, $key) => !in_array($key, $existing))
                                ->toArray();
                        })
                        ->searchable()
                        ->columns(2)
                        ->gridDirection('row')
                        ->required()
                        ->minItems(1)
                        ->helperText('Only permissions the selected user does not already have are shown.')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('reason')
                        ->label('Reason for Grant')
                        ->placeholder('e.g. Covering for sick colleague, stocktake access, temporary project...')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Validity Period')
                ->description('Set when these permissions expire. Toggle on for permanent access.')
                ->schema([
                    Forms\Components\Toggle::make('is_permanent')
                        ->label('Permanent Grant (no expiry)')
                        ->default(false)
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expires At')
                        ->native(false)
                        ->minDate(now()->addMinutes(5))
                        ->displayFormat('d M Y H:i')
                        ->placeholder('Select expiry date and time')
                        ->hidden(fn (Get $get) => $get('is_permanent'))
                        ->required(fn (Get $get) => !$get('is_permanent'))
                        ->helperText('All selected permissions will automatically expire at this date and time.')
                        ->columnSpanFull(),

                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('set_1h')
                            ->label('+ 1 Hour')
                            ->color('gray')
                            ->size('sm')
                            ->action(function (Forms\Set $set) {
                                $set('expires_at', now()->addHour()->format('Y-m-d H:i:s'));
                                $set('is_permanent', false);
                            }),
                        Forms\Components\Actions\Action::make('set_today')
                            ->label('End of Today')
                            ->color('gray')
                            ->size('sm')
                            ->action(function (Forms\Set $set) {
                                $set('expires_at', now()->endOfDay()->format('Y-m-d H:i:s'));
                                $set('is_permanent', false);
                            }),
                        Forms\Components\Actions\Action::make('set_1w')
                            ->label('+ 1 Week')
                            ->color('gray')
                            ->size('sm')
                            ->action(function (Forms\Set $set) {
                                $set('expires_at', now()->addWeek()->format('Y-m-d H:i:s'));
                                $set('is_permanent', false);
                            }),
                        Forms\Components\Actions\Action::make('set_1m')
                            ->label('+ 1 Month')
                            ->color('gray')
                            ->size('sm')
                            ->action(function (Forms\Set $set) {
                                $set('expires_at', now()->addMonth()->format('Y-m-d H:i:s'));
                                $set('is_permanent', false);
                            }),
                    ])->hidden(fn (Get $get) => $get('is_permanent')),
                ]),

            Forms\Components\Hidden::make('granted_by')
                ->default(fn () => auth()->id()),

            Forms\Components\Hidden::make('granted_at')
                ->default(fn () => now()->toDateTimeString()),

            Forms\Components\Hidden::make('status')
                ->default('active'),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Staff Member')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('permission')
                    ->label('Permission')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(function ($state) {
                        foreach (static::availablePermissions() as $group => $perms) {
                            if (isset($perms[$state])) {
                                return "[{$group}] {$perms[$state]}";
                            }
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('grantedBy.name')
                    ->label('Granted By')
                    ->sortable(),

                Tables\Columns\TextColumn::make('granted_at')
                    ->label('Granted')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Never (Permanent)')
                    ->color(fn ($record) => match(true) {
                        $record->status === 'revoked'           => 'danger',
                        $record->expires_at === null            => 'success',
                        $record->expires_at->isPast()           => 'gray',
                        $record->expires_at->diffInHours() < 2  => 'warning',
                        default                                 => 'success',
                    }),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status_badge_color),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->reason)
                    ->placeholder('No reason given'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'  => 'Active',
                        'expired' => 'Expired',
                        'revoked' => 'Revoked',
                    ]),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Staff Member')
                    ->options(User::orderBy('name')->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Revoke Permission')
                    ->modalDescription('This will immediately remove access. The staff member will lose this permission right away.')
                    ->form([
                        Forms\Components\Textarea::make('revocation_reason')
                            ->label('Reason for Revocation')
                            ->placeholder('Optional – why is this being revoked?')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->revoke(auth()->id(), $data['revocation_reason'] ?? '');
                        Notification::make()
                            ->title('Permission Revoked')
                            ->body("{$record->user->name}'s access to \"{$record->permission}\" has been removed.")
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('revoke_selected')
                    ->label('Revoke Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->is_active) {
                                $record->revoke(auth()->id(), 'Bulk revocation by admin');
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title("{$count} permission(s) revoked")
                            ->warning()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No temporary permissions granted')
            ->emptyStateDescription('Use the "Grant Permission" button to give a staff member temporary access to a feature.')
            ->emptyStateIcon('heroicon-o-key');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTemporaryPermissions::route('/'),
            'create' => Pages\CreateTemporaryPermission::route('/create'),
            'view'   => Pages\ViewTemporaryPermission::route('/{record}'),
        ];
    }
}