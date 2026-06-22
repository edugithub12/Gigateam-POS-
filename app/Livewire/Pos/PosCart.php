<?php

namespace App\Livewire\Pos;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PosCart extends Component
{
    public string $search = '';
    public array $cart = [];
    public ?int $selectedIndex = null;

    public float $globalDiscount = 0;
    public bool $applyVat = false;
    public string $notes = '';

    public bool $showPaymentPanel = false;
    public ?string $activeMethod = null;
    public ?string $amountTendered = null;
    public ?string $paymentReference = null;

    public bool $showReceipt = false;
    public ?array $completedSale = null;
    public ?int $currentSaleId = null;

    // ── Sale reference preview ────────────────────────────────────────────────

    public function nextSaleRef(): string
    {
        $last = DB::table('document_sequences')
            ->where('type', 'sale')->value('last_number') ?? 0;
        return 'SAL-' . now()->format('Ym') . '-' . str_pad(($last + 1), 4, '0', STR_PAD_LEFT);
    }

    // ── Location helpers ──────────────────────────────────────────────────────

    private function locationId(): ?int
    {
        return Auth::user()->location_id;
    }

    /**
     * Stock is tracked directly on each shop's own Product row (Product is
     * shop-scoped via BelongsToShop's global scope), so a product fetched
     * through the normal scoped queries already belongs to the active
     * location — its `quantity` column IS the stock available here.
     */
    private function stockAvailable(Product $product): int
    {
        if ($product->is_service) return PHP_INT_MAX;

        return $product->quantity;
    }

    // ── Cart row selection ────────────────────────────────────────────────────

    public function selectRow(int $index): void
    {
        $this->selectedIndex = $index;
    }

    // ── Barcode / search ──────────────────────────────────────────────────────

    /**
     * Handles Enter on the search/barcode field:
     * exact SKU/barcode match → add directly.
     * Otherwise, if only one search result matches, add it.
     */
    public function addBySearchOrBarcode(): void
    {
        $term = trim($this->search);
        if ($term === '') return;

        // Try exact SKU or barcode match first (scanner case)
        $product = Product::active()
            ->where(function ($q) use ($term) {
                $q->where('sku', $term)->orWhere('barcode', $term);
            })
            ->first();

        if ($product) {
            $this->addToCart($product->id);
            $this->search = '';
            return;
        }

        // Fallback: if search text matches exactly one product, add it
        $matches = Product::active()->search($term)->limit(2)->get();
        if ($matches->count() === 1) {
            $this->addToCart($matches->first()->id);
            $this->search = '';
        }
        // else: leave search results for user to pick from the dropdown
    }

