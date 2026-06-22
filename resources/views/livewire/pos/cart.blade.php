{{-- resources/views/livewire/pos/cart.blade.php --}}
<div class="flex flex-col h-full overflow-hidden bg-gray-100" x-data="{ showNotes: false }">

    {{-- ════════════════════════════════════════════════════════════
         HEADER
    ════════════════════════════════════════════════════════════ --}}
    <div class="bg-green-700 text-white px-4 py-2.5 shrink-0 flex items-center justify-between">
        <div>
            <h1 class="font-bold text-sm tracking-wide">
                {{ auth()->user()->location?->name ?? 'POS Till' }}
            </h1>
            <p class="text-[11px] text-green-200">{{ auth()->user()->name }}</p>
        </div>
        <span class="text-xs bg-green-600 rounded-full px-2 py-0.5">{{ $cartCount }} item(s)</span>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         SEARCH / SCAN
    ════════════════════════════════════════════════════════════ --}}
    <div class="px-3 py-2 border-b border-gray-200 bg-white shrink-0 relative" x-data="{ open: false }">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                wire:model.live.debounce.250ms="search"
                x-on:focus="open = true"
                x-on:keydown.enter.prevent="$wire.addBySearchOrBarcode()"
                x-on:keydown.escape="open = false"
                placeholder="Scan barcode or search products..."
                class="w-full bg-gray-50 text-gray-900 placeholder-gray-400 rounded-lg pl-9 pr-4 py-2.5 text-sm border border-gray-200 focus:border-green-600 focus:ring-1 focus:ring-green-600 focus:outline-none"
                x-ref="searchBox"
                @keydown.window="if (!['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) $refs.searchBox.focus()"
                autofocus
            />
        </div>

        {{-- Search results dropdown --}}
        @if($searchResults->isNotEmpty())
        <div
            x-show="open"
            x-on:click.outside="open = false"
            class="absolute left-3 right-3 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-20 overflow-hidden max-h-72 overflow-y-auto"
        >
            @foreach($searchResults as $product)
            @php
                $stock = $product->current_stock ?? 0;
                $outOfStock = !$product->is_service && $stock <= 0;
            @endphp
            <button
                type="button"
                wire:click="addToCart({{ $product->id }})"
                wire:key="sr-{{ $product->id }}"
                x-on:click="open = false"
                @disabled($outOfStock)
                class="w-full text-left px-3 py-2 flex items-center justify-between gap-3 border-b border-gray-100 last:border-0 transition
                    {{ $outOfStock ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50' }}"
            >
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $product->name }}</p>
                    <p class="text-[11px] text-gray-400 font-mono">{{ $product->sku }}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-bold text-green-700">KES {{ number_format($product->selling_price, 2) }}</p>
                    @if(!$product->is_service)
                    <p class="text-[10px] {{ $outOfStock ? 'text-red-500' : 'text-gray-400' }}">
                        {{ $outOfStock ? 'Out of stock' : $stock . ' in stock' }}
                    </p>
                    @endif
                </div>
            </button>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════
         CART TABLE
    ════════════════════════════════════════════════════════════ --}}
    <div class="flex-1 overflow-y-auto bg-white">
        <table class="w-full text-sm">
            <thead class="sticky top-0 bg-gray-50 z-10">
                <tr class="border-b border-gray-200 text-gray-500 text-xs uppercase tracking-wide">
                    <th class="text-left px-3 py-2 font-semibold">Item</th>
                    <th class="text-center px-2 py-2 font-semibold w-28">Qty</th>
                    <th class="text-right px-2 py-2 font-semibold w-24">Price</th>
                    <th class="text-right px-3 py-2 font-semibold w-28">Total</th>
                    <th class="w-6"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($cart as $index => $item)
                <tr
                    wire:key="ci-{{ $index }}"
                    wire:click="selectRow({{ $index }})"
                    class="cursor-pointer transition {{ $selectedIndex === $index ? 'bg-blue-50' : 'hover:bg-gray-50' }}"
                >
                    <td class="px-3 py-2">
                        <p class="font-medium text-gray-900 text-sm leading-tight">{{ $item['name'] }}</p>
                        <p class="text-[11px] text-gray-400 font-mono">{{ $item['sku'] }}</p>
                    </td>
                    <td class="px-2 py-2 text-center" @click.stop>
                        <div class="inline-flex items-center bg-gray-100 rounded-lg border border-gray-200 overflow-hidden">
                            <button wire:click="updateQty({{ $index }}, {{ $item['quantity'] - 1 }})"
                                class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition text-sm">−</button>
                            <input
                                wire:key="qty-{{ $index }}-{{ $item['quantity'] }}"
                                type="number"
                                value="{{ $item['quantity'] }}"
                                wire:change="updateQty({{ $index }}, $event.target.value)"
                                class="w-10 text-center bg-transparent text-gray-900 text-xs py-1 focus:outline-none"
                                min="1"
                            />
                            <button wire:click="updateQty({{ $index }}, {{ $item['quantity'] + 1 }})"
                                class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition text-sm">+</button>
                        </div>
                    </td>
                    <td class="px-2 py-2 text-right" @click.stop>
                        <input
                            type="number"
                            value="{{ $item['unit_price'] }}"
                            wire:change="updatePrice({{ $index }}, $event.target.value)"
                            class="w-20 bg-transparent border border-transparent hover:border-gray-300 focus:border-green-500 rounded text-right px-1 py-0.5 text-xs text-gray-700 focus:outline-none"
                            step="0.01" min="0"
                        />
                    </td>
                    <td class="px-3 py-2 text-right font-semibold text-gray-900 text-sm">
                        {{ number_format($item['total'], 2) }}
                    </td>
                    <td class="px-1 text-center" @click.stop>
                        <button wire:click="removeFromCart({{ $index }})" class="text-gray-300 hover:text-red-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-16 text-gray-300">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="text-sm">Cart is empty — search or scan a product</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         TOTALS + DISCOUNT/VAT
    ════════════════════════════════════════════════════════════ --}}
    <div class="shrink-0 border-t border-gray-200 bg-gray-50">

        {{-- Discount / VAT / Notes / Clear --}}
        <div class="flex items-center gap-3 px-3 py-2 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <label class="text-[11px] text-gray-500 shrink-0">Discount KES</label>
                <input
                    type="number"
                    wire:model.blur="globalDiscount"
                    class="w-24 bg-white border border-gray-200 rounded text-xs px-2 py-1 text-gray-800 focus:border-green-500 focus:outline-none"
                    min="0" step="0.01" placeholder="0"
                />
            </div>
            <label class="flex items-center gap-1.5 cursor-pointer shrink-0">
                <input type="checkbox" wire:model="applyVat" class="rounded border-gray-300 text-green-600 focus:ring-green-500 w-3 h-3">
                <span class="text-[11px] text-gray-500">VAT 16%</span>
            </label>

            <div x-show="showNotes" x-cloak class="flex-1">
                <input
                    type="text"
                    wire:model="notes"
                    placeholder="Sale notes..."
                    class="w-full bg-white border border-gray-200 rounded text-xs px-2 py-1 text-gray-700 focus:border-green-500 focus:outline-none"
                />
            </div>
            <button type="button" x-on:click="showNotes = !showNotes" class="text-[11px] text-gray-400 hover:text-gray-600 shrink-0">Notes</button>

            <button
                wire:click="clearCart"
                @disabled(empty($cart))
                class="ml-auto text-[11px] font-medium px-3 py-1 rounded-lg bg-gray-200 text-gray-600 hover:bg-gray-300 transition disabled:opacity-40 disabled:cursor-not-allowed"
            >Clear All</button>
        </div>

        {{-- Totals --}}
        <div class="px-4 py-2 space-y-0.5">
            <div class="flex justify-between text-xs text-gray-500">
                <span>Subtotal</span>
                <span>KES {{ number_format($subtotal, 2) }}</span>
            </div>
            @if($discount > 0)
            <div class="flex justify-between text-xs text-red-500">
                <span>Discount</span>
                <span>− KES {{ number_format($discount, 2) }}</span>
            </div>
            @endif
            @if($vat > 0)
            <div class="flex justify-between text-xs text-gray-500">
                <span>VAT 16%</span>
                <span>KES {{ number_format($vat, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between items-baseline pt-1 border-t border-gray-200 mt-1">
                <span class="text-sm font-bold text-gray-700 uppercase tracking-wide">Total</span>
                <span class="text-2xl font-black text-green-700">KES {{ number_format($grandTotal, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         PAYMENT
    ════════════════════════════════════════════════════════════ --}}
    <div class="shrink-0 bg-white border-t-2 border-gray-200 px-4 py-3 space-y-3">

        {{-- Payment method buttons (loaded from PaymentMethod::active()) --}}
        <div class="flex gap-2 overflow-x-auto">
            @forelse($paymentMethods as $method)
            <button
                type="button"
                wire:click="selectPaymentMethod('{{ $method->code }}')"
                wire:key="pm-{{ $method->code }}"
                @disabled(empty($cart))
                class="flex-1 min-w-[100px] py-3 rounded-xl border-2 font-bold text-sm uppercase tracking-wide transition disabled:opacity-40 disabled:cursor-not-allowed
                    {{ $activeMethod === $method->code ? 'border-green-600 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-green-400 hover:bg-green-50' }}"
            >{{ $method->icon ?: '💳' }} {{ $method->name }}</button>
            @empty
            <p class="text-xs text-gray-400 px-2">No payment methods configured.</p>
            @endforelse
        </div>

        {{-- Amount + reference + complete sale --}}
        @if($showPaymentPanel)
        <div class="flex items-end gap-3" x-data x-init="$nextTick(() => $refs.amountInput?.focus())">
            <div class="flex-1">
                <label class="block text-[11px] text-gray-500 mb-1">
                    Amount ({{ optional($paymentMethods->firstWhere('code', $activeMethod))->name ?? $activeMethod }}) — KES
                </label>
                <input
                    type="number"
                    wire:model.live="amountTendered"
                    x-ref="amountInput"
                    class="w-full bg-white border-2 border-green-400 rounded-lg text-lg font-bold px-3 py-2 text-gray-800 focus:outline-none focus:border-green-600"
                    min="0" step="0.01" placeholder="0.00"
                />
                @if($change > 0 && $activeMethod === 'cash')
                <p class="text-xs font-bold text-yellow-600 mt-1">Change due: KES {{ number_format($change, 2) }}</p>
                @endif
            </div>

            @if(optional($paymentMethods->firstWhere('code', $activeMethod))->requires_reference)
            <div class="flex-1">
                <label class="block text-[11px] text-gray-500 mb-1">Reference / Code</label>
                <input
                    type="text"
                    wire:model="paymentReference"
                    class="w-full bg-white border border-gray-200 rounded-lg text-sm px-3 py-2 text-gray-700 focus:border-green-500 focus:outline-none"
                    placeholder="Transaction code"
                />
            </div>
            @endif

            <button
                wire:click="cancelPayment"
                class="px-3 py-2 rounded-lg text-xs font-medium bg-gray-200 text-gray-600 hover:bg-gray-300 transition mb-px"
            >Cancel</button>
        </div>
        @endif

        {{-- Complete sale --}}
        <button
            wire:click="completeSale"
            wire:loading.attr="disabled"
            @disabled(empty($cart) || !$showPaymentPanel)
            class="w-full py-4 text-lg font-extrabold text-white rounded-xl transition
                {{ (empty($cart) || !$showPaymentPanel)
                    ? 'bg-gray-300 cursor-not-allowed'
                    : 'bg-green-700 hover:bg-green-600 active:bg-green-800' }}"
        >
            <span wire:loading.remove wire:target="completeSale">
                ✔ Complete Sale — KES {{ number_format($grandTotal, 2) }}
            </span>
            <span wire:loading wire:target="completeSale" class="flex items-center justify-center gap-2">
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                Processing...
            </span>
        </button>
    </div>

{{-- ════════════════════════════════════════════════════════════
     RECEIPT MODAL
════════════════════════════════════════════════════════════ --}}
@if($showReceipt && $completedSale)
<div class="absolute inset-0 bg-black/80 overflow-y-auto" style="z-index:9999;">
<div class="min-h-full flex items-center justify-center p-4 py-6">
    <div class="bg-white w-full max-w-lg shadow-2xl text-gray-900 overflow-hidden rounded-2xl">

        <div id="receipt-print" style="font-family: Arial, sans-serif; font-size: 13px; color: #1a1a1a;">

            <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px 24px 12px;">
                <img src="{{ asset('images/gigateam-logo.png') }}" alt="Gigateam" style="height: 56px; width: auto;">
                <div style="text-align: right; font-size: 11px; color: #555;">
                    <div style="font-weight: 700; font-size: 13px; color: #111;">GIGATEAM SOLUTIONS LTD</div>
                    <div>White Angle House, 1st Floor – Suite 62</div>
                    <div>P.O Box 47271-00100, Nairobi</div>
                    <div>+254 111292948 / 718811661</div>
                    <div>sales@gigateamltd.com</div>
                </div>
            </div>

            <div style="background: #DC2626; padding: 10px 24px; text-align: center;">
                <div style="font-size: 22px; font-weight: 900; letter-spacing: 4px; color: #fff;">RECEIPT</div>
            </div>

            <div style="display: flex; background: #f5f5f5; border-bottom: 2px solid #DC2626;">
                <div style="flex: 1; padding: 10px 24px; border-right: 1px solid #ddd;">
                    <div style="font-size: 10px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 1px;">Receipt No.</div>
                    <div style="font-weight: 700; margin-top: 2px;">{{ $completedSale['invoice_number'] }}</div>
                </div>
                <div style="flex: 1; padding: 10px 24px; border-right: 1px solid #ddd;">
                    <div style="font-size: 10px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 1px;">Date & Time</div>
                    <div style="font-weight: 700; margin-top: 2px;">{{ $completedSale['date'] }}</div>
                </div>
                <div style="flex: 1; padding: 10px 24px;">
                    <div style="font-size: 10px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 1px;">Payment</div>
                    <div style="font-weight: 700; margin-top: 2px;">
                        {{ $completedSale['payment_method'] }}{{ $completedSale['reference'] ? ' (' . $completedSale['reference'] . ')' : '' }}
                    </div>
                </div>
            </div>

            <div style="display: flex; padding: 14px 24px; gap: 24px; border-bottom: 1px solid #eee;">
                <div style="flex: 1;">
                    <div style="font-size: 10px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Customer</div>
                    <div style="font-weight: 700; font-size: 14px;">{{ $completedSale['customer'] }}</div>
                </div>
                <div style="flex: 1;">
                    <div style="font-size: 10px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Served By</div>
                    <div style="font-weight: 700; font-size: 14px;">{{ $completedSale['cashier'] }}</div>
                </div>
            </div>

            <div style="padding: 0 24px;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 4px;">
                    <thead>
                        <tr style="border-bottom: 2px solid #DC2626;">
                            <th style="text-align: left; padding: 8px 0; font-size: 10px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 1px; width: 40%;">Description</th>
                            <th style="text-align: center; padding: 8px 0; font-size: 10px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 1px;">Qty</th>
                            <th style="text-align: right; padding: 8px 0; font-size: 10px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 1px;">Unit Price</th>
                            <th style="text-align: right; padding: 8px 0; font-size: 10px; font-weight: 700; color: #DC2626; text-transform: uppercase; letter-spacing: 1px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($completedSale['items'] as $item)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 8px 0; font-size: 12px;">{{ $item['name'] }}</td>
                            <td style="padding: 8px 0; text-align: center; font-size: 12px;">{{ $item['quantity'] }}</td>
                            <td style="padding: 8px 0; text-align: right; font-size: 12px;">{{ number_format($item['unit_price'], 2) }}</td>
                            <td style="padding: 8px 0; text-align: right; font-size: 12px; font-weight: 600;">{{ number_format($item['total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding: 10px 24px; border-top: 2px solid #DC2626; margin: 0 24px;">
                @if($completedSale['discount'] > 0)
                <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; color: #555;">
                    <span>Discount</span>
                    <span style="color: #DC2626;">- KES {{ number_format($completedSale['discount'], 2) }}</span>
                </div>
                @endif
                @if($completedSale['vat'] > 0)
                <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; color: #555;">
                    <span>VAT (16%)</span>
                    <span>KES {{ number_format($completedSale['vat'], 2) }}</span>
                </div>
                @endif
                <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; color: #555;">
                    <span>{{ $completedSale['payment_method'] }} Tendered</span>
                    <span>KES {{ number_format($completedSale['tendered'], 2) }}</span>
                </div>
                @if($completedSale['change'] > 0)
                <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; color: #555;">
                    <span>Change</span>
                    <span>KES {{ number_format($completedSale['change'], 2) }}</span>
                </div>
                @endif
                <div style="display: flex; justify-content: space-between; padding: 8px 0 4px; font-size: 16px; font-weight: 900; border-top: 1px solid #eee; margin-top: 6px;">
                    <span>TOTAL</span>
                    <span style="color: #DC2626;">KES {{ number_format($completedSale['total'], 2) }}</span>
                </div>
            </div>

            <div style="background: #DC2626; padding: 10px 24px; display: flex; align-items: center; justify-content: space-between; margin-top: 16px;">
                <span style="color: #fff; font-weight: 700; font-size: 12px;">Goods sold are NOT returnable</span>
                <span style="color: #fecaca; font-size: 11px;">Thank you for your business!</span>
            </div>
        </div>

        <div class="p-4 flex gap-3 border-t border-gray-100 no-print">
            <button
                onclick="window.print()"
                class="flex-1 py-2.5 rounded-xl border border-gray-300 text-sm font-medium hover:bg-gray-50 transition text-gray-700"
            >🖨 Print</button>
            
                href="/invoices/{{ $completedSale['invoice_id'] }}/pdf"
                target="_blank"
                class="flex-1 py-2.5 rounded-xl border border-gray-300 text-sm font-medium hover:bg-gray-50 transition text-gray-700 text-center"
            >📄 PDF</a>
            <button
                wire:click="newSale"
                class="flex-1 py-2.5 rounded-xl bg-red-600 text-white text-sm font-bold hover:bg-red-500 transition"
            >New Sale</button>
        </div>
    </div>
</div>
</div>
@endif

</div>