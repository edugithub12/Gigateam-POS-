<?php

namespace App\Filament\Pages;

use App\Models\Location;
use App\Models\Product;
use Filament\Pages\Page;

class NetworkStock extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-magnifying-glass-circle';
    protected static ?string $navigationLabel = 'Network Stock';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort     = 5;
    protected static string $view             = 'filament.pages.network-stock';

    public string $search        = '';
    public ?int $filterShopId    = null;

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    // ── Data ──────────────────────────────────────────────────────────────────

    /**
     * All shops, used for the shop filter dropdown.
     */
    public function getShops(): \Illuminate\Support\Collection
    {
        return Location::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Full directory of every shop's independent catalog, grouped by shop.
     * Each shop's products are entirely their own — there is no shared
     * product row across shops. This is a read-only browse view; the
     * only action available is requesting a transfer.
     */
    public function getCatalogByShop(): array
    {
        $myShopId = activeShopId();

        $shopsQuery = Location::where('is_active', true)
            ->when($this->filterShopId, fn ($q) => $q->where('id', $this->filterShopId))
            ->orderBy('name');

        $shops = $shopsQuery->get();

        return $shops->map(function ($shop) use ($myShopId) {
            $products = Product::withoutShopScope()
                ->where('location_id', $shop->id)
                ->where('is_active', true)
                ->where('is_service', false)
                ->when($this->search, fn ($q) =>
                    $q->where(fn ($q2) =>
                        $q2->where('name', 'like', "%{$this->search}%")
                           ->orWhere('sku', 'like', "%{$this->search}%")
                    )
                )
                ->with('category')
                ->orderBy('name')
                ->get()
                ->map(fn ($p) => [
                    'id'         => $p->id,
                    'name'       => $p->name,
                    'sku'        => $p->sku,
                    'category'   => $p->category?->name ?? '—',
                    'quantity'   => $p->quantity,
                    'unit'       => $p->unit,
                    'status'     => $p->isOutOfStock() ? 'out' : ($p->isLowStock() ? 'low' : 'ok'),
                    'price'      => $p->selling_price,
                ]);

            return [
                'shop_id'        => $shop->id,
                'shop_name'      => $shop->name,
                'shop_type'      => $shop->type,
                'is_current_shop' => $shop->id === $myShopId,
                'product_count'  => $products->count(),
                'products'       => $products->values()->toArray(),
            ];
        })->toArray();
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * Redirect to the stock transfer create form, pre-filled with the
     * donor product and shop. The receiving shop's product is resolved
     * (matched or auto-created) only when the transfer is actually received,
     * per the agreed flow — this page just initiates the request.
     */
    public function requestTransferAction(int $donorProductId): void
    {
        $myShopId = activeShopId();

        if (! $myShopId) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('No active shop selected')
                ->body('Switch to a specific shop before requesting a transfer.')
                ->send();
            return;
        }

        $donorProduct = Product::withoutShopScope()->find($donorProductId);

        if (! $donorProduct || $donorProduct->quantity <= 0) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('No stock available for that product')
                ->send();
            return;
        }

        $this->redirect(route('filament.admin.resources.stock-transfers.create') . '?' . http_build_query([
            'donor_product_id' => $donorProductId,
            'from_location_id' => $donorProduct->location_id,
            'to_location_id'   => $myShopId,
        ]));
    }
}
