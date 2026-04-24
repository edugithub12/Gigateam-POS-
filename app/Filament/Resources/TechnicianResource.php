<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TechnicianResource\Pages;
use App\Models\Technician;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TechnicianResource extends Resource
{
    protected static ?string $model = Technician::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Field Operations';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Technician Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->required()
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
                        ->label('National ID')
                        ->maxLength(20),

                    Forms\Components\Select::make('specialization')
                        ->options(Technician::$specializations)
                        ->default('General'),

                    Forms\Components\Select::make('status')
                        ->options(Technician::$statuses)
                        ->default('active')
                        ->required(),

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

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('specialization')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'danger',
                        'on_leave' => 'warning',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('job_cards_count')
                    ->label('Total Jobs')
                    ->counts('jobCards')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Technician::$statuses),

                Tables\Filters\SelectFilter::make('specialization')
                    ->options(Technician::$specializations),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListTechnicians::route('/'),
            'create' => Pages\CreateTechnician::route('/create'),
            'edit'   => Pages\EditTechnician::route('/{record}/edit'),
        ];
    }
}