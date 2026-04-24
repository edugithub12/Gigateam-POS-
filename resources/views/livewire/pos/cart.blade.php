{{-- resources/views/livewire/pos/cart.blade.php --}}
<div class="flex h-full overflow-hidden relative" x-data="{ showNotes: false }">

    {{-- ════════════════════════════════════════════
         LEFT: Product search + grid
    ════════════════════════════════════════════ --}}
    <div class="flex flex-col flex-1 min-w-0 border-r border-gray-800">

        {{-- Search + category filter --}}
        <div class="bg-gray-900 border-b border-gray-800 p-3 space-y-2 shrink-0">
            <div class="relative">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    placeholder="Search product, SKU or scan barcode..."
                    class="w-full bg-gray-800 text-gray-100 placeholder-gray-500 rounded-lg pl-9 pr-4 py-2 text-sm border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500 focus:outline-none"
                    x-ref="searchBox"
                    @keydown.window="if (!['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) $refs.searchBox.focus()"
                />
            </div>

            {{-- Category pills --}}
            <div class="flex gap-1.5 overflow-x-auto pb-0.5 scrollbar-hide">
                <button
                    wire:click="$set('categoryId', null)"
                    class="shrink-0 px-3 py-1 rounded-full text-xs font-medium transition {{ $categoryId === null ? 'bg-red-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}"
                >All</button>
                @foreach($categories as $cat)
                <button
                    wire:click="$set('categoryId', {{ $cat->id }})"
                    class="shrink-0 px-3 py-1 rounded-full text-xs font-medium transition {{ $categoryId === $cat->id ? 'bg-red-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}"
                >{{ $cat->name }}</button>
                @endforeach
            </div>
        </div>

        {{-- Product grid --}}
        <div class="flex-1 overflow-y-auto p-3">
            @if($products->isEmpty())
                <div class="flex flex-col items-center justify-center h-48 text-gray-600">
                    <svg class="w-12 h-12 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-sm">No products found</p>
                </div>
            @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2">
            @foreach($products as $product)
                <button
                    wire:click="addToCart({{ $product->id }})"
                    wire:key="p-{{ $product->id }}"
                    @disabled($product->isOutOfStock())
                    @class([
                        'relative text-left rounded-xl p-3 border transition-all duration-150 group focus:outline-none',
                        'bg-gray-800 border-gray-700 hover:border-red-500 hover:bg-gray-750 active:scale-95 cursor-pointer'
                            => !$product->isOutOfStock(),
                        'bg-gray-900 border-gray-800 opacity-40 cursor-not-allowed'
                            => $product->isOutOfStock(),
                    ])
                >
                    <span class="inline-block text-[10px] text-red-400 bg-red-900/30 rounded px-1.5 py-0.5 mb-1.5 leading-none truncate max-w-full">
                        {{ $product->category->name ?? '' }}
                    </span>
                    <p class="text-xs font-medium text-gray-100 leading-snug line-clamp-2 mb-2 min-h-[2.5rem]">
                        {{ $product->name }}
                    </p>
                    <div class="flex items-end justify-between">
                        <span class="text-sm font-bold text-green-400">
                            {{ number_format($product->selling_price, 0) }}
                        </span>
                        @if($product->is_service)
                            <span class="text-[10px] text-purple-400">SVC</span>
                        @elseif($product->isOutOfStock())
                            <span class="text-[10px] text-red-400 font-medium">OUT</span>
                        @elseif($product->isLowStock())
                            <span class="text-[10px] text-yellow-400">{{ $product->stock_quantity }}</span>
                        @else
                            <span class="text-[10px] text-gray-500">{{ $product->stock_quantity }}</span>
                        @endif
                    </div>
                    <div class="absolute inset-0 rounded-xl bg-red-500/5 opacity-0 group-hover:opacity-100 transition pointer-events-none"></div>
                </button>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         RIGHT: Cart + totals
    ════════════════════════════════════════════ --}}
    <div class="flex flex-col w-96 shrink-0 bg-gray-900">

        {{-- Customer search --}}
        <div class="p-3 border-b border-gray-800 shrink-0">
            @if($customerName)
            <div class="flex items-center justify-between bg-red-950 border border-red-800 rounded-lg px-3 py-2">
                <div>
                    <p class="text-sm font-medium text-red-200">{{ $customerName }}</p>
                    <p class="text-xs text-red-400">{{ $customerPhone ?? 'No phone' }}</p>
                </div>
                <button wire:click="clearCustomer" class="text-red-500 hover:text-red-300 transition ml-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            @else
            <div class="relative" x-data="{ open: false }">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="customerSearch"
                    x-on:focus="open = true"
                    x-on:click.outside="open = false"
                    placeholder="Search customer (optional)..."
                    class="w-full bg-gray-800 text-gray-300 placeholder-gray-600 rounded-lg px-3 py-2 text-sm border border-gray-700 focus:border-red-500 focus:outline-none"
                />
                @if($customerSuggestions->isNotEmpty())
                <div x-show="open" class="absolute top-full left-0 right-0 mt-1 bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-20 overflow-hidden">
                    @foreach($customerSuggestions as $cust)
                    <button
                        wire:click="selectCustomer({{ $cust->id }})"
                        class="w-full text-left px-3 py-2.5 hover:bg-gray-700 transition border-b border-gray-700 last:border-0"
                    >
                        <p class="text-sm text-gray-200 font-medium">{{ $cust->company_name ?? $cust->name }}</p>
                        <p class="text-xs text-gray-500">{{ $cust->phone }} · {{ $cust->type }}</p>
                    </button>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
        </div>

        {{-- Cart items --}}
        <div class="flex-1 overflow-y-auto">
            @if(empty($cart))
            <div class="flex flex-col items-center justify-center h-full text-gray-700 select-none">
                <svg class="w-16 h-16 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-sm font-medium text-gray-600">Cart is empty</p>
                <p class="text-xs text-gray-700 mt-1">Click a product or scan barcode</p>
            </div>
            @else
            <div class="divide-y divide-gray-800">
                @foreach($cart as $index => $item)
                <div class="p-3 group" wire:key="ci-{{ $index }}">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-100 truncate">{{ $item['name'] }}</p>
                            <p class="text-[11px] text-gray-500 font-mono">{{ $item['sku'] }}</p>
                        </div>
                        <button
                            wire:click="removeFromCart({{ $index }})"
                            class="opacity-0 group-hover:opacity-100 text-gray-600 hover:text-red-400 transition shrink-0 mt-0.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Qty stepper --}}
                        <div class="flex items-center bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                            <button wire:click="updateQty({{ $index }}, {{ $item['quantity'] - 1 }})"
                                class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 transition text-base leading-none">−</button>
                            <input
                                type="number"
                                value="{{ $item['quantity'] }}"
                                wire:change="updateQty({{ $index }}, $event.target.value)"
                                class="w-9 text-center bg-transparent text-gray-100 text-sm py-0.5 focus:outline-none"
                                min="1"
                            />
                            <button wire:click="updateQty({{ $index }}, {{ $item['quantity'] + 1 }})"
                                class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 transition text-base leading-none">+</button>
                        </div>

                        {{-- Unit price --}}
                        <div class="flex-1 relative">
                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-500">KES</span>
                            <input
                                type="number"
                                value="{{ $item['unit_price'] }}"
                                wire:change="updatePrice({{ $index }}, $event.target.value)"
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg text-xs text-right pr-2 pl-7 py-1.5 text-gray-200 focus:border-red-500 focus:outline-none"
                                step="0.01" min="0"
                            />
                        </div>

                        {{-- Line total --}}
                        <div class="w-20 text-right shrink-0">
                            <p class="text-sm font-semibold text-green-400">{{ number_format($item['total'], 2) }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Totals + actions --}}
        <div class="border-t border-gray-800 p-3 space-y-2 shrink-0">

            {{-- Discount + VAT --}}
            <div class="flex gap-2 items-end">
                <div class="flex-1">
                    <label class="text-[11px] text-gray-500 block mb-1">Discount (KES)</label>
                    <input
                        type="number"
                        wire:model.blur="globalDiscount"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg text-sm px-3 py-1.5 text-gray-200 focus:border-red-500 focus:outline-none"
                        min="0" step="0.01" placeholder="0"
                    />
                </div>
                <label class="flex items-center gap-1.5 pb-1.5 cursor-pointer shrink-0">
                    <input type="checkbox" wire:model="applyVat" class="rounded bg-gray-700 border-gray-600 text-red-600 focus:ring-red-500">
                    <span class="text-xs text-gray-400">VAT 16%</span>
                </label>
            </div>

            {{-- Summary box --}}
            <div class="bg-gray-800 rounded-xl p-3 space-y-1.5">
                <div class="flex justify-between text-sm text-gray-400">
                    <span>Subtotal</span>
                    <span>KES {{ number_format($subtotal, 2) }}</span>
                </div>
                @if($discount > 0)
                <div class="flex justify-between text-sm text-red-400">
                    <span>Discount</span>
                    <span>− KES {{ number_format($discount, 2) }}</span>
                </div>
                @endif
                @if($vat > 0)
                <div class="flex justify-between text-sm text-gray-400">
                    <span>VAT (16%)</span>
                    <span>KES {{ number_format($vat, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-base text-white pt-1 border-t border-gray-700">
                    <span>TOTAL</span>
                    <span class="text-green-400">KES {{ number_format($grandTotal, 2) }}</span>
                </div>
            </div>

            {{-- Notes toggle --}}
            <div x-show="showNotes" x-cloak>
                <textarea
                    wire:model="notes"
                    rows="2"
                    placeholder="Sale notes..."
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg text-xs px-3 py-2 text-gray-300 focus:border-red-500 focus:outline-none resize-none"
                ></textarea>
            </div>

            {{-- Action buttons --}}
            <div class="grid grid-cols-2 gap-2">
                <button
                    wire:click="clearCart"
                    class="py-2.5 rounded-xl text-sm font-medium bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-white border border-gray-700 transition"
                >Clear</button>
                <button
                    wire:click="openPaymentModal"
                    @disabled(empty($cart))
                    class="py-2.5 rounded-xl text-sm font-bold bg-red-600 hover:bg-red-500 text-white transition disabled:opacity-40 disabled:cursor-not-allowed"
                >Charge KES {{ number_format($grandTotal, 2) }}</button>
            </div>

            <p class="text-center text-xs text-gray-700">
                {{ $cartCount }} item(s)
                @if($cartCount > 0)
                  · <button x-on:click="showNotes = !showNotes" class="text-red-500 hover:underline">note</button>
                @endif
            </p>
        </div>
    </div>

{{-- ════════════════════════════════════════════
     PAYMENT MODAL
════════════════════════════════════════════ --}}
@if($showPaymentModal)
<div
    class="absolute inset-0 bg-black/80 overflow-y-auto"
    style="z-index:9999;"
    x-data="mpesaPayment()"
    x-init="grandTotal = {{ $grandTotal }}; mpesaPhone = '{{ $customerPhone ?? '' }}'"
>
<div class="min-h-full flex items-center justify-center p-4 py-6">
    <div class="bg-gray-900 border border-gray-700 rounded-2xl w-full max-w-md shadow-2xl">

        <div class="flex items-center justify-between p-5 border-b border-gray-800">
            <h2 class="text-lg font-bold text-white">Payment</h2>
            <button wire:click="$set('showPaymentModal', false)" class="text-gray-500 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-5 space-y-3">
            {{-- Total --}}
            <div class="text-center py-2">
                <p class="text-sm text-gray-400">Total Due</p>
                <p class="text-4xl font-bold text-green-400">KES {{ number_format($grandTotal, 2) }}</p>
            </div>

            {{-- Payment methods --}}
            @php
                $labels = ['cash'=>'Cash','mpesa'=>'M-Pesa','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','credit'=>'Credit'];
                $icons  = ['cash'=>'💵','mpesa'=>'📱','bank_transfer'=>'🏦','cheque'=>'🗒️','credit'=>'📋'];
            @endphp

            @foreach($payments as $i => $pay)
            <div class="bg-gray-800 rounded-xl p-3">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-sm">{{ $icons[$pay['method']] }}</span>
                    <span class="text-sm font-medium text-gray-300">{{ $labels[$pay['method']] }}</span>
                </div>
                <div class="flex gap-2">
                    <div class="flex-1 relative">
                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-gray-500">KES</span>
                        <input
                            type="number"
                            wire:model.blur="payments.{{ $i }}.amount"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg text-sm pl-9 pr-2 py-2 text-gray-100 focus:border-red-500 focus:outline-none"
                            min="0" step="0.01" placeholder="0.00"
                        />
                    </div>
                    @if($pay['method'] === 'mpesa')
                    {{-- M-Pesa phone input --}}
                    <input
                        type="text"
                        x-model="mpesaPhone"
                        placeholder="07XXXXXXXX"
                        maxlength="12"
                        class="flex-1 bg-gray-700 border border-gray-600 rounded-lg text-sm px-3 py-2 text-gray-100 placeholder-gray-500 focus:border-green-500 focus:outline-none"
                    />
                    @elseif($pay['method'] !== 'cash')
                    <input
                        type="text"
                        wire:model="payments.{{ $i }}.reference"
                        placeholder="Reference"
                        class="flex-1 bg-gray-700 border border-gray-600 rounded-lg text-sm px-3 py-2 text-gray-100 placeholder-gray-500 focus:border-red-500 focus:outline-none"
                    />
                    @endif
                </div>

                {{-- STK Push button — only for M-Pesa row --}}
                @if($pay['method'] === 'mpesa')
                <div class="mt-2">
                    <button
                        type="button"
                        @click="initiateStkPush('{{ $nextSaleRef }}')"
                        :disabled="stkLoading || !mpesaPhone"
                        class="w-full py-2 rounded-lg text-sm font-bold transition flex items-center justify-center gap-2"
                        :class="stkLoading ? 'bg-gray-600 text-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-500 text-white'"
                    >
                        <span x-show="!stkLoading">📲 Send M-Pesa Prompt</span>
                        <span x-show="stkLoading" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            Sending...
                        </span>
                    </button>

                    {{-- Status display --}}
                    <div x-show="stkStatus" class="mt-2 rounded-lg px-3 py-2 text-sm text-center font-medium"
                        :class="{
                            'bg-yellow-900/40 text-yellow-300 border border-yellow-700': stkStatus === 'pending',
                            'bg-green-900/40 text-green-300 border border-green-700': stkStatus === 'completed',
                            'bg-red-900/40 text-red-300 border border-red-700': stkStatus === 'failed' || stkStatus === 'cancelled'
                        }"
                    >
                        <span x-show="stkStatus === 'pending'">⏳ Waiting for customer to approve on phone...</span>
                        <span x-show="stkStatus === 'completed'">✅ Payment received! <span x-text="stkReceipt ? '(' + stkReceipt + ')' : ''"></span></span>
                        <span x-show="stkStatus === 'failed'">❌ Payment failed. <span x-text="stkDesc"></span></span>
                        <span x-show="stkStatus === 'cancelled'">🚫 Cancelled by customer.</span>
                    </div>
                </div>
                @endif
            </div>
            @endforeach

            {{-- Tendered / change --}}
            <div class="bg-gray-800 rounded-xl p-3 space-y-1.5">
                <div class="flex justify-between text-sm text-gray-400">
                    <span>Tendered</span>
                    <span>KES {{ number_format($tendered, 2) }}</span>
                </div>
                @if($change > 0)
                <div class="flex justify-between text-sm font-bold text-yellow-400">
                    <span>Change</span>
                    <span>KES {{ number_format($change, 2) }}</span>
                </div>
                @endif
                @if($balanceDue > 0)
                <div class="flex justify-between text-sm font-bold text-red-400">
                    <span>Balance Due</span>
                    <span>KES {{ number_format($balanceDue, 2) }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="p-5 pt-0">
            <button
                wire:click="completeSale"
                wire:loading.attr="disabled"
                class="w-full py-3.5 rounded-xl font-bold text-white text-base bg-green-600 hover:bg-green-500 transition disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="completeSale">✔ Complete Sale</span>
                <span wire:loading wire:target="completeSale">Processing...</span>
            </button>
        </div>
    </div>
</div>
</div>
@endif

{{-- ════════════════════════════════════════════
     RECEIPT MODAL
════════════════════════════════════════════ --}}
@if($showReceipt && $completedSale)
<div class="absolute inset-0 bg-black/80 overflow-y-auto" style="z-index:9999;">
<div class="min-h-full flex items-center justify-center p-4 py-6">
    <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl text-gray-900 overflow-hidden">

        {{-- Success header --}}
        <div class="bg-green-600 p-4 text-center">
            <div class="text-white text-4xl mb-1">✔</div>
            <p class="text-white font-bold text-lg">Sale Complete!</p>
            <p class="text-green-200 text-sm">{{ $completedSale['sale_number'] }}</p>
        </div>

        {{-- Receipt body --}}
        <div class="p-4 font-mono text-xs" id="receipt-print">
            <div class="text-center mb-3">
                <p class="font-bold text-sm text-gray-900">GIGATEAM SOLUTIONS LIMITED</p>
                <p class="text-gray-500 text-[10px]">Secured & Connected</p>
                <p class="text-gray-500 text-[10px]">+254 111292948 / 718811661</p>
            </div>

            <div class="border-t border-dashed border-gray-300 my-2"></div>

            <div class="space-y-0.5 text-gray-600 mb-2">
                <div class="flex justify-between">
                    <span>Receipt</span>
                    <span class="font-bold text-gray-800">{{ $completedSale['invoice_number'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Date</span>
                    <span>{{ $completedSale['date'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Customer</span>
                    <span class="truncate ml-2 max-w-[140px]">{{ $completedSale['customer'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Cashier</span>
                    <span>{{ $completedSale['cashier'] }}</span>
                </div>
            </div>

            <div class="border-t border-dashed border-gray-300 my-2"></div>

            @foreach($completedSale['items'] as $item)
            <div class="mb-1">
                <p class="text-gray-800 text-[11px]">{{ Str::limit($item['name'], 28) }}</p>
                <div class="flex justify-between text-gray-500">
                    <span>{{ $item['quantity'] }} × {{ number_format($item['unit_price'], 2) }}</span>
                    <span class="font-medium text-gray-700">{{ number_format($item['total'], 2) }}</span>
                </div>
            </div>
            @endforeach

            <div class="border-t border-dashed border-gray-300 my-2"></div>

            <div class="space-y-0.5">
                @if($completedSale['discount'] > 0)
                <div class="flex justify-between text-red-600">
                    <span>Discount</span>
                    <span>- {{ number_format($completedSale['discount'], 2) }}</span>
                </div>
                @endif
                @if($completedSale['vat'] > 0)
                <div class="flex justify-between text-gray-600">
                    <span>VAT 16%</span>
                    <span>{{ number_format($completedSale['vat'], 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-gray-900 text-sm">
                    <span>TOTAL</span>
                    <span>KES {{ number_format($completedSale['total'], 2) }}</span>
                </div>
            </div>

            <div class="border-t border-dashed border-gray-300 my-2"></div>

            @foreach($completedSale['payments'] as $p)
            @if((float)($p['amount'] ?? 0) > 0)
            <div class="flex justify-between text-gray-600">
                <span>{{ ucfirst(str_replace('_', ' ', $p['method'])) }}{{ $p['reference'] ? ' ('.$p['reference'].')' : '' }}</span>
                <span>KES {{ number_format($p['amount'], 2) }}</span>
            </div>
            @endif
            @endforeach

            @if($completedSale['change'] > 0)
            <div class="flex justify-between font-bold text-green-700 mt-1">
                <span>Change</span>
                <span>KES {{ number_format($completedSale['change'], 2) }}</span>
            </div>
            @endif

            <div class="border-t border-dashed border-gray-300 my-2"></div>

            <div class="text-center text-gray-400 text-[10px] space-y-0.5">
                <p>Goods sold are NOT returnable</p>
                <p class="font-medium text-gray-600">Thank you for your business!</p>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="p-4 flex gap-3 border-t border-gray-100">
            <button
                onclick="window.print()"
                class="flex-1 py-2.5 rounded-xl border border-gray-300 text-sm font-medium hover:bg-gray-50 transition text-gray-700"
            >🖨 Print</button>
            <a
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

{{-- ════════════════════════════════════════════
     M-PESA ALPINE.JS COMPONENT
════════════════════════════════════════════ --}}
<script>
function mpesaPayment() {
    return {
        mpesaPhone: '',
        stkLoading: false,
        stkStatus: null,
        stkReceipt: null,
        stkDesc: null,
        checkoutRequestId: null,
        pollTimer: null,
        grandTotal: 0,

        async initiateStkPush(reference) {
            if (!this.mpesaPhone) {
                alert('Please enter the customer phone number.');
                return;
            }

            // Get amount from M-Pesa payment row input
            const mpesaAmountInput = document.querySelector('input[wire\\:model\\.blur="payments.1.amount"]');
            const amount = mpesaAmountInput ? parseFloat(mpesaAmountInput.value) : 0;

            if (!amount || amount <= 0) {
                alert('Please enter the M-Pesa amount first.');
                return;
            }

            this.stkLoading = true;
            this.stkStatus  = null;
            this.stkReceipt = null;
            this.stkDesc    = null;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                const res = await fetch('/mpesa/stk-push', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        phone:     this.mpesaPhone,
                        amount:    amount,
                        reference: reference || 'POS-SALE',
                        sale_id:   null
                    })
                });

                const data = await res.json();

                if (data.success) {
                    this.checkoutRequestId = data.checkout_request_id;
                    this.stkStatus  = 'pending';
                    this.stkLoading = false;
                    this.startPolling();
                } else {
                    this.stkLoading = false;
                    this.stkStatus  = 'failed';
                    this.stkDesc    = data.message || 'Failed to send prompt.';
                }
            } catch (e) {
                this.stkLoading = false;
                this.stkStatus  = 'failed';
                this.stkDesc    = 'Network error. Check connection.';
            }
        },

        startPolling() {
            let attempts = 0;
            const maxAttempts = 24; // ~2 minutes at 5s intervals

            this.pollTimer = setInterval(async () => {
                attempts++;
                if (attempts > maxAttempts) {
                    clearInterval(this.pollTimer);
                    this.stkStatus = 'failed';
                    this.stkDesc   = 'Timed out. Ask customer to retry.';
                    return;
                }

                try {
                    const res  = await fetch(`/mpesa/status/${this.checkoutRequestId}`);
                    const data = await res.json();

                    if (data.status === 'completed') {
                        clearInterval(this.pollTimer);
                        this.stkStatus  = 'completed';
                        this.stkReceipt = data.mpesa_receipt;
                    } else if (data.status === 'failed' || data.status === 'cancelled') {
                        clearInterval(this.pollTimer);
                        this.stkStatus = data.status;
                        this.stkDesc   = data.result_desc || '';
                    }
                } catch (e) {
                    // silent — keep polling
                }
            }, 5000);
        },

        destroy() {
            if (this.pollTimer) clearInterval(this.pollTimer);
        }
    }
}
</script>