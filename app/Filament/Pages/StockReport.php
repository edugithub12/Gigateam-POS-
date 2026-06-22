<?php

namespace App\Filament\Pages;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class StockReport extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Stock Report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort     = 2;
    protected static string $view             = 'filament.pages.stock-report';

    public string $categoryFilter = '';
    public string $stockFilter    = 'all'; // all | low | out
    public string $locationFilter = '';    // '' = user's primary shop (or all for super admin)

    // ── Boot ─────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        // Default location filter to the user's primary shop
        $primaryId = auth()->user()?->primaryLocationId();
        if ($primaryId) {
            $this->locationFilter = (string) $primaryId;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * The location ID to scope queries to, or null for all locations.
     * Super admin with no filter selected sees all.
     */
    private function scopedLocationId(): ?int
    {
        if ($this->locationFilter !== '') {
            return (int) $this->locationFilter;
        }
        // Non-super-admin always scoped to their primary location
        if (! auth()->user()?->isSuperAdmin()) {
            return auth()->user()?->primaryLocationId();
        }
        return null; // super admin, no filter = all
    }

    // ── Data methods ──────────────────────────────────────────────────────────

    /**
     * Each shop now owns its own independent Product rows, with quantity
     * stored directly on the row — there's no shared catalog or separate
     * location_stocks table to join anymore. withoutShopScope() is used
     * throughout this report so super admins can see across all shops
     * regardless of which shop is "active" in their session.
     */
    public function getCurrentStock(): array
    {
        $locationId = $this->scopedLocationId();

        $query = Product::withoutShopScope()
            ->with(['category', 'location'])
            ->where('is_active', true)
            ->where('is_service', false);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->stockFilter === 'low') {
            $query->where('quantity', '>', 0)
                  ->whereColumn('quantity', '<=', 'reorder_point');
        } elseif ($this->stockFilter === 'out') {
            $query->where('quantity', '<=', 0);
        }

        return $query
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'sku'           => $p->sku,
                'name'          => $p->name,
                'category'      => $p->category?->name ?? '—',
                // Included so the "all shops" view can distinguish rows —
                // each row is now one shop's own product, not a pooled total.
                'location'      => $p->location?->name ?? '—',
                'stock'         => $p->quantity,
                'threshold'     => $p->reorder_point,
                'unit'          => $p->unit,
                'selling_price' => $p->selling_price,
                'stock_value'   => $p->quantity * $p->cost_price,
                'status'        => $p->quantity <= 0 ? 'out' : ($p->quantity <= $p->reorder_point ? 'low' : 'ok'),
            ])
            ->sortBy('stock')
            ->values()
            ->toArray();
    }

    public function getStockSummary(): array
    {
        $locationId = $this->scopedLocationId();

        $query = Product::withoutShopScope()
            ->where('is_active', true)
            ->where('is_service', false);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $products = $query->get();

        return [
            'total_products'      => $products->count(),
            'out_of_stock'        => $products->where('quantity', '<=', 0)->count(),
            'low_stock'           => $products->filter(fn ($p) => $p->quantity > 0 && $p->quantity <= $p->reorder_point)->count(),
            'total_value'         => $products->sum(fn ($p) => $p->quantity * $p->cost_price),
            'total_selling_value' => $products->sum(fn ($p) => $p->quantity * $p->selling_price),
        ];
    }

    public function getRecentMovements(): array
    {
        $locationId = $this->scopedLocationId();

        return StockMovement::with(['product', 'location'])
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($m) => [
                'date'     => $m->created_at->format('d M Y H:i'),
                'product'  => $m->product?->name ?? '—',
                'location' => $m->location?->name ?? '—',
                'type'     => $m->type,
                'source'   => $m->source,
                'quantity' => $m->quantity,
                'before'   => $m->stock_before,
                'after'    => $m->stock_after,
            ])
            ->toArray();
    }

    public function getCategories(): array
    {
        return ProductCategory::orderBy('name')->pluck('name', 'id')->toArray();
    }

    /**
     * Locations the current user can filter by.
     * Super admin sees all; others see only their assigned locations.
     */
    public function getLocations(): array
    {
        if (auth()->user()?->isSuperAdmin()) {
            return Location::where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        }

        return auth()->user()
            ->activeLocations()
            ->orderBy('name')
            ->pluck('name', 'locations.id')
            ->toArray();
    }
}