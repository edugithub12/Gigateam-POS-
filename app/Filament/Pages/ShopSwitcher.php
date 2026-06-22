<?php

namespace App\Filament\Pages;

use App\Models\Location;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ShopSwitcher extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Switch Shop';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int $navigationSort     = 0;
    protected static string $view             = 'filament.pages.shop-switcher';

    public ?int $selected_shop_id = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) return false;

        // Super admin can switch to any shop
        if ($user->isSuperAdmin()) return true;

        // Users with more than one shop assigned can switch
        return $user->activeLocations()->count() > 1;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->selected_shop_id = activeShopId();
    }

    public function getShopOptions(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $locations = Location::where('is_active', true)->orderBy('name')->get();
        } else {
            $locations = $user->activeLocations()->orderBy('name')->get();
        }

        $options = [];

        if ($user->isSuperAdmin()) {
            $options[0] = '— All Shops (Super Admin View) —';
        }

        foreach ($locations as $loc) {
            $options[$loc->id] = $loc->name . ' (' . ucfirst($loc->type) . ')';
        }

        return $options;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('switch')
                ->label('Switch to Selected Shop')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->form([
                    Select::make('shop_id')
                        ->label('Select Shop')
                        ->options($this->getShopOptions())
                        ->default(activeShopId())
                        ->required(false)
                        ->native(false)
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    $shopId = $data['shop_id'] ?: null;

                    // Validate the user can access this shop
                    if ($shopId && ! auth()->user()->canAccessLocation((int) $shopId)) {
                        Notification::make()
                            ->danger()
                            ->title('Access denied')
                            ->body('You are not assigned to that shop.')
                            ->send();
                        return;
                    }

                    session(['active_shop_id' => $shopId]);
                    app()->instance('active_shop', $shopId ? Location::find($shopId) : null);

                    $shopName = $shopId
                        ? Location::find($shopId)?->name
                        : 'All Shops';

                    Notification::make()
                        ->success()
                        ->title("Switched to {$shopName}")
                        ->send();

                    $this->redirect(route('filament.admin.pages.dashboard'));
                }),
        ];
    }
}
