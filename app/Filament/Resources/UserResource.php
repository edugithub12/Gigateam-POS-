<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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
                        ->label('Role')
                        ->options(Role::all()->pluck('name', 'name')->map(fn ($name) => ucfirst($name)))
                        ->required()
                        ->native(false)
                        ->relationship('roles', 'name')
                        ->preload()
                        ->helperText('Admin: full access | Accountant: finance | Salesperson: sales & quotes | Technician: job cards only'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Account Active')
                        ->default(true)
                        ->helperText('Inactive accounts cannot log in.'),
                ])->columns(2),
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
                        'admin'       => 'danger',
                        'accountant'  => 'warning',
                        'salesperson' => 'success',
                        'technician'  => 'info',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Filter by Role'),
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
        return auth()->user()?->hasRole('admin') ?? false;
    }
}