    // ── Cart operations ───────────────────────────────────────────────────────

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) return;

        $available = $this->stockAvailable($product);

        if (!$product->is_service && $available <= 0) {
            $this->dispatch('notify', type: 'error', message: "Out of stock: {$product->name}");
            return;
        }

        $key = $this->cartKey($productId);
        if ($key !== null) {
            $newQty = $this->cart[$key]['quantity'] + 1;
            if (!$product->is_service && $newQty > $available) {
                $this->dispatch('notify', type: 'warning', message: "Only {$available} in stock at your location");
                return;
            }
            $this->cart[$key]['quantity'] = $newQty;
            $this->recalcLine($key);
            $this->selectedIndex = $key;
        } else {
            $this->cart[] = [
                'product_id'      => $product->id,
                'name'            => $product->name,
                'sku'             => $product->sku,
                'unit'            => $product->unit,
                'unit_price'      => (float) $product->selling_price,
                'cost_price'      => (float) $product->cost_price,
                'quantity'        => 1,
                'discount'        => 0,
                'total'           => (float) $product->selling_price,
                'stock_available' => $available,
                'is_service'      => $product->is_service,
            ];
            $this->selectedIndex = array_key_last($this->cart);
        }
        $this->search = '';
    }

    public function removeFromCart(int $index): void
    {
        if ($index < 0 || !array_key_exists($index, $this->cart)) {
            return;
        }

        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->selectedIndex = null;
    }

    public function updateQty(int $index, $qty): void
    {
        $qty = (int) $qty;
        if ($qty <= 0) {
            $this->removeFromCart($index);
            return;
        }
        $item = $this->cart[$index];
        if (!$item['is_service'] && $qty > $item['stock_available']) {
            $qty = $item['stock_available'];
        }
        $this->cart[$index]['quantity'] = $qty;
        $this->recalcLine($index);
    }

    public function updatePrice(int $index, $price): void
    {
        $this->cart[$index]['unit_price'] = max(0, (float) $price);
        $this->recalcLine($index);
    }

    public function clearCart(): void
    {
        $this->cart           = [];
        $this->selectedIndex  = null;
        $this->globalDiscount = 0;
        $this->applyVat       = false;
        $this->notes          = '';
        $this->currentSaleId  = null;
        $this->showPaymentPanel = false;
        $this->activeMethod     = null;
        $this->amountTendered   = null;
        $this->paymentReference = null;
    }

    // ── Payment ───────────────────────────────────────────────────────────────

    /**
     * Select (or toggle off) a payment method tile.
     * Pre-fills the amount with the exact total.
     */
    public function selectPaymentMethod(string $code): void
    {
        if ($this->activeMethod === $code) {
            $this->cancelPayment();
            return;
        }

        $this->activeMethod     = $code;
        $this->showPaymentPanel = true;
        $this->amountTendered   = number_format($this->calcGrandTotal(), 2, '.', '');
        $this->paymentReference = null;
    }

    public function cancelPayment(): void
    {
        $this->showPaymentPanel = false;
        $this->activeMethod     = null;
        $this->amountTendered   = null;
        $this->paymentReference = null;
    }

    // ── Complete sale ─────────────────────────────────────────────────────────

    public function completeSale(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Cart is empty.');
            return;
        }

        if (!$this->activeMethod) {
            $this->dispatch('notify', type: 'error', message: 'Select a payment method.');
            return;
        }

        $paymentMethod = PaymentMethod::where('code', $this->activeMethod)->first();

        if ($paymentMethod?->requires_reference && !$this->paymentReference) {
            $this->dispatch('notify', type: 'error', message: 'Enter a reference/code for this payment method.');
            return;
        }

        $grandTotal = $this->calcGrandTotal();
        $tendered   = (float) ($this->amountTendered ?: 0);

        if ($tendered < $grandTotal) {
            $this->dispatch('notify', type: 'error', message: 'Amount paid is less than total.');
            return;
        }

        $locationId = $this->locationId();
        if (!$locationId) {
            $this->dispatch('notify', type: 'error', message: 'Your account has no location assigned. Contact admin.');
            return;
        }

        try {
            DB::transaction(function () use ($grandTotal, $tendered, $locationId, $paymentMethod) {
                $subtotal  = collect($this->cart)->sum('total');
                $discount  = round($this->globalDiscount, 2);
                $taxable   = max(0, $subtotal - $discount);
                $vat       = $this->applyVat ? round($taxable * 0.16, 2) : 0;
                $changeDue = round($tendered - $grandTotal, 2);

                // ── Create sale (always cash/instant walk-in, fully paid) ──────
                $sale = Sale::create([
                    'customer_id'     => null,
                    'user_id'         => Auth::id(),
                    'location_id'     => $locationId,
                    'subtotal'        => $subtotal,
                    'discount_amount' => $discount,
                    'vat_amount'      => $vat,
                    'total'           => $grandTotal,
                    'amount_paid'     => $grandTotal,
                    'change_given'    => $changeDue,
                    'payment_status'  => 'paid',
                    'sale_type'       => 'walk_in',
                    'include_vat'     => $this->applyVat,
                    'notes'           => $this->notes,
                ]);

                $this->currentSaleId = $sale->id;

                // ── Sale items + stock deduction ──────────────────────────────
                foreach ($this->cart as $item) {
                    SaleItem::create([
                        'sale_id'      => $sale->id,
                        'product_id'   => $item['product_id'],
                        'product_name' => $item['name'],
                        'product_sku'  => $item['sku'],
                        'unit'         => $item['unit'],
                        'unit_price'   => $item['unit_price'],
                        'cost_price'   => $item['cost_price'],
                        'quantity'     => $item['quantity'],
                        'discount'     => $item['discount'],
                        'total'        => $item['total'],
                    ]);

                    if (!$item['is_service']) {
                        // Stock lives directly on the product row (each shop
                        // owns its own Product rows), not in a separate
                        // location_stocks table — lock and decrement it
                        // in place, same pattern as StockTransfer::moveStock().
                        $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                        $before = $product->quantity;
                        $after  = $before - $item['quantity'];

                        $product->update(['quantity' => $after]);

                        StockMovement::create([
                            'product_id'   => $item['product_id'],
                            'location_id'  => $locationId,
                            'type'         => 'sale',
                            'source'       => 'sale',
                            'source_id'    => $sale->id,
                            'quantity'     => -$item['quantity'],
                            'stock_before' => $before,
                            'stock_after'  => $after,
                            'notes'        => "Sold via POS — {$sale->sale_number}",
                            'user_id'      => Auth::id(),
                        ]);
                    }
                }

                // ── Payment record ──────────────────────────────────────────────
                Payment::create([
                    'sale_id'   => $sale->id,
                    'amount'    => $grandTotal,
                    'method'    => $this->activeMethod,
                    'reference' => $this->paymentReference ?: null,
                    'user_id'   => Auth::id(),
                ]);

                // ── Auto-generate invoice ─────────────────────────────────────
                $applyVat = $this->applyVat;
                $notes    = $this->notes;

                $invoice = Invoice::withoutEvents(function () use (
                    $sale, $subtotal, $discount, $vat,
                    $grandTotal, $applyVat, $notes, $locationId
                ) {
                    $inv = new Invoice([
                        'customer_id'     => null,
                        'sale_id'         => $sale->id,
                        'created_by'      => Auth::id(),
                        'location_id'     => $locationId,
                        'client_name'     => 'Walk-in Customer',
                        'client_phone'    => null,
                        'status'          => 'paid',
                        'include_vat'     => $applyVat,
                        'subtotal'        => $subtotal,
                        'discount_amount' => $discount,
                        'vat_amount'      => $vat,
                        'total'           => $grandTotal,
                        'amount_paid'     => $grandTotal,
                        'notes'           => $notes,
                        'footer_text'     => 'Accounts are due on demand.',
                    ]);
                    $inv->invoice_number = Invoice::generateNumber();
                    $inv->save();
                    return $inv;
                });

                foreach ($this->cart as $i => $item) {
                    InvoiceItem::create([
                        'invoice_id'  => $invoice->id,
                        'product_id'  => $item['product_id'],
                        'sort_order'  => $i,
                        'description' => $item['name'],
                        'unit'        => $item['unit'],
                        'unit_price'  => $item['unit_price'],
                        'cost_price'  => $item['cost_price'],
                        'quantity'    => $item['quantity'],
                        'discount'    => $item['discount'],
                        'total'       => $item['total'],
                    ]);
                }

                // ── Build completed sale data for receipt ─────────────────────
                $this->completedSale = [
                    'sale_number'    => $sale->sale_number,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_id'     => $invoice->id,
                    'customer'       => 'Walk-in Customer',
                    'items'          => $this->cart,
                    'subtotal'       => $subtotal,
                    'discount'       => $discount,
                    'vat'            => $vat,
                    'total'          => $grandTotal,
                    'tendered'       => $tendered,
                    'change'         => $changeDue,
                    'payment_method' => $paymentMethod?->name ?? ucfirst(str_replace('_', ' ', $this->activeMethod)),
                    'reference'      => $this->paymentReference,
                    'cashier'        => Auth::user()->name,
                    'date'           => now()->format('d/m/Y H:i'),
                ];
            });

            $this->showReceipt = true;
            $this->dispatch('notify', type: 'success', message: "Sale {$this->completedSale['sale_number']} complete!");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('POS Sale Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function newSale(): void
    {
        $this->showReceipt   = false;
        $this->completedSale = null;
        $this->clearCart();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function calcGrandTotal(): float
    {
        $subtotal = collect($this->cart)->sum('total');
        $taxable  = max(0, $subtotal - round($this->globalDiscount, 2));
        $vat      = $this->applyVat ? round($taxable * 0.16, 2) : 0;
        return round($taxable + $vat, 2);
    }

    private function cartKey(int $productId): ?int
    {
        foreach ($this->cart as $key => $item) {
            if ($item['product_id'] === $productId) return $key;
        }
        return null;
    }

    private function recalcLine(int $key): void
    {
        $item = $this->cart[$key];
        $this->cart[$key]['total'] = round(
            ($item['unit_price'] * $item['quantity']) - $item['discount'],
            2
        );
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $subtotal   = collect($this->cart)->sum('total');
        $discount   = round($this->globalDiscount, 2);
        $taxable    = max(0, $subtotal - $discount);
        $vat        = $this->applyVat ? round($taxable * 0.16, 2) : 0;
        $grandTotal = round($taxable + $vat, 2);
        $tendered   = (float) ($this->amountTendered ?: 0);
        $change     = max(0, round($tendered - $grandTotal, 2));
        $cartCount  = collect($this->cart)->sum('quantity');

        $paymentMethods = PaymentMethod::active()->get();

        // Search results for the dropdown (only when typing).
        // Product is already shop-scoped (BelongsToShop global scope), so
        // every result here already belongs to the active location — its
        // `quantity` column IS the current stock, no join needed.
        $searchResults = collect();
        if (strlen($this->search) >= 2) {
            $searchResults = Product::active()
                ->search($this->search)
                ->limit(8)
                ->get()
                ->map(function ($product) {
                    $product->current_stock = $product->is_service ? null : $product->quantity;
                    return $product;
                });
        }

        return view('livewire.pos.cart', compact(
            'subtotal', 'discount', 'taxable', 'vat', 'grandTotal',
            'tendered', 'change', 'cartCount', 'searchResults', 'paymentMethods'
        ))->layout('layouts.pos');
    }
}