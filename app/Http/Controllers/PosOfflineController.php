<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Notifications\LowStockNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosOfflineController extends Controller
{
    // ── Sync products to offline cache ────────────────────────────────────────
    public function products()
    {
        $locationId = Auth::user()->primaryLocationId();

        if (! $locationId) {
            return response()->json(['error' => 'No shop assigned to your account.'], 403);
        }

        // Explicitly scoped to the user's primary location (not the
        // session's "active shop") and bypassing the global shop scope,
        // since this is a sync endpoint that should reflect the assigned
        // shop's catalog regardless of whatever shop happens to be active
        // in the admin session.
        $products = Product::withoutShopScope()
            ->active()
            ->where('location_id', $locationId)
            ->with(['category'])
            ->get()
            ->map(function ($p) {
                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'sku'           => $p->sku ?? '',
                    'unit'          => $p->unit ?? 'pcs',
                    'selling_price' => (float) $p->selling_price,
                    'cost_price'    => (float) $p->cost_price,
                    'stock'         => $p->is_service ? null : (int) $p->quantity,
                    'is_service'    => (bool) $p->is_service,
                    'category'      => $p->category?->name ?? 'Uncategorised',
                    'category_id'   => $p->category_id,
                    'image'         => $p->image ? asset('storage/' . $p->image) : null,
                ];
            });

        return response()->json([
            'products'    => $products,
            'location_id' => $locationId,
            'synced_at'   => now()->toDateTimeString(),
        ]);
    }

    // ── Sync customers to offline cache ───────────────────────────────────────
    public function customers()
    {
        $customers = Customer::active()
            ->get()
            ->map(fn ($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'company_name' => $c->company_name ?? '',
                'display_name' => $c->company_name ?? $c->name,
                'phone'        => $c->phone ?? '',
                'type'         => $c->type,
            ]);

        return response()->json([
            'customers' => $customers,
            'synced_at' => now()->toDateTimeString(),
        ]);
    }

    // ── Receive and save offline queued sales ─────────────────────────────────
    public function sync(Request $request)
    {
        $sales  = $request->input('sales', []);
        $saved  = [];
        $errors = [];

        $locationId = Auth::user()->primaryLocationId();

        foreach ($sales as $offlineSale) {
            try {
                if (! $locationId) {
                    throw new \Exception('Your account has no shop assigned. Contact admin.');
                }

                DB::transaction(function () use ($offlineSale, &$saved, $locationId) {
                    $cart          = $offlineSale['cart'];
                    $subtotal      = collect($cart)->sum('total');
                    $discount      = (float) ($offlineSale['discount'] ?? 0);
                    $applyVat      = (bool) ($offlineSale['apply_vat'] ?? false);
                    $taxable       = max(0, $subtotal - $discount);
                    $vat           = $applyVat ? round($taxable * 0.16, 2) : 0;
                    $grandTotal    = round($taxable + $vat, 2);
                    $payments      = $offlineSale['payments'] ?? [];
                    $tendered      = collect($payments)->sum(fn ($p) => (float) ($p['amount'] ?? 0));
                    $amountPaid    = min($tendered, $grandTotal);
                    $changeDue     = max(0, $tendered - $grandTotal);
                    $paymentStatus = $amountPaid >= $grandTotal ? 'paid'
                        : ($amountPaid > 0 ? 'partial' : 'unpaid');

                    $sale = Sale::create([
                        'customer_id'     => $offlineSale['customer_id'] ?? null,
                        'user_id'         => Auth::id(),
                        'location_id'     => $locationId,
                        'subtotal'        => $subtotal,
                        'discount_amount' => $discount,
                        'vat_amount'      => $vat,
                        'total'           => $grandTotal,
                        'amount_paid'     => $amountPaid,
                        'change_given'    => $changeDue,
                        'payment_status'  => $paymentStatus,
                        'sale_type'       => 'walk_in',
                        'include_vat'     => $applyVat,
                        'notes'           => ($offlineSale['notes'] ?? '')
                            . ' [OFFLINE SALE - ' . ($offlineSale['offline_id'] ?? '') . ']',
                    ]);

                    foreach ($cart as $item) {
                        SaleItem::create([
                            'sale_id'      => $sale->id,
                            'product_id'   => $item['product_id'],
                            'product_name' => $item['name'],
                            'product_sku'  => $item['sku'] ?? '',
                            'unit'         => $item['unit'] ?? 'pcs',
                            'unit_price'   => $item['unit_price'],
                            'cost_price'   => $item['cost_price'],
                            'quantity'     => $item['quantity'],
                            'discount'     => $item['discount'] ?? 0,
                            'total'        => $item['total'],
                        ]);

                        if (! ($item['is_service'] ?? false)) {
                            // Stock lives directly on the (shop-scoped)
                            // Product row now. withoutShopScope() is used
                            // because this is a background sync that may
                            // run outside the admin session's active-shop
                            // context — lock and decrement explicitly by
                            // product id rather than relying on the scope.
                            $product = Product::withoutShopScope()
                                ->lockForUpdate()
                                ->findOrFail($item['product_id']);

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
                                'notes'        => "Offline sale synced — {$sale->sale_number}",
                                'user_id'      => Auth::id(),
                            ]);

                            // Fire low stock alert if threshold crossed
                            if ($after <= $product->reorder_point) {
                                $managers = User::whereHas('activeLocations', fn ($q) =>
                                    $q->where('location_id', $locationId)
                                      ->where('location_user.shop_role', 'shop_manager')
                                )->get();

                                foreach ($managers as $manager) {
                                    $manager->notify(new LowStockNotification($product));
                                }
                            }
                        }
                    }

                    foreach ($payments as $p) {
                        $amt = (float) ($p['amount'] ?? 0);
                        if ($amt > 0) {
                            Payment::create([
                                'sale_id'   => $sale->id,
                                'amount'    => $amt,
                                'method'    => $p['method'],
                                'reference' => $p['reference'] ?? null,
                                'user_id'   => Auth::id(),
                            ]);
                        }
                    }

                    $inv = new Invoice([
                        'customer_id'     => $offlineSale['customer_id'] ?? null,
                        'sale_id'         => $sale->id,
                        'created_by'      => Auth::id(),
                        'location_id'     => $locationId,
                        'client_name'     => $offlineSale['customer_name'] ?? 'Walk-in Customer',
                        'client_phone'    => $offlineSale['customer_phone'] ?? null,
                        'status'          => $paymentStatus === 'paid' ? 'paid' : 'unpaid',
                        'include_vat'     => $applyVat,
                        'subtotal'        => $subtotal,
                        'discount_amount' => $discount,
                        'vat_amount'      => $vat,
                        'total'           => $grandTotal,
                        'amount_paid'     => $amountPaid,
                        'notes'           => 'Offline sale synced on ' . now()->format('d M Y H:i'),
                        'footer_text'     => 'Accounts are due on demand.',
                    ]);
                    $inv->invoice_number = Invoice::generateNumber();
                    $inv->save();

                    foreach ($cart as $i => $item) {
                        InvoiceItem::create([
                            'invoice_id'  => $inv->id,
                            'product_id'  => $item['product_id'],
                            'sort_order'  => $i,
                            'description' => $item['name'],
                            'unit'        => $item['unit'] ?? 'pcs',
                            'unit_price'  => $item['unit_price'],
                            'cost_price'  => $item['cost_price'],
                            'quantity'    => $item['quantity'],
                            'discount'    => $item['discount'] ?? 0,
                            'total'       => $item['total'],
                        ]);
                    }

                    $saved[] = [
                        'offline_id'     => $offlineSale['offline_id'],
                        'sale_number'    => $sale->sale_number,
                        'invoice_number' => $inv->invoice_number,
                    ];
                });

            } catch (\Exception $e) {
                $errors[] = [
                    'offline_id' => $offlineSale['offline_id'] ?? 'unknown',
                    'error'      => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'saved'  => $saved,
            'errors' => $errors,
        ]);
    }
}