<?php

namespace App\Livewire\Pos;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PosCart extends Component
{
    public string $search = '';
    public ?int $categoryId = null;
    public array $cart = [];
    public string $customerSearch = '';
    public ?int $customerId = null;
    public ?string $customerName = null;
    public ?string $customerPhone = null;
    public float $globalDiscount = 0;
    public bool $applyVat = false;
    public string $notes = '';
    public bool $showPaymentModal = false;
    public bool $showReceipt = false;
    public ?array $completedSale = null;
    public ?int $currentSaleId = null;

    public array $payments = [
        ['method' => 'cash',          'amount' => '', 'reference' => ''],
        ['method' => 'mpesa',         'amount' => '', 'reference' => ''],
        ['method' => 'bank_transfer', 'amount' => '', 'reference' => ''],
        ['method' => 'cheque',        'amount' => '', 'reference' => ''],
        ['method' => 'credit',        'amount' => '', 'reference' => ''],
    ];

    public function nextSaleRef(): string
    {
        $last = DB::table('document_sequences')
            ->where('type', 'sale')->value('last_number') ?? 0;
        return 'SAL-' . now()->format('Ym') . '-' . str_pad(($last + 1), 4, '0', STR_PAD_LEFT);
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) return;
        if (!$product->is_service && $product->stock_quantity <= 0) {
            $this->dispatch('notify', type: 'error', message: "Out of stock: {$product->name}");
            return;
        }
        $key = $this->cartKey($productId);
        if ($key !== null) {
            $newQty = $this->cart[$key]['quantity'] + 1;
            if (!$product->is_service && $newQty > $product->stock_quantity) {
                $this->dispatch('notify', type: 'warning', message: "Only {$product->stock_quantity} in stock");
                return;
            }
            $this->cart[$key]['quantity'] = $newQty;
            $this->recalcLine($key);
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
                'stock_available' => $product->stock_quantity,
                'is_service'      => $product->is_service,
            ];
        }
        $this->search = '';
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function updateQty(int $index, $qty): void
    {
        $qty = (int) $qty;
        if ($qty <= 0) { $this->removeFromCart($index); return; }
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
        $this->cart = [];
        $this->customerId = null;
        $this->customerName = null;
        $this->customerPhone = null;
        $this->customerSearch = '';
        $this->globalDiscount = 0;
        $this->applyVat = false;
        $this->notes = '';
        $this->currentSaleId = null;
        $this->resetPayments();
    }

    public function selectCustomer(int $id): void
    {
        $c = Customer::find($id);
        if ($c) {
            $this->customerId    = $c->id;
            $this->customerName  = $c->company_name ?? $c->name;
            $this->customerPhone = $c->phone;
            $this->customerSearch = $this->customerName;
        }
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->customerName = null;
        $this->customerPhone = null;
        $this->customerSearch = '';
    }

    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Cart is empty');
            return;
        }
        $this->resetPayments();
        $this->payments[0]['amount'] = number_format($this->calcGrandTotal(), 2, '.', '');
        $this->showPaymentModal = true;
    }

    public function completeSale(): void
    {
        $grandTotal = $this->calcGrandTotal();
        $tendered   = collect($this->payments)->sum(fn ($p) => (float) ($p['amount'] ?: 0));
        if ($tendered < $grandTotal && !$this->customerId) {
            $this->dispatch('notify', type: 'error', message: 'Amount less than total. Assign a customer for credit.');
            return;
        }
        try {
            DB::transaction(function () use ($grandTotal, $tendered) {
                $subtotal      = collect($this->cart)->sum('total');
                $discount      = round($this->globalDiscount, 2);
                $taxable       = max(0, $subtotal - $discount);
                $vat           = $this->applyVat ? round($taxable * 0.16, 2) : 0;
                $changeDue     = max(0, $tendered - $grandTotal);
                $amountPaid    = min($tendered, $grandTotal);
                $paymentStatus = $amountPaid >= $grandTotal ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid');

                $sale = Sale::create([
                    'customer_id'    => $this->customerId,
                    'user_id'        => Auth::id(),
                    'subtotal'       => $subtotal,
                    'discount_amount'=> $discount,
                    'vat_amount'     => $vat,
                    'total'          => $grandTotal,
                    'amount_paid'    => $amountPaid,
                    'change_given'   => $changeDue,
                    'payment_status' => $paymentStatus,
                    'sale_type'      => 'walk_in',
                    'include_vat'    => $this->applyVat,
                    'notes'          => $this->notes,
                ]);

                $this->currentSaleId = $sale->id;

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
                        $product = Product::find($item['product_id']);
                        $before  = $product->stock_quantity;
                        $product->decrement('stock_quantity', $item['quantity']);
                        StockMovement::create([
                            'product_id'  => $item['product_id'],
                            'type'        => 'out',
                            'source'      => 'sale',
                            'source_id'   => $sale->id,
                            'quantity'    => -$item['quantity'],
                            'stock_before'=> $before,
                            'stock_after' => $before - $item['quantity'],
                            'user_id'     => Auth::id(),
                        ]);
                    }
                }

                foreach ($this->payments as $p) {
                    $amt = (float) ($p['amount'] ?: 0);
                    if ($amt > 0) {
                        Payment::create([
                            'sale_id'   => $sale->id,
                            'amount'    => $amt,
                            'method'    => $p['method'],
                            'reference' => $p['reference'] ?: null,
                            'user_id'   => Auth::id(),
                        ]);
                    }
                }

                // Update any pending M-Pesa transaction to link to this sale
                \App\Models\MpesaTransaction::where('status', 'completed')
                    ->whereNull('sale_id')
                    ->where('amount', $grandTotal)
                    ->latest()
                    ->first()?->update(['sale_id' => $sale->id]);

                $customerId    = $this->customerId;
                $customerName  = $this->customerName ?? 'Walk-in Customer';
                $customerPhone = $this->customerPhone;
                $applyVat      = $this->applyVat;
                $notes         = $this->notes;

                $invoice = Invoice::withoutEvents(function () use (
                    $sale, $paymentStatus, $subtotal, $discount, $vat,
                    $grandTotal, $amountPaid, $customerId, $customerName,
                    $customerPhone, $applyVat, $notes
                ) {
                    $inv = new Invoice([
                        'customer_id'     => $customerId,
                        'sale_id'         => $sale->id,
                        'created_by'      => Auth::id(),
                        'client_name'     => $customerName,
                        'client_phone'    => $customerPhone,
                        'status'          => $paymentStatus === 'paid' ? 'paid' : 'unpaid',
                        'include_vat'     => $applyVat,
                        'subtotal'        => $subtotal,
                        'discount_amount' => $discount,
                        'vat_amount'      => $vat,
                        'total'           => $grandTotal,
                        'amount_paid'     => $amountPaid,
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

                $this->completedSale = [
                    'sale_number'    => $sale->sale_number,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_id'     => $invoice->id,
                    'customer'       => $this->customerName ?? 'Walk-in Customer',
                    'items'          => $this->cart,
                    'subtotal'       => $subtotal,
                    'discount'       => $discount,
                    'vat'            => $vat,
                    'total'          => $grandTotal,
                    'tendered'       => $amountPaid,
                    'change'         => $changeDue,
                    'payments'       => collect($this->payments)
                        ->filter(fn ($p) => (float) ($p['amount'] ?: 0) > 0)
                        ->values()->toArray(),
                    'cashier' => Auth::user()->name,
                    'date'    => now()->format('d/m/Y H:i'),
                ];
            });

            $this->showPaymentModal = false;
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
        $this->cart[$key]['total'] = round(($item['unit_price'] * $item['quantity']) - $item['discount'], 2);
    }

    private function resetPayments(): void
    {
        $this->payments = [
            ['method' => 'cash',          'amount' => '', 'reference' => ''],
            ['method' => 'mpesa',         'amount' => '', 'reference' => ''],
            ['method' => 'bank_transfer', 'amount' => '', 'reference' => ''],
            ['method' => 'cheque',        'amount' => '', 'reference' => ''],
            ['method' => 'credit',        'amount' => '', 'reference' => ''],
        ];
    }

    public function render()
    {
        $subtotal    = collect($this->cart)->sum('total');
        $discount    = round($this->globalDiscount, 2);
        $taxable     = max(0, $subtotal - $discount);
        $vat         = $this->applyVat ? round($taxable * 0.16, 2) : 0;
        $grandTotal  = round($taxable + $vat, 2);
        $tendered    = collect($this->payments)->sum(fn ($p) => (float) ($p['amount'] ?: 0));
        $change      = max(0, $tendered - $grandTotal);
        $balanceDue  = max(0, $grandTotal - $tendered);
        $cartCount   = collect($this->cart)->sum('quantity');
        $nextSaleRef = $this->nextSaleRef();

        $products = Product::active()
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->with('category')->orderBy('name')->limit(48)->get();

        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        $customerSuggestions = collect();
        if (strlen($this->customerSearch) >= 2) {
            $customerSuggestions = Customer::active()
                ->where(function ($q) {
                    $q->where('name', 'like', "%{$this->customerSearch}%")
                      ->orWhere('company_name', 'like', "%{$this->customerSearch}%")
                      ->orWhere('phone', 'like', "%{$this->customerSearch}%");
                })->limit(6)->get();
        }

        return view('livewire.pos.cart', compact(
            'subtotal', 'discount', 'taxable', 'vat', 'grandTotal',
            'tendered', 'change', 'balanceDue', 'cartCount',
            'products', 'categories', 'customerSuggestions', 'nextSaleRef'
        ))->layout('layouts.pos');
    }
}