<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

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
            Forms\Components\Section::make('Customer Details')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options(Customer::$types)
                        ->default('individual')
                        ->required()
                        ->live(),

                    Forms\Components\TextInput::make('name')
                        ->label('Full Name / Contact Person')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('company_name')
                        ->label('Company / Business Name')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get) => $get('type') !== 'individual'),

                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('phone_alt')
                        ->label('Alternative Phone')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('id_number')
                        ->label('ID / KRA PIN')
                        ->maxLength(50),
                ])->columns(2),

            Forms\Components\Section::make('Location')
                ->schema([
                    Forms\Components\Textarea::make('address')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('area')
                        ->label('Area / Estate')
                        ->placeholder('e.g. Westlands, Karen, CBD')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('city')
                        ->default('Nairobi')
                        ->maxLength(100),
                ])->columns(2),

            Forms\Components\Section::make('Account')
                ->schema([
                    Forms\Components\TextInput::make('credit_limit')
                        ->label('Credit Limit (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->default(0),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'government' => 'success',
                        'business'   => 'info',
                        'school'     => 'warning',
                        'estate'     => 'primary',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('area')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('outstanding_balance')
                    ->label('Balance (KES)')
                    ->money('KES')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(Customer::$types),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('admin')),
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
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}