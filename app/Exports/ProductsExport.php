<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct(
        // Pass a location_id to export one shop's stock; null = all shops
        // (each row is that shop's own product — no more pooled totals,
        // since each shop now owns an independent catalog).
        private ?int $locationId = null
    ) {}

    public function title(): string
    {
        return 'Products';
    }

    public function query()
    {
        $query = Product::withoutShopScope()->with(['category', 'location']);

        if ($this->locationId) {
            $query->where('location_id', $this->locationId);
        }

        return $query->orderBy('name');
    }

    public function headings(): array
    {
        $headings = [
            'SKU',
            'Product Name',
            'Category',
        ];

        // Only relevant for the all-shops export, since each row is now a
        // distinct shop's own product rather than a pooled total.
        if (! $this->locationId) {
            $headings[] = 'Shop';
        }

        return [
            ...$headings,
            'Brand',
            'Model No.',
            'Unit',
            'Cost Price (KES)',
            'Selling Price (KES)',
            'Installation Price (KES)',
            'Stock Qty',
            'Reorder Point',
            'Type',
            'Status',
            'Description',
        ];
    }

    public function map($product): array
    {
        if ($product->is_service) {
            $stock        = 'N/A';
            $reorderPoint = 'N/A';
        } else {
            $stock        = $product->quantity;
            $reorderPoint = $product->reorder_point;
        }

        $row = [
            $product->sku,
            $product->name,
            $product->category?->name ?? '—',
        ];

        if (! $this->locationId) {
            $row[] = $product->location?->name ?? '—';
        }

        return [
            ...$row,
            $product->brand ?? '—',
            $product->model_number ?? '—',
            $product->unit,
            number_format($product->cost_price, 2),
            number_format($product->selling_price, 2),
            number_format($product->installation_price ?? 0, 2),
            $stock,
            $reorderPoint,
            $product->is_service ? 'Service' : 'Physical',
            $product->is_active ? 'Active' : 'Inactive',
            $product->description ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size'  => 11,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1A1A1A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 15, // SKU
            'B' => 35, // Product Name
            'C' => 20, // Category
        ];

        $col = 'D';
        if (! $this->locationId) {
            $widths[$col] = 18; // Shop
            $col++;
        }

        // Remaining columns: Brand, Model No., Unit, Cost, Selling,
        // Installation, Stock, Reorder, Type, Status, Description
        $remaining = [18, 18, 10, 18, 20, 22, 12, 14, 12, 10, 40];
        foreach ($remaining as $w) {
            $widths[$col] = $w;
            $col++;
        }

        return $widths;
    }
}