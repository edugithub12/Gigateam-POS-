<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Stock Report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.stock-report';

    public string $categoryFilter = '';
    public string $stockFilter = 'all'; // all, low, out

    public function getCurrentStock(): array
    {
        $query = Product::with('category')
            ->where('is_active', true)
            ->where('is_service', false);

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->stockFilter === 'low') {
            $query->whereRaw('stock_quantity <= low_stock_threshold AND stock_quantity > 0');
        } elseif ($this->stockFilter === 'out') {
            $query->where('stock_quantity', 0);
        }

        return $query->orderBy('stock_quantity')
            ->get()
            ->map(fn($p) => [
                'id'            => $p->id,
                'sku'           => $p->sku,
                'name'          => $p->name,
                'category'      => $p->category?->name ?? '—',
                'stock'         => $p->stock_quantity,
                'threshold'     => $p->low_stock_threshold,
                'unit'          => $p->unit,
                'selling_price' => $p->selling_price,
                'stock_value'   => $p->stock_quantity * $p->cost_price,
                'status'        => $p->stock_quantity == 0 ? 'out'
                    : ($p->stock_quantity <= $p->low_stock_threshold ? 'low' : 'ok'),
            ])
            ->toArray();
    }

    public function getStockSummary(): array
    {
        $products = Product::where('is_active', true)->where('is_service', false)->get();
        return [
            'total_products' => $products->count(),
            'out_of_stock'   => $products->where('stock_quantity', 0)->count(),
            'low_stock'      => $products->filter(fn($p) => $p->stock_quantity > 0 && $p->stock_quantity <= $p->low_stock_threshold)->count(),
            'total_value'    => $products->sum(fn($p) => $p->stock_quantity * $p->cost_price),
            'total_selling_value' => $products->sum(fn($p) => $p->stock_quantity * $p->selling_price),
        ];
    }

    public function getRecentMovements(): array
    {
        return StockMovement::with('product')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn($m) => [
                'date'     => $m->created_at->format('d M Y H:i'),
                'product'  => $m->product?->name ?? '—',
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
        return \App\Models\ProductCategory::orderBy('name')->pluck('name', 'id')->toArray();
    }
}