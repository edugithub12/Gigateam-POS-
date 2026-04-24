<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobCardResource\Pages;
use App\Models\JobCard;
use App\Models\Customer;
use App\Models\Technician;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class JobCardResource extends Resource
{
    protected static ?string $model = JobCard::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Field Operations';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'job_number';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'technician']) ?? false;
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
            Forms\Components\Section::make('Job Details')
                ->schema([
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
                                    $set('site_address', $customer->address);
                                    $set('site_area', $customer->area);
                                }
                            }
                        }),

                    Forms\Components\Select::make('technician_id')
                        ->label('Assigned Technician')
                        ->relationship('technician', 'name')
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('client_name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('client_phone')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\Select::make('job_type')
                        ->options(JobCard::$jobTypes)
                        ->required(),

                    Forms\Components\Select::make('category')
                        ->options(JobCard::$categories)
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options(JobCard::$statuses)
                        ->default('pending')
                        ->required(),

                    Forms\Components\DatePicker::make('scheduled_date')
                        ->label('Scheduled Date'),

                    Forms\Components\TimePicker::make('scheduled_time')
                        ->label('Scheduled Time'),

                    Forms\Components\Textarea::make('site_address')
                        ->label('Site Address')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('site_area')
                        ->label('Area / Estate')
                        ->maxLength(100),
                ])->columns(2),

            Forms\Components\Section::make('Work Details')
                ->schema([
                    Forms\Components\Textarea::make('work_description')
                        ->label('Work to be Done')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('work_done')
                        ->label('Work Done (filled by technician)')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('technician_notes')
                        ->label('Technician Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Materials Used')
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
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('unit_price')
                                ->label('Unit Price')
                                ->numeric()
                                ->prefix('KES')
                                ->columnSpan(2),

                            Forms\Components\Select::make('source')
                                ->options(['shop' => 'From Shop', 'site' => 'On Site'])
                                ->default('shop')
                                ->columnSpan(2),
                        ])
                        ->columns(12)
                        ->addActionLabel('+ Add Material')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Costs')
                ->schema([
                    Forms\Components\TextInput::make('labour_cost')
                        ->label('Labour Cost (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->default(0),

                    Forms\Components\TextInput::make('transport_cost')
                        ->label('Transport Cost (KES)')
                        ->numeric()
                        ->prefix('KES')
                        ->default(0),

                    Forms\Components\Hidden::make('created_by')
                        ->default(fn () => Auth::id()),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('job_number')
                    ->label('Job #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('technician.name')
                    ->label('Technician')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('job_type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => JobCard::$jobTypes[$state] ?? $state),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => JobCard::$statusColors[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => JobCard::$statuses[$state] ?? $state),

                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Scheduled')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('site_area')
                    ->label('Area')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(JobCard::$statuses),

                Tables\Filters\SelectFilter::make('technician_id')
                    ->label('Technician')
                    ->relationship('technician', 'name'),

                Tables\Filters\SelectFilter::make('category')
                    ->options(JobCard::$categories),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (JobCard $record) => route('job-cards.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('complete')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (JobCard $record) => $record->status === 'in_progress')
                    ->action(fn (JobCard $record) => $record->update([
                        'status'       => 'completed',
                        'completed_at' => now(),
                    ])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getNavigationBadge(): ?string
    {
        $active = JobCard::whereIn('status', ['scheduled', 'in_progress'])->count();
        return $active > 0 ? (string) $active : null;
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
            'index'  => Pages\ListJobCards::route('/'),
            'create' => Pages\CreateJobCard::route('/create'),
            'edit'   => Pages\EditJobCard::route('/{record}/edit'),
        ];
    }
}